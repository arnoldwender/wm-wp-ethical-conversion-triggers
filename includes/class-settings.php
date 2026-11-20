<?php
/**
 * Admin settings for explicit opt-in data cleanup on uninstall (D-UNINSTALL).
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace WMEthicalConversionTriggers;

defined( 'ABSPATH' ) || exit;

class Settings {

    public const OPTION_DELETE_ON_UNINSTALL = 'wm_ethical_triggers_delete_data_on_uninstall';
    public const SETTINGS_GROUP             = 'wm_ethical_triggers_settings';
    public const PAGE_SLUG                  = 'wm-ethical-conversion-triggers';

    public static function register_hooks(): void {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
    }

    public static function register_settings(): void {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_DELETE_ON_UNINSTALL,
            array(
                'type'              => 'string',
                'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
                'default'           => '0',
            )
        );

        add_settings_section(
            'wm_ect_uninstall_section',
            __( 'Data & Uninstall', 'wm-ethical-conversion-triggers' ),
            '__return_null',
            self::PAGE_SLUG
        );

        add_settings_field(
            self::OPTION_DELETE_ON_UNINSTALL,
            __( 'Delete data on uninstall', 'wm-ethical-conversion-triggers' ),
            array( __CLASS__, 'render_delete_field' ),
            self::PAGE_SLUG,
            'wm_ect_uninstall_section',
            array(
                'label_for' => self::OPTION_DELETE_ON_UNINSTALL,
            )
        );
    }

    /**
     * Normalises checkbox input to `'1'` or `'0'`.
     *
     * @param mixed $value Raw option value.
     */
    public static function sanitize_checkbox( $value ): string {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0';
    }

    public static function register_menu(): void {
        add_options_page(
            __( 'WM Ethical Conversion Triggers', 'wm-ethical-conversion-triggers' ),
            __( 'Ethical Triggers', 'wm-ethical-conversion-triggers' ),
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render_page' )
        );
    }

    public static function render_delete_field(): void {
        $enabled = filter_var(
            get_option( self::OPTION_DELETE_ON_UNINSTALL, '0' ),
            FILTER_VALIDATE_BOOLEAN
        );
        echo '<label for="' . esc_attr( self::OPTION_DELETE_ON_UNINSTALL ) . '">';
        echo '<input type="checkbox" id="' . esc_attr( self::OPTION_DELETE_ON_UNINSTALL ) . '" name="' . esc_attr( self::OPTION_DELETE_ON_UNINSTALL ) . '" value="1"' . ( $enabled ? ' checked="checked"' : '' ) . ' /> ';
        echo esc_html__( 'Delete plugin options and cached order-count transients when the plugin is uninstalled.', 'wm-ethical-conversion-triggers' );
        echo '</label>';
        echo '<p class="description">'
            . esc_html__( 'Disabled by default so reinstalling or updating manually keeps existing settings.', 'wm-ethical-conversion-triggers' )
            . '</p>';
    }

    public static function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<section class="wrap" id="wm-ect-settings">';
        echo '<h1>' . esc_html__( 'WM Ethical Conversion Triggers', 'wm-ethical-conversion-triggers' ) . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( self::SETTINGS_GROUP );
        do_settings_sections( self::PAGE_SLUG );
        submit_button();
        echo '</form>';
        echo '</section>';
    }
}
