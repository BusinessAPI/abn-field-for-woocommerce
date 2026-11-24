<?php
/**
 * The ABN field on the Checkout block (Additional Checkout Fields API).
 *
 * Address fields registered with the API appear in both the billing and the shipping address
 * forms: with "Use same address for billing" the block only shows the shipping form and copies
 * it to billing. The billing value is the ABN; it is copied to _billing_abn (order) and
 * billing_abn (customer) and the shipping copy is never shown.
 *
 * @package AbnField
 */

namespace AbnField;

defined( 'ABSPATH' ) || exit;

final class BlockField {

	public const ID        = 'abnf/abn';
	public const ORDER_KEY = '_wc_billing/abnf/abn';

	public static function register(): void {
		add_action( 'woocommerce_init', array( self::class, 'maybe_register_field' ) );
		add_action( 'woocommerce_set_additional_field_value', array( self::class, 'sync' ), 10, 4 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( self::class, 'sync_order' ) );
		add_action( 'woocommerce_customer_save_address', array( self::class, 'sync_account_address' ), 20, 4 );
		add_filter( 'woocommerce_get_default_value_for_' . self::ID, array( self::class, 'default_value' ), 10, 3 );
	}

	public static function maybe_register_field(): void {
		if ( Plugin::block_mode() && function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			self::register_field();
		}
	}

	public static function register_field(): void {
		$label = Settings::label();
		woocommerce_register_additional_checkout_field(
			array(
				'id'                         => self::ID,
				'label'                      => $label,
				/* translators: %s: field label, "ABN" by default. */
				'optionalLabel'              => sprintf( __( '%s (optional)', 'abn-field-for-woocommerce' ), $label ),
				'location'                   => 'address',
				'type'                       => 'text',
				'required'                   => self::required_rule(),
				'hidden'                     => array(
					'customer' => array(
						'properties' => array(
							'address' => array(
								'properties' => array(
									'country' => array( 'not' => array( 'const' => Plugin::COUNTRY ) ),
								),
							),
						),
					),
				),
				'attributes'                 => array(
					'autocomplete' => 'off',
					'maxLength'    => 20,
				),
				// Shown once, in the formatted billing address (see Display).
				'show_in_order_confirmation' => false,
				'sanitize_callback'          => static fn ( $value ) => Abn::format( sanitize_text_field( (string) $value ), Settings::format() ),
				'validate_callback'          => array( self::class, 'validate' ),
			)
		);
	}

	/** True, false or a rule on the company name in the same address. */
	private static function required_rule() {
		switch ( Settings::required() ) {
			case Settings::REQUIRED_YES:
				return true;
			case Settings::REQUIRED_COMPANY:
				return array(
					'customer' => array(
						'properties' => array(
							'address' => array(
								'properties' => array(
									'company' => array( 'minLength' => 1 ),
								),
							),
						),
					),
				);
			default:
				return false;
		}
	}

	/**
	 * Empty values are handled by the API's required rules.
	 *
	 * @return true|\WP_Error
	 */
	public static function validate( $value ) {
		$value = (string) $value;
		if ( '' === trim( $value ) || Abn::is_valid( $value ) ) {
			return true;
		}
		return new \WP_Error( 'abnf_invalid', Settings::invalid_message() );
	}

	/** Mirror the billing value to the canonical key whenever WooCommerce stores the field. */
	public static function sync( $key, $value, $group, $wc_object ): void {
		if ( self::ID !== $key || 'billing' !== $group ) {
			return;
		}
		$value = Abn::is_valid( (string) $value ) ? Abn::format( (string) $value, Settings::format() ) : '';
		if ( $wc_object instanceof \WC_Order ) {
			$wc_object->update_meta_data( Plugin::ORDER_META, $value );
		} elseif ( $wc_object instanceof \WC_Customer ) {
			$wc_object->update_meta_data( Plugin::CUSTOMER_META, $value );
		}
	}

	/** Last word at block checkout: no ABN on orders billed outside Australia. */
	public static function sync_order( $order ): void {
		if ( ! $order instanceof \WC_Order || Plugin::COUNTRY === $order->get_billing_country() ) {
			return;
		}
		$order->delete_meta_data( Plugin::ORDER_META );
		$order->delete_meta_data( self::ORDER_KEY );
	}

	/** My Account billing address saved with a country other than Australia (the field was hidden). */
	public static function sync_account_address( $user_id, $address_type, $address, $customer ): void {
		if ( ! Plugin::block_mode() || 'billing' !== $address_type || ! $customer instanceof \WC_Customer ) {
			return;
		}
		if ( Plugin::COUNTRY !== $customer->get_billing_country() && '' !== (string) $customer->get_meta( Plugin::CUSTOMER_META ) ) {
			$customer->update_meta_data( Plugin::CUSTOMER_META, '' );
			$customer->save();
		}
	}

	/** Prefill the block from an ABN saved by the classic checkout. */
	public static function default_value( $value, $group, $wc_object ) {
		if ( ( null === $value || '' === $value ) && 'billing' === $group && $wc_object instanceof \WC_Customer ) {
			$saved = (string) $wc_object->get_meta( Plugin::CUSTOMER_META );
			return '' === $saved ? $value : $saved;
		}
		return $value;
	}
}
