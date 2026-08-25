<?php
/**
 * Product QR code dynamic tag.
 *
 * @package Catalogist
 */

declare(strict_types=1);

namespace Catalogist\Elementor\DynamicTags;

defined( 'ABSPATH' ) || exit;

/**
 * Product QR code dynamic tag.
 */
class ProductQrCodeDynamicTag extends ProductDynamicTagBase {

	/**
	 * QR code size in pixels.
	 *
	 * @var int
	 */
	private int $qr_size = 150;

	/**
	 * Get the tag ID.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'catalogist_product_qr_code';
	}

	/**
	 * Get the tag title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'QR Code', 'catalogist' );
	}

	/**
	 * Render the tag content.
	 *
	 * @return string
	 */
	public function render(): string {
		$product_id = (int) $this->get_settings( 'product_id' );

		if ( ! $product_id ) {
			return '';
		}

		$item = $this->resolve_catalog_item( $product_id );

		if ( ! $item || empty( $item->get_permalink() ) ) {
			return '';
		}

		$url = $item->get_permalink();
		$size = (int) $this->get_settings( 'size', $this->qr_size );

		// Use a public QR code API for simplicity.
		$qr_api = 'https://api.qrserver.com/v1/create-qr-code/?size=' . absint( $size ) . 'x' . absint( $size ) . '&data=' . urlencode( $url );

		return sprintf(
			'<img src="%s" alt="%s" class="catalogist-qr-code" width="%d" height="%d" />',
			esc_url( $qr_api ),
			esc_attr__( 'QR Code', 'catalogist' ),
			absint( $size ),
			absint( $size )
		);
	}

	/**
	 * Get control settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_control_settings(): array {
		$settings = parent::get_control_settings();
		$settings['size'] = array(
			'label'   => __( 'QR Code Size', 'catalogist' ),
			'type'    => 'number',
			'default' => $this->qr_size,
			'min'     => 50,
			'max'     => 500,
		);
		return $settings;
	}

	/**
	 * Render plain content for accessibility.
	 *
	 * @param array<string, mixed> $controls_data Control data.
	 *
	 * @return string
	 */
	public function render_plain_content( array $controls_data ): string {
		$product_id = (int) $controls_data['product_id'] ?? 0;

		if ( ! $product_id ) {
			return '';
		}

		$item = $this->resolve_catalog_item( $product_id );

		if ( ! $item ) {
			return '';
		}

		return $item->get_permalink();
	}
}
