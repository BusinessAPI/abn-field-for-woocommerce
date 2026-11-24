<?php
/**
 * ABN line under the billing address: admin order screen, emails, thank-you page, My Account.
 *
 * @package AbnField
 */

namespace AbnField;

defined( 'ABSPATH' ) || exit;

final class Display {

	public static function register(): void {
		add_filter( 'woocommerce_order_get_formatted_billing_address', array( self::class, 'order_address' ), 10, 3 );
		add_action( 'woocommerce_my_account_after_my_address', array( self::class, 'account_address' ) );
	}

	/**
	 * Appended to the formatted address rather than added to the address format, which the
	 * Checkout block also renders and would show as a literal placeholder.
	 */
	public static function order_address( $address, $raw_address = array(), $order = null ) {
		if ( ! is_string( $address ) || '' === $address || ! $order instanceof \WC_Order ) {
			return $address;
		}
		$abn = Plugin::order_abn( $order );
		return '' === $abn ? $address : $address . '<br/>' . esc_html( self::line( $abn ) );
	}

	/** Classic mode only: with the Checkout block WooCommerce prints the field itself. */
	public static function account_address( $address_type ): void {
		if ( 'billing' !== $address_type || Plugin::block_mode() || ! is_user_logged_in() ) {
			return;
		}
		$customer = new \WC_Customer( get_current_user_id() );
		$abn      = Plugin::COUNTRY === $customer->get_billing_country() ? (string) $customer->get_meta( Plugin::CUSTOMER_META ) : '';
		if ( '' !== $abn ) {
			printf( '<br><strong>%s</strong>: %s', esc_html( Settings::label() ), esc_html( $abn ) );
		}
	}

	public static function line( string $abn ): string {
		return Settings::label() . ': ' . $abn;
	}
}
