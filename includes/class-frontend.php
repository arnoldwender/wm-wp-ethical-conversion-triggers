<?php
/**
 * Frontend: the order and low-stock notice on the product page.
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace WMEthicalConversionTriggers;

defined( 'ABSPATH' ) || exit;

class Frontend {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Inyectar en la página de producto de WooCommerce
        add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_ethical_trigger' ), 15 );
    }

    public function enqueue_scripts(): void {
        // is_product() es de WooCommerce: llamarla sin WooCommerce tiraba el sitio entero.
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            return;
        }

        wp_enqueue_style(
            'wm-ect-style',
            WM_ECT_URL . 'assets/css/ethical-triggers.css',
            array(),
            WM_ECT_VERSION
        );

        wp_enqueue_script(
            'wm-ect-script',
            WM_ECT_URL . 'assets/js/ethical-triggers.js',
            array(),
            WM_ECT_VERSION,
            true
        );
    }

    /**
     * Calcula las frases verificadas para un producto preservando los invariantes UWG §5.
     *
     * @param mixed $product Instancia de WC_Product.
     * @return array{sales: ?string, stock: ?string}
     */
    public static function get_verified_notices( $product ): array {
        if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
            return array(
                'sales' => null,
                'stock' => null,
            );
        }

        $product_id     = (int) $product->get_id();
        $default_config = array(
            'low_stock_threshold' => 5,
            'enable_sales_notice' => true,
            'enable_stock_notice' => true,
        );
        $config = function_exists( 'apply_filters' )
            ? apply_filters( 'wm_ethical_triggers_config', $default_config, $product_id )
            : $default_config;
        if ( ! is_array( $config ) ) {
            $config = $default_config;
        }

        $threshold    = isset( $config['low_stock_threshold'] ) ? max( 1, min( 50, (int) $config['low_stock_threshold'] ) ) : 5;
        $enable_sales = ! array_key_exists( 'enable_sales_notice', $config ) || (bool) $config['enable_sales_notice'];
        $enable_stock = ! array_key_exists( 'enable_stock_notice', $config ) || (bool) $config['enable_stock_notice'];

        $sales = Stock_Verifier::get_verified_sales( $product_id );
        $stock = Stock_Verifier::get_verified_stock( $product );

        // Qué se va a emitir realmente. Las dos condiciones se calculan ANTES
        // del contenedor porque la guarda tiene que ser la negación exacta de
        // lo que se pinta: la versión anterior era `! $sales && null === $stock`,
        // que deja pasar el caso "cero ventas + stock gestionado alto" — ahí no
        // se emite ninguna de las dos frases y quedaba un contenedor VACÍO con
        // aria-live/role=status, que un lector de pantalla anuncia igual.
        $show_sales = $enable_sales && $sales > 0;
        $show_stock = $enable_stock && ( null !== $stock && $stock > 0 && $stock <= $threshold );

        $sales_notice = null;
        if ( $show_sales ) {
            // Pedidos pagados, no personas ni unidades. Hasta el 2026-09-15: "%d personas han comprado …".
            /* translators: %d: paid orders containing this product in the last 7 days */
            $sales_text   = _n( '%d Bestellung dieses Artikels in den letzten 7 Tagen.', '%d Bestellungen dieses Artikels in den letzten 7 Tagen.', (int) $sales, 'wm-ethical-conversion-triggers' );
            $sales_notice = sprintf( $sales_text, (int) $sales );
        }

        $stock_notice = null;
        if ( $show_stock ) {
            /* translators: %d: units left in WooCommerce-managed stock (1 to 5) */
            $stock_text   = _n( 'Nur noch %d Stück auf Lager.', 'Nur noch %d Stück auf Lager.', (int) $stock, 'wm-ethical-conversion-triggers' );
            $stock_notice = sprintf( $stock_text, (int) $stock );
        }

        return array(
            'sales' => $sales_notice,
            'stock' => $stock_notice,
        );
    }

    /**
     * Formatea el aviso verificado como texto plano para Block Bindings / GenerateBlocks.
     *
     * @param mixed  $product Instancia de WC_Product.
     * @param string $field   'summary' | 'sales_notice' | 'stock_notice'.
     */
    public static function format_notice_text( $product, string $field = 'summary' ): string {
        $notices = self::get_verified_notices( $product );

        if ( 'sales_notice' === $field ) {
            return $notices['sales'] ?? '';
        }

        if ( 'stock_notice' === $field ) {
            return $notices['stock'] ?? '';
        }

        $parts = array_values( array_filter( array( $notices['sales'], $notices['stock'] ) ) );
        return implode( ' · ', $parts );
    }

    public function render_ethical_trigger(): void {
        global $product;

        $notices = self::get_verified_notices( $product );

        // UWG §5: sin dato verificado no se enseña nada. No inventar escasez.
        if ( null === $notices['sales'] && null === $notices['stock'] ) {
            return;
        }

        echo '<div class="wm-ect-container" aria-live="polite" role="status">';

        if ( null !== $notices['sales'] ) {
            echo '<p class="wm-ect-sales"><span class="wm-ect-icon wm-ect-icon-verified" aria-hidden="true"></span> '
                . esc_html( $notices['sales'] )
                . '</p>';
        }

        if ( null !== $notices['stock'] ) {
            echo '<p class="wm-ect-stock wm-ect-low-stock"><span class="wm-ect-icon wm-ect-icon-alert" aria-hidden="true"></span> '
                . esc_html( $notices['stock'] )
                . '</p>';
        }

        echo '</div>';
    }
}
