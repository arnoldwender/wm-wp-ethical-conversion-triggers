<?php
/**
 * Main plugin class.
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace WMEthicalConversionTriggers;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton Plugin Class.
 */
final class Plugin {

    private static ?self $instance = null;

    public string $version = WM_ECT_VERSION;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \Exception( 'Cannot unserialize singleton' );
    }

    private function includes(): void {
        require_once WM_ECT_PATH . 'includes/class-stock-verifier.php';
        require_once WM_ECT_PATH . 'includes/class-webhook-validator.php';
        require_once WM_ECT_PATH . 'includes/class-frontend.php';
        require_once WM_ECT_PATH . 'includes/class-settings.php';
        require_once WM_ECT_PATH . 'includes/class-integrations.php';
    }

    private function init_hooks(): void {
        // El hub arma su registro con este filtro al final de plugins_loaded (prioridad 99). Va antes
        // de la guarda de WooCommerce para que, sin WooCommerce, el hub muestre el plugin en ERROR en
        // vez de no mostrarlo.
        add_filter( 'wm_register_suite_module', array( Integrations::class, 'register_suite_module' ) );

        // Sin WooCommerce no hay ficha de producto, ni tabla de pedidos, ni producto que
        // actualizar: arrancar el frontend llamaba a is_product() y el sitio entero respondía
        // 500 (medido en una instalación local de WordPress, WordPress 7.1, 2026-09-14). Sin WooCommerce el plugin
        // registra únicamente el aviso de administración y el filtro del hub.
        if ( ! self::woocommerce_active() ) {
            add_action( 'admin_notices', array( __CLASS__, 'missing_woocommerce_notice' ) );
            return;
        }

        add_action( 'init', array( $this, 'init' ), 0 );

        // Registrar endpoint para webhooks si se habilita
        add_action( 'rest_api_init', array( Webhook_Validator::class, 'register_routes' ) );

        Settings::register_hooks();
        Integrations::register_hooks();
    }

    /**
     * WooCommerce define su clase principal al cargar su fichero, antes de plugins_loaded.
     */
    public static function woocommerce_active(): bool {
        return class_exists( 'WooCommerce' );
    }

    public static function missing_woocommerce_notice(): void {
        echo '<div class="notice notice-error"><p>'
            . esc_html__( 'WM Ethical Conversion Triggers benötigt WooCommerce. Ohne WooCommerce zeigt das Plugin nichts an.', 'wm-ethical-conversion-triggers' )
            . '</p></div>';
    }

    public function init(): void {
        if ( function_exists( 'load_plugin_textdomain' ) && defined( 'WM_ECT_FILE' ) ) {
            load_plugin_textdomain(
                'wm-ethical-conversion-triggers',
                false,
                dirname( plugin_basename( WM_ECT_FILE ) ) . '/languages'
            );
        }

        // Inicializar frontend si no es admin
        if ( ! is_admin() || wp_doing_ajax() ) {
            Frontend::instance();
        }

        if ( function_exists( 'do_action' ) ) {
            do_action( 'wm_ethical_triggers_ready', $this );
        }
    }
}
