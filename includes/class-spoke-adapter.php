<?php
/**
 * WM Suite Hub module of this plugin.
 *
 * Health, telemetry, the SBOM entry and a self test, computed from WooCommerce, the two analytics tables the
 * order count reads and the plugin's own rules. Loaded only when the hub's contract exists: the hub collects
 * objects that implement WM_Plugin_Module_Interface through the `wm_register_suite_module` filter. Up to 1.1.4
 * the plugin called a function the hub does not define, or fired that filter as an action with an array, and
 * never appeared in the hub (measured 2026-11-20 with the hub's own Module_Registry: 22 of 23 plugins).
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace WMEthicalConversionTriggers;

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
    return;
}

use ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface;

final class Spoke_Adapter implements WM_Plugin_Module_Interface {

    /** Analytics tables the paid-order count joins (see Stock_Verifier::get_verified_sales()). */
    private const ANALYTICS_TABLES = array( 'wc_order_product_lookup', 'wc_order_stats' );

    public function get_module_slug(): string {
        return 'wm-ethical-conversion-triggers';
    }

    public function get_name(): string {
        return 'WM Ethical Conversion Triggers';
    }

    public function get_version(): string {
        return WM_ECT_VERSION;
    }

    public function get_description(): string {
        return 'Order and low-stock notices on WooCommerce product pages, shown only from real paid orders and managed stock.';
    }

    /** ERROR without WooCommerce, DEGRADED when an analytics table is missing (no order notice can appear). */
    public function get_health_status(): string {
        if ( ! Plugin::woocommerce_active() ) {
            return 'ERROR';
        }
        return array() === self::missing_tables() ? 'HEALTHY' : 'DEGRADED';
    }

    public function get_sbom_data(): array {
        return array(
            'bom-ref'     => 'pkg:wordpress/wm-ethical-conversion-triggers@' . $this->get_version(),
            'type'        => 'application',
            'name'        => $this->get_name(),
            'version'     => $this->get_version(),
            'description' => $this->get_description(),
            'scope'       => 'required',
            // Hash of the loader file only, like the other plugins of the family; it does not cover includes/.
            'hashes'      => array(
                array(
                    'alg'     => 'SHA-256',
                    'content' => (string) hash_file( 'sha256', WM_ECT_FILE ),
                ),
            ),
            // CycloneDX 1.6: "id" must be an SPDX identifier and cannot be combined with "name".
            'licenses'    => array(
                array(
                    'license' => array(
                        'id' => 'GPL-2.0-or-later',
                    ),
                ),
            ),
            'properties'  => array(
                array(
                    'name'  => 'wm:features:notices',
                    'value' => 'paid-order count over 7 days and a low-stock notice, only from managed stock',
                ),
                array(
                    'name'  => 'wm:features:webhook',
                    'value' => 'stock update endpoint wm-ect/v1/stock-update with an HMAC-SHA256 signature',
                ),
                array(
                    'name'  => 'wm:features:integrations',
                    'value' => 'Block Bindings source wm-ethical-triggers/metrics and GenerateBlocks 2.x tag wm_ethical_trigger',
                ),
            ),
        );
    }

    public function get_dependencies(): array {
        return array();
    }

    /** The settings page is registered only with WooCommerce active; without it there is no page to link to. */
    public function get_settings_url(): ?string {
        return Plugin::woocommerce_active() ? admin_url( 'options-general.php?page=' . Settings::PAGE_SLUG ) : null;
    }

    public function get_telemetry_metrics(): array {
        return array(
            'woocommerce_active'        => Plugin::woocommerce_active() ? 'YES' : 'NO',
            'analytics_tables'          => array() === self::missing_tables() ? 'YES' : 'NO',
            'webhook_secret_configured' => '' !== Webhook_Validator::configured_secret() ? 'YES' : 'NO',
            'delete_data_on_uninstall'  => filter_var( get_option( Settings::OPTION_DELETE_ON_UNINSTALL, '0' ), FILTER_VALIDATE_BOOLEAN ) ? 'YES' : 'NO',
        );
    }

    public function execute_action( string $action, array $params = array() ): array {
        unset( $params );

        if ( 'self_test' !== $action ) {
            return array(
                'success' => false,
                'message' => sprintf( 'Unbekannte Spoke-Aktion "%s".', esc_html( $action ) ),
            );
        }

        // Product doubles that never touch the database: the rule is checked on its own code path.
        $unmanaged = new class() {
            public function get_manage_stock(): bool {
                return false;
            }
            public function get_stock_quantity(): int {
                return 99;
            }
        };
        $managed   = new class() {
            public function get_manage_stock(): bool {
                return true;
            }
            public function get_stock_quantity(): int {
                return 3;
            }
        };

        $checks = array(
            'WooCommerce aktiv'               => Plugin::woocommerce_active(),
            'Analytics-Tabellen vorhanden'    => array() === self::missing_tables(),
            'kein Bestand ohne Lagerführung'  => null === Stock_Verifier::get_verified_stock( $unmanaged ) && 3 === Stock_Verifier::get_verified_stock( $managed ),
            'Webhook ohne Signatur abgelehnt' => Webhook_Validator::verify_signature( new \WP_REST_Request() ) instanceof \WP_Error,
        );
        $failed = array_keys( array_filter( $checks, static fn( bool $ok ): bool => ! $ok ) );

        return array(
            'success' => array() === $failed,
            'message' => array() === $failed
                ? 'Selbsttest bestanden: WooCommerce, Analytics-Tabellen, Bestandsregel und Webhook-Signatur.'
                : 'Selbsttest fehlgeschlagen: ' . implode( ', ', $failed ) . '.',
        );
    }

    /** @return array<int, string> analytics tables that do not exist under the site prefix */
    private static function missing_tables(): array {
        global $wpdb;
        $missing = array();
        foreach ( self::ANALYTICS_TABLES as $table ) {
            $name = $wpdb->prefix . $table;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- read-only existence check, run only when the hub polls the module.
            if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $name ) ) ) !== $name ) {
                $missing[] = $table;
            }
        }
        return $missing;
    }
}
