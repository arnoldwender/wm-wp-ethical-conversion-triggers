<?php
/**
 * Ecosystem bridges: the WM Suite Hub filter, WP 6.5+ Block Bindings,
 * and the GenerateBlocks 2.x Dynamic Tag.
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

namespace WMEthicalConversionTriggers;

defined( 'ABSPATH' ) || exit;

class Integrations {

    public const BLOCK_BINDING_SOURCE = 'wm-ethical-triggers/metrics';
    public const GB_DYNAMIC_TAG       = 'wm_ethical_trigger';

    public static function register_hooks(): void {
        add_action( 'init', array( __CLASS__, 'register_block_bindings' ), 10 );
        add_action( 'init', array( __CLASS__, 'register_generateblocks_tag' ), 20 );
        add_filter( 'generateblocks_dynamic_tag_replacement', array( __CLASS__, 'filter_generateblocks_output' ), 10, 2 );
    }

    /**
     * Adds this plugin's module to the list WM Suite Hub collects (filter `wm_register_suite_module`).
     *
     * The hub builds its registry from this filter late in plugins_loaded (priority 99) and keeps only objects
     * that implement its WM_Plugin_Module_Interface. Plugin::init_hooks() hooks it before the WooCommerce check,
     * so without WooCommerce the hub lists the plugin as ERROR instead of not at all.
     *
     * @param mixed $modules Modules added so far by the other plugins of the family.
     * @return array<int|string, mixed>
     */
    public static function register_suite_module( $modules ): array {
        $modules = is_array( $modules ) ? $modules : array();
        if ( ! interface_exists( 'ArnoldWender\SuiteHub\Contracts\WM_Plugin_Module_Interface' ) ) {
            return $modules;
        }

        require_once WM_ECT_PATH . 'includes/class-spoke-adapter.php';
        if ( class_exists( __NAMESPACE__ . '\\Spoke_Adapter', false ) ) {
            $modules[] = new Spoke_Adapter();
        }
        return $modules;
    }

    /**
     * Registers the WordPress 6.5+ Block Bindings source `wm-ethical-triggers/metrics`.
     */
    public static function register_block_bindings(): void {
        if ( ! function_exists( 'register_block_bindings_source' ) ) {
            return;
        }

        register_block_bindings_source(
            'wm-ethical-triggers/metrics',
            array(
                'label'              => __( 'WM Ethical Conversion Triggers', 'wm-ethical-conversion-triggers' ),
                'get_value_callback' => array( __CLASS__, 'get_block_binding_value' ),
                'uses_context'       => array( 'postId', 'postType' ),
            )
        );
    }

    /**
     * Resolves a verified notice string for WP 6.5+ Block Bindings.
     *
     * @param array<string, mixed> $source_args    Binding source arguments (`key`, optional `product_id`).
     * @param mixed                $block_instance Block instance (`WP_Block` or test double).
     * @param string               $attribute_name Bound block attribute name.
     */
    public static function get_block_binding_value( array $source_args, $block_instance = null, string $attribute_name = 'content' ): ?string {
        unset( $attribute_name );

        $product_id = 0;
        if ( isset( $source_args['product_id'] ) ) {
            $product_id = (int) $source_args['product_id'];
        } elseif ( is_object( $block_instance ) && isset( $block_instance->context['postId'] ) ) {
            $product_id = (int) $block_instance->context['postId'];
        }

        $product = self::resolve_product( $product_id );
        if ( null === $product ) {
            return null;
        }

        $key  = isset( $source_args['key'] ) && is_string( $source_args['key'] ) ? $source_args['key'] : 'summary';
        $text = Frontend::format_notice_text( $product, $key );

        return '' !== $text ? $text : null;
    }

    /**
     * Registers the GenerateBlocks 2.x Dynamic Tag `{{wm_ethical_trigger}}`.
     */
    public static function register_generateblocks_tag(): void {
        if ( ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
            return;
        }

        new \GenerateBlocks_Register_Dynamic_Tag(
            array(
                'title'       => __( 'Ethical Conversion Trigger', 'wm-ethical-conversion-triggers' ),
                'tag'         => 'wm_ethical_trigger',
                'type'        => 'woocommerce',
                'supports'    => array( 'source' ),
                'description' => __( 'Verified 7-day paid orders and managed low-stock notice.', 'wm-ethical-conversion-triggers' ),
                'options'     => array(
                    'field' => array(
                        'type'    => 'select',
                        'label'   => __( 'Notice field', 'wm-ethical-conversion-triggers' ),
                        'default' => 'summary',
                        'options' => array(
                            array(
                                'value' => 'summary',
                                'label' => __( 'Combined verified notices', 'wm-ethical-conversion-triggers' ),
                            ),
                            array(
                                'value' => 'sales_notice',
                                'label' => __( 'Verified 7-day orders only', 'wm-ethical-conversion-triggers' ),
                            ),
                            array(
                                'value' => 'stock_notice',
                                'label' => __( 'Verified low stock only', 'wm-ethical-conversion-triggers' ),
                            ),
                        ),
                    ),
                ),
                'return'      => array( __CLASS__, 'render_generateblocks_tag' ),
            )
        );
    }

    /**
     * Renders `{{wm_ethical_trigger}}` for GenerateBlocks 2.x.
     *
     * @param array<string, mixed> $options  Tag options (`id`, `field`).
     * @param mixed                $block    Block array/context.
     * @param mixed                $instance Dynamic tag instance.
     */
    public static function render_generateblocks_tag( array $options = array(), $block = array(), $instance = null ): string {
        unset( $block, $instance );

        $product_id = isset( $options['id'] ) ? (int) $options['id'] : 0;
        $product    = self::resolve_product( $product_id );
        if ( null === $product ) {
            return '';
        }

        $field = isset( $options['field'] ) && is_string( $options['field'] ) ? $options['field'] : 'summary';
        return esc_html( Frontend::format_notice_text( $product, $field ) );
    }

    /**
     * Resolves `wm_ethical_trigger` when filtered via `generateblocks_dynamic_tag_replacement` without double-escaping.
     *
     * @param mixed $output   Rendered dynamic tag replacement/output.
     * @param mixed $tag_data Dynamic tag metadata.
     * @param mixed $context  Block context or raw scalar output.
     * @return mixed
     */
    public static function filter_generateblocks_output( mixed $output, mixed $tag_data = array(), mixed $context = array() ): mixed {
        unset( $context );
        if ( is_array( $tag_data ) && ( $tag_data['tag'] ?? '' ) === self::GB_DYNAMIC_TAG && ( '' === $output || null === $output ) ) {
            $options = isset( $tag_data['options'] ) && is_array( $tag_data['options'] ) ? $tag_data['options'] : array();
            return self::render_generateblocks_tag( $options );
        }
        return $output;
    }

    /**
     * Resolves a WC_Product from an explicit ID, current post ID, or global `$product`.
     *
     * @return mixed|null WC_Product instance or null.
     */
    private static function resolve_product( int $product_id ) {
        if ( $product_id > 0 && function_exists( 'wc_get_product' ) ) {
            $resolved = wc_get_product( $product_id );
            if ( is_object( $resolved ) && is_a( $resolved, 'WC_Product' ) ) {
                return $resolved;
            }
        }

        if ( isset( $GLOBALS['product'] ) && is_object( $GLOBALS['product'] ) && is_a( $GLOBALS['product'], 'WC_Product' ) ) {
            if ( 0 === $product_id || (int) $GLOBALS['product']->get_id() === $product_id ) {
                return $GLOBALS['product'];
            }
        }

        if ( 0 === $product_id && function_exists( 'get_the_ID' ) && function_exists( 'wc_get_product' ) ) {
            $current_id = (int) get_the_ID();
            if ( $current_id > 0 ) {
                $resolved = wc_get_product( $current_id );
                if ( is_object( $resolved ) && is_a( $resolved, 'WC_Product' ) ) {
                    return $resolved;
                }
            }
        }

        return null;
    }
}
