<?php
/**
 * Suite de tests standalone para wm-ethical-conversion-triggers.
 *
 * Sin dependencia de WordPress ni de MySQL: las funciones de WP que el plugin
 * usa se sustituyen por dobles en memoria, igual que hace el resto de la flota
 * (wm-social-care-accessibility-hub, wm-newsticker, wm-suite-hub...). Así el
 * CI puede correrla con un `php tests/test-suite.php` y nada más.
 *
 * QUÉ SE PRUEBA Y POR QUÉ ESTO
 * ---------------------------
 * Este plugin muestra avisos de pedidos y escasez solo con datos reales y
 * verificables. Esa promesa se sostiene sobre invariantes muy concretos, y son
 * ESOS los que se prueban — no solo la fontanería:
 *
 *   - Escasez sólo con stock REAL gestionado. Si WooCommerce no gestiona el
 *     stock no hay número que enseñar, y enseñarlo igualmente sería
 *     exactamente la escasez inventada que el §5 UWG persigue.
 *   - Prueba social sólo con ventas REALES. Cero ventas, cero frase.
 *   - La firma HMAC del webhook se compara en tiempo constante y rechaza
 *     ausencia, firma inválida y secreto sin configurar.
 *   - Desinstalación conservadora por defecto (D-UNINSTALL): sin opt-in
 *     explícito `uninstall.php` no borra opciones ni transients.
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Dobles de WordPress
// ---------------------------------------------------------------------------

define('ABSPATH', __DIR__ . '/../');
define('WM_ECT_VERSION', '1.2.0');
define('WM_ECT_PATH', __DIR__ . '/../');
define('WM_ECT_URL', 'https://example.test/wp-content/plugins/wm-ethical-conversion-triggers/');
define('WM_ECT_FILE', __DIR__ . '/../wm-ethical-conversion-triggers.php');
define('HOUR_IN_SECONDS', 3600);

$GLOBALS['wm_ect_transients']          = [];
$GLOBALS['wm_ect_actions']             = [];
$GLOBALS['wm_ect_filters']             = [];
$GLOBALS['wm_ect_fired_actions']       = [];
$GLOBALS['wm_ect_options']             = [];
$GLOBALS['wm_ect_can_manage_options']  = false;
$GLOBALS['wm_ect_registered_settings'] = [];
$GLOBALS['wm_ect_block_bindings']      = [];
$GLOBALS['wm_ect_gb_tags']             = [];
$GLOBALS['wm_ect_products']            = [];
$GLOBALS['wm_ect_rest_routes']         = [];

function add_action(string $hook, $cb, int $prio = 10, int $args = 1): void {
    unset($prio, $args);
    $GLOBALS['wm_ect_actions'][$hook][] = $cb;
}

function do_action(string $hook, ...$args): void {
    $GLOBALS['wm_ect_fired_actions'][$hook][] = $args;
    foreach ($GLOBALS['wm_ect_actions'][$hook] ?? [] as $cb) {
        if (is_callable($cb)) {
            $cb(...$args);
        }
    }
}

function add_filter(string $hook, $cb, int $prio = 10, int $args = 1): void {
    unset($prio, $args);
    $GLOBALS['wm_ect_filters'][$hook][] = $cb;
}

function apply_filters(string $hook, $value, ...$args) {
    foreach ($GLOBALS['wm_ect_filters'][$hook] ?? [] as $cb) {
        if (is_callable($cb)) {
            $value = $cb($value, ...$args);
        }
    }
    return $value;
}

function get_option(string $key, $default = false) {
    return array_key_exists($key, $GLOBALS['wm_ect_options'])
        ? $GLOBALS['wm_ect_options'][$key]
        : $default;
}

function add_option(string $key, $value = ''): bool {
    if (array_key_exists($key, $GLOBALS['wm_ect_options'])) {
        return false;
    }
    $GLOBALS['wm_ect_options'][$key] = $value;
    return true;
}

function update_option(string $key, $value): bool {
    $GLOBALS['wm_ect_options'][$key] = $value;
    return true;
}

function delete_option(string $key): bool {
    unset($GLOBALS['wm_ect_options'][$key]);
    return true;
}

function get_transient(string $key) {
    return $GLOBALS['wm_ect_transients'][$key] ?? false;
}
function set_transient(string $key, $value, int $ttl = 0): bool {
    unset($ttl);
    $GLOBALS['wm_ect_transients'][$key] = $value;
    return true;
}
function delete_transient(string $key): bool {
    unset($GLOBALS['wm_ect_transients'][$key]);
    return true;
}

function esc_html(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function esc_attr(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function absint($val): int {
    return abs((int) $val);
}
function current_user_can(string $cap): bool {
    return 'manage_options' === $cap && (bool) $GLOBALS['wm_ect_can_manage_options'];
}
function admin_url(string $path = ''): string {
    return 'https://example.test/wp-admin/' . ltrim($path, '/');
}
function register_setting(string $group, string $name, array $args = []): void {
    $GLOBALS['wm_ect_registered_settings'][$name] = ['group' => $group, 'args' => $args];
}
function add_settings_section(string $id, string $title, $cb, string $page): void {
    unset($id, $title, $cb, $page);
}
function add_settings_field(string $id, string $title, $cb, string $page, string $section = 'default', array $args = []): void {
    unset($id, $title, $cb, $page, $section, $args);
}
function add_options_page(string $page_title, string $menu_title, string $cap, string $slug, $cb): string {
    unset($page_title, $menu_title, $cap, $cb);
    return 'settings_page_' . $slug;
}
function settings_fields(string $group): void {
    echo '<input type="hidden" name="option_page" value="' . esc_attr($group) . '" />';
}
function do_settings_sections(string $page): void {
    unset($page);
    \WMEthicalConversionTriggers\Settings::render_delete_field();
}
function submit_button(): void {
    echo '<button type="submit">Save</button>';
}
function register_block_bindings_source(string $name, array $props): bool {
    $GLOBALS['wm_ect_block_bindings'][$name] = $props;
    return true;
}

class GenerateBlocks_Register_Dynamic_Tag {
    /** @param array<string,mixed> $args */
    public function __construct(array $args) {
        $tag = isset($args['tag']) && is_string($args['tag']) ? $args['tag'] : '';
        $GLOBALS['wm_ect_gb_tags'][$tag] = $args;
    }
}

class WP_Error {
    /** @param array<string,mixed> $data */
    public function __construct(
        private string $code,
        private string $message,
        private array $data = []
    ) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
    /** @return array<string,mixed> */
    public function get_error_data(): array { return $this->data; }
}

class WP_REST_Server {
    public const CREATABLE = 'POST';
}

class WP_REST_Request {
    /**
     * @param array<string,string> $headers
     * @param array<string,mixed>|null $json
     */
    public function __construct(
        private string $body = '',
        private array $headers = [],
        private ?array $json = null
    ) {}
    public function get_header(string $key): ?string {
        $lower = strtolower($key);
        foreach ($this->headers as $k => $v) {
            if (strtolower($k) === $lower) {
                return $v;
            }
        }
        return null;
    }
    public function get_body(): string { return $this->body; }
    /** @return array<string,mixed>|null */
    public function get_json_params(): ?array { return $this->json; }
}

function register_rest_route(string $ns, string $route, array $args): void {
    $GLOBALS['wm_ect_rest_routes'][$ns . $route] = $args;
}
/** @param mixed $response */
function rest_ensure_response($response) {
    return $response;
}

// El doble de la traducción devuelve el original: lo que se prueba es la
// lógica de cuándo se emite un mensaje, no su traducción.
function esc_html__(string $s, string $domain = ''): string { unset($domain); return $s; }
function __(string $s, string $domain = ''): string { unset($domain); return $s; }
function _n(string $single, string $plural, int $number, string $domain = ''): string { unset($domain); return 1 === $number ? $single : $plural; }

/** Producto de WooCommerce reducido a lo que el plugin consulta y actualiza. */
class WC_Product {
    public bool $saved = false;

    public function __construct(
        private int $id,
        private bool $manage_stock,
        private ?int $stock
    ) {}
    public function get_id(): int { return $this->id; }
    public function get_manage_stock(): bool { return $this->manage_stock; }
    public function get_stock_quantity(): ?int { return $this->stock; }
    public function set_manage_stock(bool $manage): void { $this->manage_stock = $manage; }
    public function set_stock_quantity(int $qty): void { $this->stock = $qty; }
    public function save(): void { $this->saved = true; }
}

/**
 * Doble de $wpdb. Registra cada consulta para poder afirmar sobre CÓMO se
 * construye, no sólo sobre lo que devuelve: una inyección SQL no se ve en el
 * valor de retorno.
 */
class WM_ECT_Fake_Wpdb {
    public string $prefix  = 'wp_';
    public string $options = 'wp_options';
    /** @var array<int,string> */
    public array $queries = [];
    /** @var array<int,string> */
    public array $executed_queries = [];
    /** @var array<int,array<string,mixed>> */
    public array $prepared = [];
    public int $sales_to_return = 0;
    /** @var array<int,string> tablas que SHOW TABLES LIKE encuentra */
    public array $tables = ['wp_wc_order_product_lookup', 'wp_wc_order_stats'];

    public function esc_like(string $text): string {
        return addcslashes($text, '_%\\');
    }

    public function prepare(string $sql, ...$args): string {
        $this->prepared[] = ['sql' => $sql, 'args' => $args];
        $out = $sql;
        foreach ($args as $a) {
            $rep = is_int($a) ? (string) $a : "'" . addslashes((string) $a) . "'";
            $out = preg_replace('/%[ds]/', $rep, $out, 1) ?? $out;
        }
        return $out;
    }

    public function get_var(string $sql) {
        $this->queries[] = $sql;
        // SHOW TABLES LIKE devuelve el nombre de la tabla si existe y null si no, como MySQL.
        if (preg_match("/^SHOW TABLES LIKE '(.*)'$/", $sql, $m) === 1) {
            $name = str_replace(['\\_', '\\%'], ['_', '%'], stripslashes($m[1]));
            return in_array($name, $this->tables, true) ? $name : null;
        }
        return $this->sales_to_return;
    }

    public function query(string $sql): int {
        $this->executed_queries[] = $sql;
        return 1;
    }
}

$GLOBALS['wpdb'] = new WM_ECT_Fake_Wpdb();

require_once __DIR__ . '/../includes/class-stock-verifier.php';
require_once __DIR__ . '/../includes/class-frontend.php';

use WMEthicalConversionTriggers\Frontend;
use WMEthicalConversionTriggers\Stock_Verifier;

// ---------------------------------------------------------------------------
// Arnés
// ---------------------------------------------------------------------------

$passed = 0;
$failed = 0;

function t(bool $cond, string $name, string $detail = ''): void {
    global $passed, $failed;
    if ($cond) {
        echo " [PASS] {$name}\n";
        $passed++;
    } else {
        echo " [FAIL] {$name}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
        $failed++;
    }
}

/** Renderiza el trigger capturando la salida, con el producto dado. */
function render_for(WC_Product $product, int $sales): string {
    global $wpdb;
    $GLOBALS['wm_ect_transients'] = [];
    $wpdb->sales_to_return = $sales;
    $GLOBALS['product'] = $product;

    $ref = new ReflectionClass(\WMEthicalConversionTriggers\Frontend::class);
    $fe  = $ref->newInstanceWithoutConstructor();  // el ctor engancha hooks
    ob_start();
    $fe->render_ethical_trigger();
    return (string) ob_get_clean();
}

echo "==============================================================\n";
echo " WM Ethical Conversion Triggers — Test Suite\n";
echo "==============================================================\n\n";

// --- Cabecera y versión sincronizada ---------------------------------------

$main_src = (string) file_get_contents(__DIR__ . '/../wm-ethical-conversion-triggers.php');
t(
    str_contains($main_src, 'Version:           1.2.0') && str_contains($main_src, "define( 'WM_ECT_VERSION', '1.2.0' );"),
    'Cabecera y constante WM_ECT_VERSION sincronizadas en 1.2.0'
);

// --- UWG §5: nada de escasez inventada -------------------------------------

t(
    null === Stock_Verifier::get_verified_stock(new WC_Product(1, false, 99)),
    'Sin gestión de stock no se expone cantidad (UWG: no inventar escasez)'
);

t(
    3 === Stock_Verifier::get_verified_stock(new WC_Product(2, true, 3)),
    'Con stock gestionado se devuelve la cantidad real'
);

// --- Ventas verificadas -----------------------------------------------------

$GLOBALS['wpdb']->sales_to_return = 12;
$GLOBALS['wm_ect_transients'] = [];
t(
    12 === Stock_Verifier::get_verified_sales(101),
    'Las ventas verificadas salen de la tabla de pedidos'
);

t(
    isset($GLOBALS['wm_ect_transients']['wm_ect_sales_101']),
    'El resultado se cachea en un transient por producto'
);

$GLOBALS['wpdb']->sales_to_return = 999;   // la BD cambia...
t(
    12 === Stock_Verifier::get_verified_sales(101),
    '...pero el transient cacheado gana, sin volver a consultar'
);

Stock_Verifier::clear_product_transients(101);
t(
    !isset($GLOBALS['wm_ect_transients']['wm_ect_sales_101']),
    'clear_product_transients invalida la caché del producto'
);

// --- SQL: la consulta va preparada y cuenta pedidos pagados ----------------

$prep = $GLOBALS['wpdb']->prepared;
$last = end($prep);
$sql  = $last !== false ? preg_replace('/\s+/', ' ', (string) $last['sql']) : '';
t(
    str_contains($sql, '%d') && str_contains($sql, '%s'),
    'La consulta de ventas usa marcadores de $wpdb->prepare, no concatenación'
);
// Sin WooCommerce cargado la lista de estados pagados cae en processing y completed: 1 id + 2 estados + 1 fecha.
t(
    $last !== false && count($last['args']) === 4 && ['wc-processing', 'wc-completed'] === array_slice($last['args'], 1, 2),
    'prepare() recibe el producto, los estados pagados con prefijo wc- y la fecha',
    var_export($last['args'] ?? null, true)
);
// Hasta el 2026-09-15 la consulta era SUM(product_qty) sobre todos los pedidos (pendientes, fallidos y
// cancelados incluidos) y el texto la llamaba "N personas": un pedido de 3 unidades eran "3 personas".
t(
    str_contains($sql, 'COUNT(DISTINCT lookup.order_id)') && !str_contains($sql, 'SUM(product_qty)')
        && str_contains($sql, 'wc_order_stats') && str_contains($sql, 'stats.parent_id = 0') && str_contains($sql, 'stats.status IN'),
    'Cuenta pedidos distintos con estado pagado, sin reembolsos, no unidades de cualquier pedido',
    $sql
);

// --- Render: qué se enseña y qué NO ----------------------------------------

$html = render_for(new WC_Product(7, true, 3), 20);
t(
    str_contains($html, '20 Bestellungen dieses Artikels in den letzten 7 Tagen.'),
    'Con pedidos reales se muestra el número de pedidos, no "personas"'
);
t(
    str_contains($html, 'Nur noch 3 Stück auf Lager.'),
    'Con stock real bajo (<=5) se muestra la escasez'
);
$html_one = render_for(new WC_Product(71, false, null), 1);
t(
    str_contains($html_one, '1 Bestellung dieses Artikels') && !str_contains($html_one, '1 Bestellungen'),
    'Un pedido usa el singular'
);
t(
    str_contains($html, 'aria-live="polite"') && str_contains($html, 'role="status"'),
    'El contenedor se anuncia como live-region (BFSG 2025)'
);

$html = render_for(new WC_Product(8, true, 50), 20);
t(
    !str_contains($html, 'Nur noch'),
    'Con stock alto NO se muestra escasez'
);

$html = render_for(new WC_Product(9, false, null), 0);
t(
    trim($html) === '',
    'Sin ventas y sin stock gestionado no se emite nada'
);

// Este caso es el que estaba roto: 0 ventas + stock gestionado alto dejaba
// pasar la guarda y emitía el contenedor VACÍO. Una live-region vacía se
// anuncia igual a un lector de pantalla.
$html = render_for(new WC_Product(10, true, 50), 0);
t(
    trim($html) === '',
    'Sin ventas y con stock alto NO se emite una live-region vacía',
    'salida: ' . var_export($html, true)
);

// --- Filtro wm_ethical_triggers_config respeta los invariantes UWG §5 -------

$GLOBALS['wm_ect_filters']['wm_ethical_triggers_config'] = [
    static fn(array $cfg): array => array_merge($cfg, ['low_stock_threshold' => 8]),
];
$html_filtered = render_for(new WC_Product(11, true, 7), 0);
t(
    str_contains($html_filtered, 'Nur noch 7 Stück auf Lager.'),
    'El filtro wm_ethical_triggers_config permite ajustar low_stock_threshold sobre stock gestionado'
);
$html_unmanaged_filtered = render_for(new WC_Product(12, false, 7), 0);
t(
    trim($html_unmanaged_filtered) === '',
    'Incluso con low_stock_threshold ampliado, sin stock gestionado sigue sin emitirse nada'
);
$GLOBALS['wm_ect_filters'] = [];

// --- Sin WooCommerce: nada fatalea ----------------------------------------
// Medido en una instalación local de WordPress (WordPress 7.1) el 2026-09-14: con el plugin activo y
// WooCommerce ausente la portada respondía 500, porque enqueue_scripts()
// llamaba a is_product() sin comprobar que exista. Esta suite no define
// is_product ni la clase WooCommerce hasta más abajo, reproduciendo ese sitio.

$GLOBALS['wm_ect_enqueued'] = [];
function wp_enqueue_style(string $handle, ...$rest): void { unset($rest); $GLOBALS['wm_ect_enqueued'][] = $handle; }
function wp_enqueue_script(string $handle, ...$rest): void { unset($rest); $GLOBALS['wm_ect_enqueued'][] = $handle; }
function is_admin(): bool { return false; }
function wp_doing_ajax(): bool { return false; }

$fatal = null;
try {
    $ref = new ReflectionClass(\WMEthicalConversionTriggers\Frontend::class);
    $ref->newInstanceWithoutConstructor()->enqueue_scripts();
} catch (\Throwable $e) {
    $fatal = get_class($e) . ': ' . $e->getMessage();
}
t(null === $fatal, 'enqueue_scripts() no fatalea sin WooCommerce (is_product ausente)', (string) $fatal);
t([] === $GLOBALS['wm_ect_enqueued'], 'Sin WooCommerce no se encola ningún asset');

require_once __DIR__ . '/../includes/class-plugin.php';

t(
    false === \WMEthicalConversionTriggers\Plugin::woocommerce_active(),
    'Plugin::woocommerce_active() es false cuando la clase WooCommerce no existe'
);

$GLOBALS['wm_ect_actions'] = [];
\WMEthicalConversionTriggers\Plugin::instance();
t(
    isset($GLOBALS['wm_ect_actions']['admin_notices']) && !isset($GLOBALS['wm_ect_actions']['rest_api_init']) && !isset($GLOBALS['wm_ect_actions']['init']),
    'Sin WooCommerce el plugin registra sólo el aviso de administración: ni frontend ni ruta REST',
    'hooks: ' . implode(', ', array_keys($GLOBALS['wm_ect_actions']))
);

// --- WM Suite Hub: el plugin entra en el registro del hub, también sin WooCommerce --
// El hub arma su registro con el filtro wm_register_suite_module al final de plugins_loaded y sólo
// conserva objetos que implementan su WM_Plugin_Module_Interface. Hasta la 1.1.4 el plugin llamaba a
// una función wm_register_suite_module() que el hub no define y nunca aparecía en él (medido el
// 2026-11-20 con el Module_Registry del propio hub: 22 de 23 plugins).

t(
    in_array([\WMEthicalConversionTriggers\Integrations::class, 'register_suite_module'], $GLOBALS['wm_ect_filters']['wm_register_suite_module'] ?? [], true),
    'El filtro wm_register_suite_module queda enganchado también sin WooCommerce'
);
t(
    ['otro-modulo'] === \WMEthicalConversionTriggers\Integrations::register_suite_module(['otro-modulo'])
        && !class_exists('WMEthicalConversionTriggers\Spoke_Adapter', false),
    'Sin el hub instalado el filtro devuelve la lista intacta y no carga el adaptador'
);

require_once __DIR__ . '/stubs/hub-interface.php';
$hub_modules = \WMEthicalConversionTriggers\Integrations::register_suite_module(['otro-modulo']);
$adapter     = $hub_modules[1] ?? null;
// Cada aserción sobre el adaptador comprueba antes que existe: una mutación que lo quite debe dejar
// la suite en rojo, no cortarla con un error fatal antes de la línea de resultados.
$has_adapter = $adapter instanceof \ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface;
t(
    $has_adapter && 2 === count($hub_modules) && 'otro-modulo' === $hub_modules[0]
        && 'wm-ethical-conversion-triggers' === $adapter->get_module_slug(),
    'Con el hub instalado el filtro añade un WM_Plugin_Module_Interface con el slug del plugin y conserva los demás'
);
t(
    $has_adapter && 'ERROR' === $adapter->get_health_status() && null === $adapter->get_settings_url()
        && 'NO' === ($adapter->get_telemetry_metrics()['woocommerce_active'] ?? ''),
    'Sin WooCommerce el hub lo ve en ERROR y sin enlace a una página de ajustes que no existe'
);

ob_start();
\WMEthicalConversionTriggers\Plugin::missing_woocommerce_notice();
$notice = (string) ob_get_clean();
t(
    str_contains($notice, 'notice-error') && str_contains($notice, 'WooCommerce'),
    'El aviso de administración nombra a WooCommerce como requisito'
);

// Declaración condicional: una clase declarada sin condición se registra al
// compilar el fichero y haría verdadera la comprobación anterior.
if (!class_exists('WooCommerce', false)) {
    class WooCommerce {}
}
t(
    true === \WMEthicalConversionTriggers\Plugin::woocommerce_active(),
    '...y true en cuanto la clase WooCommerce existe'
);

// --- Con WooCommerce activo: acción wm_ethical_triggers_ready y puentes -----

function wc_get_product(int $id): ?WC_Product {
    return $GLOBALS['wm_ect_products'][$id] ?? null;
}

$GLOBALS['wm_ect_fired_actions'] = [];
\WMEthicalConversionTriggers\Plugin::instance()->init();
t(
    isset($GLOBALS['wm_ect_fired_actions']['wm_ethical_triggers_ready'])
        && count($GLOBALS['wm_ect_fired_actions']['wm_ethical_triggers_ready']) === 1,
    'Plugin::init() dispara la acción wm_ethical_triggers_ready'
);

// --- Integraciones: WM Suite Hub, WP 6.5+ Block Bindings y GenerateBlocks 2.x --

t(
    $has_adapter && 'HEALTHY' === $adapter->get_health_status()
        && 'https://example.test/wp-admin/options-general.php?page=wm-ethical-conversion-triggers' === $adapter->get_settings_url(),
    'Con WooCommerce y las dos tablas de analítica el hub lo ve HEALTHY y enlaza Ajustes > Ethical Triggers'
);

$GLOBALS['wpdb']->tables = ['wp_wc_order_product_lookup'];   // falta wc_order_stats
t(
    $has_adapter && 'DEGRADED' === $adapter->get_health_status()
        && 'NO' === ($adapter->get_telemetry_metrics()['analytics_tables'] ?? ''),
    'Sin la tabla wc_order_stats el hub lo ve DEGRADED: el aviso de pedidos no puede salir'
);
$GLOBALS['wpdb']->tables = ['wp_wc_order_product_lookup', 'wp_wc_order_stats'];

$sbom = $has_adapter ? $adapter->get_sbom_data() : [];
t(
    'GPL-2.0-or-later' === ($sbom['licenses'][0]['license']['id'] ?? '')
        && 'pkg:wordpress/wm-ethical-conversion-triggers@' . WM_ECT_VERSION === ($sbom['bom-ref'] ?? '')
        && hash_file('sha256', WM_ECT_FILE) === ($sbom['hashes'][0]['content'] ?? ''),
    'El SBOM del módulo declara GPL-2.0-or-later, el purl de la versión y el SHA-256 del fichero principal'
);

$self_test = $has_adapter ? $adapter->execute_action('self_test') : [];
$unknown   = $has_adapter ? $adapter->execute_action('borrar_todo') : [];
t(
    true === ($self_test['success'] ?? null) && false === ($unknown['success'] ?? null),
    'self_test pasa con WooCommerce, las tablas, la regla de stock y el webhook; una acción desconocida se rechaza',
    var_export($self_test, true)
);

\WMEthicalConversionTriggers\Integrations::register_block_bindings();
t(
    isset($GLOBALS['wm_ect_block_bindings']['wm-ethical-triggers/metrics']),
    'Integrations registra la fuente Block Bindings wm-ethical-triggers/metrics'
);

$GLOBALS['wm_ect_products'][501] = new WC_Product(501, true, 2);
$GLOBALS['wm_ect_products'][502] = new WC_Product(502, false, null);
$GLOBALS['wm_ect_transients']    = [];
$GLOBALS['wpdb']->sales_to_return = 4;

$binding_val = \WMEthicalConversionTriggers\Integrations::get_block_binding_value(['product_id' => 501, 'key' => 'summary']);
t(
    is_string($binding_val)
        && str_contains($binding_val, '4 Bestellungen dieses Artikels in den letzten 7 Tagen.')
        && str_contains($binding_val, 'Nur noch 2 Stück auf Lager.'),
    'Block Bindings devuelve el resumen verificado para pedidos reales y stock bajo gestionado'
);

$GLOBALS['wm_ect_transients']    = [];
$GLOBALS['wpdb']->sales_to_return = 0;
t(
    null === \WMEthicalConversionTriggers\Integrations::get_block_binding_value(['product_id' => 502, 'key' => 'summary']),
    'Block Bindings devuelve null cuando no hay pedidos ni stock gestionado'
);

\WMEthicalConversionTriggers\Integrations::register_generateblocks_tag();
t(
    isset($GLOBALS['wm_ect_gb_tags']['wm_ethical_trigger']),
    'Integrations registra la etiqueta dinámica wm_ethical_trigger de GenerateBlocks 2.x'
);

$GLOBALS['wm_ect_transients']    = [];
$GLOBALS['wpdb']->sales_to_return = 3;
$gb_out = \WMEthicalConversionTriggers\Integrations::render_generateblocks_tag(['id' => 501, 'field' => 'stock_notice']);
t(
    'Nur noch 2 Stück auf Lager.' === $gb_out,
    'GenerateBlocks render_generateblocks_tag devuelve la frase verificada solicitada'
);

// --- Settings: capacidad manage_options y sanitización de checkbox ----------

t(
    '1' === \WMEthicalConversionTriggers\Settings::sanitize_checkbox('1')
        && '0' === \WMEthicalConversionTriggers\Settings::sanitize_checkbox('0')
        && '0' === \WMEthicalConversionTriggers\Settings::sanitize_checkbox(''),
    'Settings::sanitize_checkbox normaliza estrictamente a "1" o "0"'
);

$GLOBALS['wm_ect_can_manage_options'] = false;
ob_start();
\WMEthicalConversionTriggers\Settings::render_page();
$denied_page = (string) ob_get_clean();
t(
    '' === $denied_page,
    'Settings::render_page bloquea la salida sin capacidad manage_options'
);

$GLOBALS['wm_ect_can_manage_options'] = true;
ob_start();
\WMEthicalConversionTriggers\Settings::render_page();
$allowed_page = (string) ob_get_clean();
t(
    str_contains($allowed_page, 'label for="wm_ethical_triggers_delete_data_on_uninstall"')
        && str_contains($allowed_page, 'action="options.php"'),
    'Settings::render_page renderiza el formulario accesible con label explícito cuando el usuario tiene manage_options'
);

// --- Webhook HMAC-SHA256 en tiempo constante --------------------------------

$GLOBALS['wm_ect_options'] = [];
$missing_secret = \WMEthicalConversionTriggers\Webhook_Validator::verify_signature(new WP_REST_Request('{}'));
t(
    $missing_secret instanceof WP_Error && 'missing_secret' === $missing_secret->get_error_code(),
    'Webhook rechaza peticiones cuando no hay secreto configurado'
);

$GLOBALS['wm_ect_options']['wm_ect_webhook_secret'] = 'test-shared-secret-2026';
t(
    $has_adapter && 'YES' === ($adapter->get_telemetry_metrics()['webhook_secret_configured'] ?? ''),
    'La telemetría del hub ve el secreto del webhook en cuanto está configurado'
);
$payload = '{"product_id":501,"stock_quantity":4}';
$bad_sig = \WMEthicalConversionTriggers\Webhook_Validator::verify_signature(
    new WP_REST_Request($payload, ['x-wc-webhook-signature' => 'invalid-hmac'])
);
t(
    $bad_sig instanceof WP_Error && 'invalid_signature' === $bad_sig->get_error_code(),
    'Webhook rechaza una firma HMAC inválida con 403'
);

$valid_hmac = base64_encode(hash_hmac('sha256', $payload, 'test-shared-secret-2026', true));
$good_sig   = \WMEthicalConversionTriggers\Webhook_Validator::verify_signature(
    new WP_REST_Request($payload, ['x-wc-webhook-signature' => $valid_hmac])
);
t(
    true === $good_sig,
    'Webhook acepta una firma HMAC-SHA256 válida'
);

$GLOBALS['wm_ect_transients']['wm_ect_sales_501'] = 9;
$wh_res = \WMEthicalConversionTriggers\Webhook_Validator::handle_webhook(
    new WP_REST_Request($payload, ['x-wc-webhook-signature' => $valid_hmac], ['product_id' => 501, 'stock_quantity' => 4])
);
t(
    is_array($wh_res) && true === ($wh_res['success'] ?? false)
        && 4 === $GLOBALS['wm_ect_products'][501]->get_stock_quantity()
        && !isset($GLOBALS['wm_ect_transients']['wm_ect_sales_501']),
    'Webhook actualiza el stock gestionado e invalida el transient del producto'
);

// --- Desinstalación opt-in (D-UNINSTALL) ------------------------------------

define('WP_UNINSTALL_PLUGIN', true);

$GLOBALS['wm_ect_options'] = [
    'wm_ect_webhook_secret'                        => 'keep-me-by-default',
    'wm_ethical_triggers_delete_data_on_uninstall' => '0',
];
$GLOBALS['wpdb']->executed_queries = [];
require __DIR__ . '/../uninstall.php';
t(
    isset($GLOBALS['wm_ect_options']['wm_ect_webhook_secret'])
        && [] === $GLOBALS['wpdb']->executed_queries,
    'D-UNINSTALL: con opt-in desactivado ("0") uninstall.php conserva opciones y transients'
);

$GLOBALS['wm_ect_options']['wm_ethical_triggers_delete_data_on_uninstall'] = '1';
require __DIR__ . '/../uninstall.php';
t(
    !isset($GLOBALS['wm_ect_options']['wm_ect_webhook_secret'])
        && !isset($GLOBALS['wm_ect_options']['wm_ethical_triggers_delete_data_on_uninstall'])
        && count($GLOBALS['wpdb']->executed_queries) === 1
        && str_contains(stripslashes($GLOBALS['wpdb']->executed_queries[0]), '_transient_wm_ect_sales_'),
    'D-UNINSTALL: con opt-in explícito ("1") uninstall.php borra opciones y transients del plugin'
);

echo "\n--------------------------------------------------------------\n";
echo " RESULT: {$passed} PASSED | {$failed} FAILED\n";
echo "==============================================================\n";

exit($failed === 0 ? 0 : 1);
