<?php
/**
 * The billing_abn field on the classic checkout and the My Account billing address form.
 *
 * @package AbnField
 */

namespace AbnField;

defined( 'ABSPATH' ) || exit;

final class ClassicField {

	public const KEY = 'billing_abn';

	public static function register(): void {
		add_filter( 'woocommerce_billing_fields', array( self::class, 'add_field' ), 20 );
		add_filter( 'woocommerce_checkout_posted_data', array( self::class, 'prepare_posted' ) );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'validate_checkout' ), 10, 2 );
		add_action( 'woocommerce_after_save_address_validation', array( self::class, 'save_account_address' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	public static function add_field( $fields ) {
		if ( ! is_array( $fields ) || Plugin::block_mode() ) {
			return $fields;
		}
		// Required is enforced in PHP (it depends on the country and company name); the script shows the marker.
		$fields[ self::KEY ] = array(
			'label'             => Settings::label(),
			'required'          => false,
			'type'              => 'text',
			'class'             => array( 'form-row-wide', 'abnf-field' ),
			'priority'          => 31,
			'autocomplete'      => 'off',
			'maxlength'         => 20,
			'custom_attributes' => array( 'data-abnf-required' => Settings::required() ),
		);
		return $fields;
	}

	public static function prepare_posted( $data ) {
		if ( is_array( $data ) && array_key_exists( self::KEY, $data ) ) {
			$data[ self::KEY ] = Plugin::prepare( (string) $data[ self::KEY ], (string) ( $data['billing_country'] ?? '' ) );
		}
		return $data;
	}

	public static function validate_checkout( $data, $errors ): void {
		if ( ! is_array( $data ) || ! array_key_exists( self::KEY, $data ) || Plugin::COUNTRY !== ( $data['billing_country'] ?? '' ) ) {
			return;
		}
		$error = self::error( (string) $data[ self::KEY ], (string) ( $data['billing_company'] ?? '' ) );
		if ( $error ) {
			$errors->add( self::KEY . '_validation', $error, array( 'id' => self::KEY ) );
		}
	}

	/**
	 * WooCommerce has put the posted value on the customer; replace it with the saved format or flag it.
	 */
	public static function save_account_address( $user_id, $address_type, $address, $customer ): void {
		if ( 'billing' !== $address_type || ! is_array( $address ) || ! isset( $address[ self::KEY ] ) || ! $customer instanceof \WC_Customer ) {
			return;
		}
		$country = $customer->get_billing_country();
		$value   = Plugin::prepare( (string) $customer->get_meta( self::KEY ), $country );
		if ( Plugin::COUNTRY === $country ) {
			$error = self::error( $value, $customer->get_billing_company() );
			if ( $error ) {
				wc_add_notice( $error, 'error', array( 'id' => self::KEY ) );
				return;
			}
		}
		$customer->update_meta_data( self::KEY, $value );
	}

	/** Error message for an Australian billing address, or '' when the value is fine. */
	public static function error( string $value, string $company ): string {
		if ( '' === $value ) {
			return Settings::is_required( $company ) ? Settings::required_message() : '';
		}
		return Abn::is_valid( $value ) ? '' : Settings::invalid_message();
	}

	public static function enqueue(): void {
		if ( Plugin::block_mode() || ! ( is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) ) {
			return;
		}
		wp_enqueue_script( 'abnf-classic', ABNF_URL . 'assets/classic.js', array( 'jquery' ), ABNF_VERSION, true );
	}
}
