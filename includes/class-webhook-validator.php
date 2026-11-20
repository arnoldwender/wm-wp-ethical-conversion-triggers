<?php
/**
 * Validador de Webhooks HMAC (Seguridad).
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace WMEthicalConversionTriggers;

defined( 'ABSPATH' ) || exit;

class Webhook_Validator {

    public static function register_routes(): void {
        register_rest_route( 'wm-ect/v1', '/stock-update', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'handle_webhook' ),
            'permission_callback' => array( __CLASS__, 'verify_signature' ),
        ) );
    }

    /**
     * Verifica la firma HMAC-SHA256 de la petición REST en tiempo constante.
     *
     * @return bool|\WP_Error
     */
    public static function verify_signature( \WP_REST_Request $request ) {
        $secret = self::configured_secret();

        if ( '' === $secret ) {
            return new \WP_Error( 'missing_secret', 'Webhook secret is not configured.', array( 'status' => 500 ) );
        }

        $signature = $request->get_header( 'x-wc-webhook-signature' );
        if ( ! is_string( $signature ) || '' === $signature ) {
            return new \WP_Error( 'missing_signature', 'Signature required.', array( 'status' => 401 ) );
        }

        $payload            = (string) $request->get_body();
        $expected_signature = base64_encode( hash_hmac( 'sha256', $payload, $secret, true ) );

        if ( ! hash_equals( $expected_signature, $signature ) ) {
            return new \WP_Error( 'invalid_signature', 'Invalid signature.', array( 'status' => 403 ) );
        }

        return true;
    }

    /**
     * Secreto compartido del webhook: la constante WM_ECT_WEBHOOK_SECRET o, sin ella, la opción
     * wm_ect_webhook_secret; '' si no hay ninguno. Lo leen la firma y la telemetría del hub.
     */
    public static function configured_secret(): string {
        // En un entorno de producción, WM_ECT_WEBHOOK_SECRET debe estar en wp-config.php o en opciones seguras
        $raw_secret = defined( 'WM_ECT_WEBHOOK_SECRET' ) ? WM_ECT_WEBHOOK_SECRET : get_option( 'wm_ect_webhook_secret', '' );
        return is_string( $raw_secret ) ? $raw_secret : '';
    }

    /**
     * Actualiza el stock real del producto en WooCommerce tras verificar la firma HMAC.
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public static function handle_webhook( \WP_REST_Request $request ) {
        $params = $request->get_json_params();

        if ( ! is_array( $params ) || ! isset( $params['product_id'] ) || ! isset( $params['stock_quantity'] ) ) {
            return new \WP_Error( 'invalid_payload', 'Payload needs product_id and stock_quantity.', array( 'status' => 400 ) );
        }

        $product_id = absint( $params['product_id'] );
        $stock      = (int) $params['stock_quantity'];

        // La ruta sólo se registra con WooCommerce activo; esta guarda cubre una desactivación
        // de WooCommerce con la ruta ya cacheada o una llamada directa al handler.
        if ( ! function_exists( 'wc_get_product' ) ) {
            return new \WP_Error( 'woocommerce_missing', 'WooCommerce is not active.', array( 'status' => 501 ) );
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return new \WP_Error( 'not_found', 'Product not found.', array( 'status' => 404 ) );
        }

        $product->set_manage_stock( true );
        $product->set_stock_quantity( $stock );
        $product->save();

        // Limpiar transients
        Stock_Verifier::clear_product_transients( $product_id );

        if ( function_exists( 'do_action' ) ) {
            do_action( 'wm_ethical_triggers_stock_updated', $product_id, $stock );
        }

        return rest_ensure_response( array(
            'success' => true,
            'message' => 'Stock updated.',
        ) );
    }
}
