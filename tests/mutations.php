<?php
/**
 * Mutations for tests/run-mutations.sh: each entry undoes one correction with literal replacements
 * [ file, search, replace ]; the standalone suite must turn red for every one of them.
 *
 * @package WMEthicalConversionTriggers
 */

declare(strict_types=1);

return [
	[
		'sales count back to the sum of units',
		[
			[ 'includes/class-stock-verifier.php', 'SELECT COUNT(DISTINCT lookup.order_id)', 'SELECT SUM(product_qty)' ],
		],
	],
	[
		'refund rows counted as orders again',
		[
			[ 'includes/class-stock-verifier.php', 'AND stats.parent_id = 0', 'AND 1 = 1' ],
		],
	],
	[
		'order statuses without the wc- prefix the analytics tables store',
		[
			[ 'includes/class-stock-verifier.php', "return 'wc-' . preg_replace(", "return '' . preg_replace(" ],
		],
	],
	[
		'the notice calls orders people again',
		[
			[ 'includes/class-frontend.php', "_n( '%d Bestellung dieses Artikels in den letzten 7 Tagen.', '%d Bestellungen dieses Artikels in den letzten 7 Tagen.'", "_n( '%d personas han comprado este artículo en los últimos 7 días.', '%d personas han comprado este artículo en los últimos 7 días.'" ],
		],
	],
	[
		'one order printed with the plural',
		[
			[ 'includes/class-frontend.php', "_n( '%d Bestellung dieses Artikels in den letzten 7 Tagen.',", "_n( '%d Bestellungen dieses Artikels in den letzten 7 Tagen.'," ],
		],
	],
	[
		'uninstall deletes data when opt-in is disabled (D-UNINSTALL violation)',
		[
			[ 'uninstall.php', 'if ( ! $wm_ect_delete_data ) {', 'if ( $wm_ect_delete_data ) {' ],
		],
	],
	[
		'settings screen rendered without manage_options capability check',
		[
			[ 'includes/class-settings.php', "if ( ! current_user_can( 'manage_options' ) ) {", 'if ( false ) {' ],
		],
	],
	[
		'wm_ethical_triggers_config filter ignored during notice calculation',
		[
			[ 'includes/class-frontend.php', "apply_filters( 'wm_ethical_triggers_config', \$default_config, \$product_id )", '$default_config' ],
		],
	],
	[
		'WP 6.5 Block Bindings source slug broken',
		[
			[ 'includes/class-integrations.php', "'wm-ethical-triggers/metrics',\n            array(", "'wm-ethical-triggers/broken',\n            array(" ],
		],
	],
	[
		'GenerateBlocks dynamic tag slug broken',
		[
			[ 'includes/class-integrations.php', "'tag'         => 'wm_ethical_trigger',", "'tag'         => 'wm_ethical_broken'," ],
		],
	],
	[
		'webhook accepts invalid HMAC signatures',
		[
			[ 'includes/class-webhook-validator.php', 'if ( ! hash_equals( $expected_signature, $signature ) ) {', 'if ( false ) {' ],
		],
	],
	[
		'the hub filter is not hooked (the plugin vanishes from the hub, as up to 1.1.4)',
		[
			[ 'includes/class-plugin.php', "add_filter( 'wm_register_suite_module', array( Integrations::class, 'register_suite_module' ) );", '// hub filter not hooked' ],
		],
	],
	[
		'the hub filter returns the list without the module',
		[
			[ 'includes/class-integrations.php', '$modules[] = new Spoke_Adapter();', '$modules = array_values( $modules );' ],
		],
	],
	[
		'the hub sees the plugin healthy without WooCommerce',
		[
			[ 'includes/class-spoke-adapter.php', "return 'ERROR';", "return 'HEALTHY';" ],
		],
	],
	// The two version mutations name no version, so a bump cannot leave them searching for one that is gone.
	[
		'the header runs ahead of the version constant',
		[
			[ 'wm-ethical-conversion-triggers.php', ' * Version:           ', ' * Version:           9' ],
		],
	],
	[
		'the version constant falls behind the header',
		[
			[ 'wm-ethical-conversion-triggers.php', "define( 'WM_ECT_VERSION', '", "define( 'WM_ECT_VERSION', '0" ],
		],
	],
];
