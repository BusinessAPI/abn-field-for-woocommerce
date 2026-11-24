<?php
/**
 * Bootstrap and shared helpers.
 *
 * @package AbnField
 */

namespace AbnField;

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public const ORDER_META    = '_billing_abn';
	public const CUSTOMER_META = 'billing_abn';
	public const COUNTRY       = 'AU';

	private static ?bool $block_mode = null;

	public static function boot(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( self::class, 'missing_woocommerce_notice' ) );
			return;
		}
		Settings::register();
		add_filter( 'plugin_action_links_' . plugin_basename( ABNF_FILE ), array( self::class, 'action_links' ) );
		ClassicField::register();
		BlockField::register();
		OrderAdmin::register();
		Display::register();
	}

	/**
	 * Whether the checkout page uses the Checkout block. The block field is registered only then,
	 * so classic shops get no extra field in My Account shipping addresses.
	 */
	public static function block_mode(): bool {
		if ( null === self::$block_mode ) {
			$default = class_exists( CartCheckoutUtils::class ) && CartCheckoutUtils::is_checkout_block_default();
			/**
			 * Use the Checkout block field instead of the classic checkout field.
			 *
			 * @param bool $block_mode True when the WooCommerce checkout page holds the Checkout block.
			 */
			self::$block_mode = (bool) apply_filters( 'abnf_use_block_field', $default );
		}
		return self::$block_mode;
	}

	/** Forget the cached mode, e.g. after the checkout page changed. */
	public static function reset_mode(): void {
		self::$block_mode = null;
	}

	/**
	 * The ABN to save for a billing country: formatted when valid, empty outside Australia.
	 * Invalid input comes back trimmed so validation can reject it.
	 */
	public static function prepare( string $value, string $country ): string {
		if ( self::COUNTRY !== $country ) {
			return '';
		}
		return Abn::format( sanitize_text_field( $value ), Settings::format() );
	}

	/** The order's ABN, formatted, or '' when there is none or the billing country is not Australia. */
	public static function order_abn( \WC_Order $order ): string {
		if ( self::COUNTRY !== $order->get_billing_country() ) {
			return '';
		}
		$value = (string) $order->get_meta( self::ORDER_META );
		if ( '' === $value ) {
			$value = (string) $order->get_meta( BlockField::ORDER_KEY );
		}
		return '' === $value ? '' : Abn::format( $value, Settings::format() );
	}

	/**
	 * @param array<string, string> $links Plugin row links.
	 * @return array<string, string>
	 */
	public static function action_links( array $links ): array {
		$url = admin_url( 'admin.php?page=wc-settings&tab=general#abnf_options-description' );
		return array( 'settings' => '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'abn-field-for-woocommerce' ) . '</a>' ) + $links;
	}

	public static function missing_woocommerce_notice(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html__( 'ABN Field for WooCommerce needs WooCommerce to be installed and active.', 'abn-field-for-woocommerce' )
		);
	}
}
