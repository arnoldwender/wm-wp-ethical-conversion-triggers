<?php
/**
 * Lógica de verificación de inventario y ventas (Legal/UWG).
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace WMEthicalConversionTriggers;

defined( 'ABSPATH' ) || exit;

class Stock_Verifier {

    /**
     * Pedidos pagados que contienen el producto en los últimos N días. Compatible con HPOS (no usa postmeta).
     *
     * Cuenta PEDIDOS distintos con estado pagado (wc_get_is_paid_statuses(): processing y completed por
     * defecto), sin reembolsos (parent_id = 0 en wc_order_stats). Hasta el 2026-09-15 sumaba product_qty
     * de todos los pedidos, incluidos pendientes, fallidos y cancelados, y el texto lo presentaba como
     * "N personas": un pedido de 3 unidades eran "3 personas". En un plugin cuya promesa es no inventar
     * prueba social, eso era el defecto.
     */
    public static function get_verified_sales( int $product_id, int $days = 7 ): int {
        // Manejo de caché via Transients API
        $transient_key = 'wm_ect_sales_' . $product_id;
        $cached_sales = get_transient( $transient_key );

        if ( false !== $cached_sales ) {
            return (int) $cached_sales;
        }

        global $wpdb;

        // Las tablas de analítica de WooCommerce guardan date_created en hora local del sitio y el estado
        // con prefijo "wc-" (Reports DataStore::normalize_order_status()).
        $paid     = function_exists( 'wc_get_is_paid_statuses' ) ? (array) wc_get_is_paid_statuses() : array( 'processing', 'completed' );
        $statuses = array_values( array_map( static function ( $status ) {
            return 'wc-' . preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $status ) );
        }, $paid ) );
        $timezone       = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
        $date_threshold = ( new \DateTimeImmutable( 'now', $timezone ) )->modify( '-' . max( 0, $days ) . ' days' )->format( 'Y-m-d H:i:s' );
        $in             = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

        // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $in holds only %s placeholders, one per status.
        $sales = $wpdb->get_var( (string) $wpdb->prepare( "
            SELECT COUNT(DISTINCT lookup.order_id)
            FROM {$wpdb->prefix}wc_order_product_lookup AS lookup
            INNER JOIN {$wpdb->prefix}wc_order_stats AS stats ON stats.order_id = lookup.order_id
            WHERE lookup.product_id = %d
            AND stats.parent_id = 0
            AND stats.status IN ( {$in} )
            AND lookup.date_created >= %s
        ", $product_id, ...array_merge( $statuses, array( $date_threshold ) ) ) );

        $sales = (int) $sales;

        // Guardar transient. Expiración de 1 hora para evitar recálculos constantes.
        set_transient( $transient_key, $sales, HOUR_IN_SECONDS );

        return $sales;
    }

    /**
     * Verifica el stock real gestionado por WooCommerce.
     *
     * @param mixed $product Instancia de WC_Product.
     */
    public static function get_verified_stock( $product ): ?int {
        if ( ! is_object( $product ) || ! method_exists( $product, 'get_manage_stock' ) || ! $product->get_manage_stock() ) {
            return null; // No hay límite, no mostrar escasez
        }

        $stock = $product->get_stock_quantity();
        return (int) $stock;
    }

    /**
     * Limpia los transients cuando un producto cambia (compra, actualización de stock).
     */
    public static function clear_product_transients( int $product_id ): void {
        delete_transient( 'wm_ect_sales_' . $product_id );
    }
}

// Hooks para limpiar transients cuando sea necesario
add_action( 'woocommerce_update_product', array( __NAMESPACE__ . '\\Stock_Verifier', 'clear_product_transients' ) );
