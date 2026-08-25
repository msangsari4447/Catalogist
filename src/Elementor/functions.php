<?php
/**
 * Get catalog item by product or variation ID.
 *
 * @param int   $product_id Product ID or variation ID.
 * @param int   $parent_product_id Parent product ID for variations.
 * @param array $context Additional context.
 *
 * @return \Catalogist\CatalogItem\CatalogItem|null
 */
function catalogist_get_catalog_item( int $product_id, int $parent_product_id = 0, array $context = array() ): ?\Catalogist\CatalogItem\CatalogItem {
	static $cache = array();

	$cache_key = $product_id . ':' . $parent_product_id . ':' . md5( serialize( $context ) );

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	/** @var \Catalogist\CatalogItem\CatalogItemFactory $factory */
	$factory = do_action_ref_array(
		'catalogist_get_catalog_item',
		array(
			$product_id,
			$parent_product_id,
			&$context,
		)
	);

	$item = $factory->create_from_product( $product_id, $parent_product_id );

	$cache[ $cache_key ] = $item;

	return $item;
}
