<?php
/**
 * ABN normalising, checksum and formatting.
 *
 * @package AbnField
 */

namespace AbnField;

defined( 'ABSPATH' ) || exit;

final class Abn {

	private const WEIGHTS = array( 10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19 );

	/**
	 * Digits of an ABN typed with optional spaces, dashes or dots. Null when anything else is in it.
	 */
	public static function digits( string $value ): ?string {
		$value = trim( $value );
		if ( '' === $value || preg_match( '/[^0-9\s.\-]/u', $value ) ) {
			return null;
		}
		return preg_replace( '/[\s.\-]+/u', '', $value );
	}

	public static function is_valid( string $value ): bool {
		$digits = self::digits( $value );
		if ( null === $digits || 11 !== strlen( $digits ) ) {
			return false;
		}
		$sum = 0;
		foreach ( self::WEIGHTS as $i => $weight ) {
			$digit = (int) $digits[ $i ];
			if ( 0 === $i ) {
				--$digit;
			}
			$sum += $digit * $weight;
		}
		return 0 === $sum % 89;
	}

	/**
	 * A valid ABN in the given style ("digits" or "spaced"); anything else comes back trimmed.
	 */
	public static function format( string $value, string $style = Settings::FORMAT_SPACED ): string {
		if ( ! self::is_valid( $value ) ) {
			return trim( $value );
		}
		$digits = self::digits( $value );
		if ( Settings::FORMAT_DIGITS === $style ) {
			return $digits;
		}
		return substr( $digits, 0, 2 ) . ' ' . substr( $digits, 2, 3 ) . ' ' . substr( $digits, 5, 3 ) . ' ' . substr( $digits, 8, 3 );
	}
}
