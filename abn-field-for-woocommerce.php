<?php
/**
 * Plugin Name:          ABN Field for WooCommerce
 * Description:          Adds an Australian Business Number field to the WooCommerce billing address, with checksum validation.
 * Version:              1.0.0
 * Requires at least:    6.5
 * Requires PHP:         8.1
 * Requires Plugins:     woocommerce
 * WC requires at least: 9.9
 * WC tested up to:      11.3
 * Author:               Business API
 * Author URI:           https://businessapi.com.au
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          abn-field-for-woocommerce
 *
 * @package AbnField
 */

defined( 'ABSPATH' ) || exit;

define( 'ABNF_VERSION', '1.0.0' );
define( 'ABNF_FILE', __FILE__ );
define( 'ABNF_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABNF_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'AbnField\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$path = ABNF_DIR . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action( 'plugins_loaded', array( AbnField\Plugin::class, 'boot' ) );
