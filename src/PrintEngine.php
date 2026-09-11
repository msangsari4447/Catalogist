<?php

declare(strict_types=1);

namespace Catalogist;

/**
 * Print Engine — prepares rendered catalog HTML for A4 browser printing.
 *
 * This class owns print CSS and print-specific markup only. It does not query
 * WordPress or WooCommerce, render catalog data, or provide a preview/output
 * endpoint.
 */
final class PrintEngine {

	/**
	 * Default print page size.
	 */
	public const DEFAULT_PAGE_SIZE = 'A4';

	/**
	 * Default print configuration.
	 *
	 * Margins are measured in millimetres and are deliberately represented as
	 * numeric values so user-provided configuration cannot become CSS.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_configuration(): array {
		return array(
			'page_size'   => self::DEFAULT_PAGE_SIZE,
			'orientation' => 'portrait',
			'direction'   => 'ltr',
			'margins'     => array(
				'top'    => 10,
				'right'  => 10,
				'bottom' => 10,
				'left'   => 10,
			),
		);
	}

	/**
	 * Validate print configuration.
	 *
	 * @param array<string, mixed> $configuration Print configuration.
	 * @return list<string> Validation errors.
	 */
	public static function validate_configuration( array $configuration ): array {
		$errors = array();

		if ( ( $configuration['page_size'] ?? self::DEFAULT_PAGE_SIZE ) !== self::DEFAULT_PAGE_SIZE ) {
			$errors[] = __( 'Print page size must be A4.', 'catalogist' );
		}

		if ( ! in_array( $configuration['orientation'] ?? 'portrait', array( 'portrait', 'landscape' ), true ) ) {
			$errors[] = __( 'Print orientation must be portrait or landscape.', 'catalogist' );
		}

		if ( ! in_array( $configuration['direction'] ?? 'ltr', array( 'ltr', 'rtl' ), true ) ) {
			$errors[] = __( 'Print direction must be ltr or rtl.', 'catalogist' );
		}

		$margins = $configuration['margins'] ?? array();
		if ( ! is_array( $margins ) ) {
			$errors[] = __( 'Print margins must be an array.', 'catalogist' );
			return $errors;
		}

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( ! array_key_exists( $side, $margins ) || ! self::is_valid_margin( $margins[ $side ] ) ) {
				$errors[] = sprintf(
					/* translators: %s: margin side */
					__( 'Print margin "%s" must be between 0 and 50 millimetres.', 'catalogist' ),
					$side
				);
			}
		}

		return $errors;
	}

	/**
	 * Sanitize and normalize print configuration.
	 *
	 * Unknown keys are discarded. Invalid values fall back to safe defaults.
	 *
	 * @param array<string, mixed> $input Raw print configuration.
	 * @return array<string, mixed> Normalized configuration.
	 */
	public static function sanitize_configuration( array $input ): array {
		$defaults = self::default_configuration();
		$config   = $defaults;

		if ( ( $input['page_size'] ?? null ) === self::DEFAULT_PAGE_SIZE ) {
			$config['page_size'] = self::DEFAULT_PAGE_SIZE;
		}

		if ( in_array( $input['orientation'] ?? null, array( 'portrait', 'landscape' ), true ) ) {
			$config['orientation'] = $input['orientation'];
		}

		if ( in_array( $input['direction'] ?? null, array( 'ltr', 'rtl' ), true ) ) {
			$config['direction'] = $input['direction'];
		}

		if ( isset( $input['margins'] ) && is_array( $input['margins'] ) ) {
			foreach ( array_keys( $defaults['margins'] ) as $side ) {
				if ( isset( $input['margins'][ $side ] ) && self::is_valid_margin( $input['margins'][ $side ] ) ) {
					$config['margins'][ $side ] = self::normalize_margin( $input['margins'][ $side ] );
				}
			}
		}

		return $config;
	}

	/**
	 * Render a print-ready wrapper around HTML produced by Renderer.
	 *
	 * The HTML argument is an already-rendered fragment. Renderer is responsible
	 * for escaping dynamic catalog values; this class only adds validated print
	 * configuration and print CSS around that fragment.
	 *
	 * @param string               $html          Rendered catalog HTML fragment.
	 * @param array<string, mixed> $configuration Print configuration.
	 * @return string Print-ready HTML.
	 */
	public function render( string $html, array $configuration = array() ): string {
		$config = self::sanitize_configuration( $configuration );

		return '<div class="catalogist-print" dir="' . esc_attr( $config['direction'] ) . '"' .
			' data-page-size="' . esc_attr( $config['page_size'] ) . '"' .
			' data-orientation="' . esc_attr( $config['orientation'] ) . '">' .
			$this->get_print_css( $config ) .
			$html .
			'</div>';
	}

	/**
	 * Build the print stylesheet for a normalized configuration.
	 *
	 * @param array<string, mixed> $configuration Print configuration.
	 * @return string A style element containing print-only CSS.
	 */
	public function get_print_css( array $configuration = array() ): string {
		$config  = self::sanitize_configuration( $configuration );
		$margins = $config['margins'];
		$size    = $config['page_size'] . ' ' . $config['orientation'];
		$margin  = $margins['top'] . 'mm ' .
			$margins['right'] . 'mm ' .
			$margins['bottom'] . 'mm ' .
			$margins['left'] . 'mm';

		$css  = '@page { size: ' . $size . '; margin: ' . $margin . '; }';
		$css .= '@media print {';
		$css .= '.catalogist-print { direction: ' . $config['direction'] . '; }';
		$css .= '.catalogist-print .catalogist-header,';
		$css .= '.catalogist-print .catalogist-footer,';
		$css .= '.catalogist-print .catalogist-card {';
		$css .= 'break-inside: avoid; page-break-inside: avoid; }';
		$css .= '.catalogist-print .catalogist-header {';
		$css .= 'break-after: avoid; page-break-after: avoid; }';
		$css .= '.catalogist-print .catalogist-footer {';
		$css .= 'break-before: avoid; page-break-before: avoid; }';
		$css .= '.catalogist-print .catalogist-card > * {';
		$css .= 'break-inside: avoid; page-break-inside: avoid; }';
		$css .= '.catalogist-print img { max-width: 100%; height: auto; }';
		$css .= '.catalogist-print, .catalogist-print * {';
		$css .= '-webkit-print-color-adjust: exact; print-color-adjust: exact; }';
		$css .= '}';

		return '<style media="print" data-catalogist-print="1">' . $css . '</style>';
	}

	/**
	 * Check whether a margin is a finite value in the supported range.
	 *
	 * @param mixed $margin Margin value in millimetres.
	 * @return bool
	 */
	private static function is_valid_margin( $margin ): bool {
		if ( ! is_numeric( $margin ) || ! is_finite( (float) $margin ) ) {
			return false;
		}

		$value = (float) $margin;
		return $value >= 0 && $value <= 50;
	}

	/**
	 * Normalize a validated margin for deterministic CSS output.
	 *
	 * @param mixed $margin Margin value in millimetres.
	 * @return int|float
	 */
	private static function normalize_margin( $margin ) {
		$value = round( (float) $margin, 2 );
		return floor( $value ) === $value ? (int) $value : $value;
	}
}
