<?php
/**
 * ABN in the billing section of the admin order screen.
 *
 * @package AbnField
 */

namespace AbnField;

defined( 'ABSPATH' ) || exit;

final class OrderAdmin {

	public static function register(): void {
		add_filter( 'woocommerce_admin_billing_fields', array( self::class, 'billing_fields' ), 20, 2 );
		add_filter( 'woocommerce_admin_shipping_fields', array( self::class, 'shipping_fields' ), 20 );
		// After WooCommerce saves the order data meta box (priority 40).
		add_action( 'woocommerce_process_shop_order_meta', array( self::class, 'save' ), 50 );
		add_filter( 'woocommerce_ajax_get_customer_details', array( self::class, 'customer_details' ), 10, 2 );
	}

	public static function billing_fields( $fields, $order = null ) {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}
		// The Checkout block's own copy is replaced by the field below.
		unset( $fields[ BlockField::ID ] );

		$field    = array(
			'abn' => array(
				'label'           => Settings::label(),
				// The read-only view shows it in the formatted billing address.
				'show'            => false,
				'value'           => $order instanceof \WC_Order ? self::stored( $order ) : '',
				'update_callback' => '__return_null',
			),
		);
		$position = array_search( 'company', array_keys( $fields ), true );
		$position = false === $position ? count( $fields ) : $position + 1;
		return array_slice( $fields, 0, $position, true ) + $field + array_slice( $fields, $position, null, true );
	}

	public static function shipping_fields( $fields ) {
		if ( is_array( $fields ) ) {
			unset( $fields[ BlockField::ID ] );
		}
		return $fields;
	}

	public static function save( $order_id ): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce value.
		$nonce = isset( $_POST['woocommerce_meta_nonce'] ) ? wp_unslash( $_POST['woocommerce_meta_nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) || ! isset( $_POST['_billing_abn'] ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$country = $order->get_billing_country();
		$value   = Plugin::prepare( sanitize_text_field( wp_unslash( $_POST['_billing_abn'] ) ), $country );
		if ( '' !== $value && ! Abn::is_valid( $value ) ) {
			\WC_Admin_Meta_Boxes::add_error( Settings::invalid_message() );
			return;
		}
		if ( $value === (string) $order->get_meta( Plugin::ORDER_META ) && ! $order->meta_exists( BlockField::ORDER_KEY ) ) {
			return;
		}
		$order->update_meta_data( Plugin::ORDER_META, $value );
		if ( $order->meta_exists( BlockField::ORDER_KEY ) ) {
			$order->update_meta_data( BlockField::ORDER_KEY, $value );
		}
		$order->save();
	}

	/** "Load billing address" on a new order fills the ABN too. */
	public static function customer_details( $data, $customer ) {
		if ( is_array( $data ) && $customer instanceof \WC_Customer && isset( $data['billing'] ) && is_array( $data['billing'] ) ) {
			$data['billing']['abn'] = (string) $customer->get_meta( Plugin::CUSTOMER_META );
		}
		return $data;
	}

	/** What the edit form shows: the saved value as is, including one saved by the Checkout block. */
	private static function stored( \WC_Order $order ): string {
		$value = (string) $order->get_meta( Plugin::ORDER_META );
		return '' === $value ? (string) $order->get_meta( BlockField::ORDER_KEY ) : $value;
	}
}
