<?php
/**
 * Plugin Name:       WM Ethical Conversion Triggers
 * Plugin URI:        https://arnoldwender.com/wordpress-plugins/wm-ethical-conversion-triggers/
 * Description:       Order and low-stock notices on WooCommerce product pages, shown only from real paid orders and managed stock.
 * Version:           1.2.0
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * Author:            Arnold Wender
 * Author URI:        https://arnoldwender.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wm-ethical-conversion-triggers
 * Domain Path:       /languages
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// Declarar compatibilidad con High-Performance Order Storage (HPOS)
add_action( 'before_woocommerce_init', function (): void {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// Constantes
define( 'WM_ECT_VERSION', '1.2.0' );
define( 'WM_ECT_FILE', __FILE__ );
define( 'WM_ECT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WM_ECT_URL', plugin_dir_url( __FILE__ ) );

// Inicializador
function wm_ect_init(): void {
    load_plugin_textdomain(
        'wm-ethical-conversion-triggers',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );

    require_once WM_ECT_PATH . 'includes/class-plugin.php';

    if ( class_exists( 'WMEthicalConversionTriggers\\Plugin' ) ) {
        \WMEthicalConversionTriggers\Plugin::instance();
    }
}
add_action( 'plugins_loaded', 'wm_ect_init' );

// Activación
register_activation_hook( __FILE__, 'wm_ect_activate' );
function wm_ect_activate(): void {
    if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( esc_html__( 'WM Ethical Conversion Triggers benötigt PHP 8.0 oder neuer.', 'wm-ethical-conversion-triggers' ) );
    }

    if ( function_exists( 'get_option' ) && function_exists( 'add_option' ) ) {
        if ( false === get_option( 'wm_ethical_triggers_delete_data_on_uninstall', false ) ) {
            add_option( 'wm_ethical_triggers_delete_data_on_uninstall', '0' );
        }
    }
}

// Desactivación
register_deactivation_hook( __FILE__, 'wm_ect_deactivate' );
function wm_ect_deactivate(): void {
    // Tareas de limpieza en desactivación no borran opciones por diseño (D-UNINSTALL).
}
