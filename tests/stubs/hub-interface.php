<?php
/**
 * Stub of the WM Suite Hub module contract for the standalone suite (the real one lives in wm-suite-hub,
 * src/Contracts/WM_Plugin_Module_Interface.php).
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace ArnoldWender\SuiteHub\Contracts;

interface WM_Plugin_Module_Interface {
    public function get_module_slug(): string;
    public function get_name(): string;
    public function get_version(): string;
    public function get_description(): string;
    public function get_health_status(): string;
    public function get_sbom_data(): array;
    public function get_dependencies(): array;
    public function get_settings_url(): ?string;
    public function get_telemetry_metrics(): array;
    public function execute_action( string $action, array $params = [] ): array;
}
