<?php
/**
 * Options and the "ABN field" section in WooCommerce > Settings > General.
 *
 * @package AbnField
 */

namespace AbnField;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const REQUIRED = 'abnf_required';
	public const FORMAT   = 'abnf_format';
	public const LABEL    = 'abnf_label';

	public const REQUIRED_NO      = 'no';
	public const REQUIRED_YES     = 'yes';
	public const REQUIRED_COMPANY = 'company';

	public const FORMAT_DIGITS = 'digits';
	public const FORMAT_SPACED = 'spaced';

	public static function register(): void {
		add_filter( 'woocommerce_general_settings', array( self::class, 'add_section' ) );
	}

	public static function required(): string {
		$value = get_option( self::REQUIRED, self::REQUIRED_COMPANY );
		return in_array( $value, array( self::REQUIRED_NO, self::REQUIRED_YES, self::REQUIRED_COMPANY ), true ) ? $value : self::REQUIRED_COMPANY;
	}

	public static function format(): string {
		return self::FORMAT_DIGITS === get_option( self::FORMAT, self::FORMAT_SPACED ) ? self::FORMAT_DIGITS : self::FORMAT_SPACED;
	}

	public static function label(): string {
		$label = trim( (string) get_option( self::LABEL, '' ) );
		return '' === $label ? __( 'ABN', 'abn-field-for-woocommerce' ) : $label;
	}

	/**
	 * Whether the field must be filled in, given the company name entered with it.
	 */
	public static function is_required( string $company ): bool {
		$mode = self::required();
		return self::REQUIRED_YES === $mode || ( self::REQUIRED_COMPANY === $mode && '' !== trim( $company ) );
	}

	public static function invalid_message(): string {
		/* translators: %s: field label, "ABN" by default. */
		return sprintf( __( 'Enter a valid 11-digit %s.', 'abn-field-for-woocommerce' ), self::label() );
	}

	public static function required_message(): string {
		/* translators: %s: field label, "ABN" by default. */
		return sprintf( __( '%s is a required field.', 'abn-field-for-woocommerce' ), '<strong>' . esc_html( self::label() ) . '</strong>' );
	}

	public static function add_section( array $settings ): array {
		$settings[] = array(
			'title' => __( 'ABN field', 'abn-field-for-woocommerce' ),
			'type'  => 'title',
			'desc'  => __( 'Australian Business Number in the billing address. Shown only when the billing country is Australia.', 'abn-field-for-woocommerce' ),
			'id'    => 'abnf_options',
		);
		$settings[] = array(
			'title'    => __( 'Required', 'abn-field-for-woocommerce' ),
			'id'       => self::REQUIRED,
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'default'  => self::REQUIRED_COMPANY,
			'desc_tip' => true,
			'options'  => array(
				self::REQUIRED_NO      => __( 'No', 'abn-field-for-woocommerce' ),
				self::REQUIRED_YES     => __( 'Yes', 'abn-field-for-woocommerce' ),
				self::REQUIRED_COMPANY => __( 'Only when a company name is entered', 'abn-field-for-woocommerce' ),
			),
		);
		$settings[] = array(
			'title'    => __( 'Format saved', 'abn-field-for-woocommerce' ),
			'id'       => self::FORMAT,
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'default'  => self::FORMAT_SPACED,
			'desc'     => __( 'Applies when an ABN is saved at checkout, in My Account and on the order screen.', 'abn-field-for-woocommerce' ),
			'desc_tip' => true,
			'options'  => array(
				self::FORMAT_DIGITS => __( 'Digits only (51824753556)', 'abn-field-for-woocommerce' ),
				self::FORMAT_SPACED => __( 'Spaced (51 824 753 556)', 'abn-field-for-woocommerce' ),
			),
		);
		$settings[] = array(
			'title'       => __( 'Label', 'abn-field-for-woocommerce' ),
			'id'          => self::LABEL,
			'type'        => 'text',
			'default'     => 'ABN',
			'placeholder' => 'ABN',
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'abnf_options',
		);
		return $settings;
	}
}
