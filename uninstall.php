<?php
/**
 * Uninstall routine for WM Ethical Conversion Triggers.
 *
 * Preserves plugin options and cached order-count transients by default
 * (D-UNINSTALL) unless the site administrator explicitly enabled data deletion
 * via `wm_ethical_triggers_delete_data_on_uninstall`.
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$wm_ect_delete_data = filter_var(
    get_option( 'wm_ethical_triggers_delete_data_on_uninstall', '0' ),
    FILTER_VALIDATE_BOOLEAN
);

if ( ! $wm_ect_delete_data ) {
    return;
}

delete_option( 'wm_ect_webhook_secret' );
delete_option( 'wm_ethical_triggers_delete_data_on_uninstall' );

global $wpdb;

if ( isset( $wpdb ) && is_object( $wpdb ) && isset( $wpdb->options ) && method_exists( $wpdb, 'query' ) && method_exists( $wpdb, 'prepare' ) ) {
    $wm_ect_like_value = method_exists( $wpdb, 'esc_like' )
        ? $wpdb->esc_like( '_transient_wm_ect_sales_' ) . '%'
        : addcslashes( '_transient_wm_ect_sales_', '_%\\' ) . '%';
    $wm_ect_like_timeout = method_exists( $wpdb, 'esc_like' )
        ? $wpdb->esc_like( '_transient_timeout_wm_ect_sales_' ) . '%'
        : addcslashes( '_transient_timeout_wm_ect_sales_', '_%\\' ) . '%';

    $wpdb->query(
        (string) $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wm_ect_like_value,
            $wm_ect_like_timeout
        )
    );
}
