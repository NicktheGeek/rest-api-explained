<?php
/**
 * Shopping List Interface.
 *
 * @package rest-api-explained
 */

namespace REST\API\Explained\Routes;

/**
 * Interface for shopping list interaction.
 */
interface Shopping_List {
	/**
	 * Adds an item to the shopping list.
	 *
	 * @param string $sku Single product ID used to identify item.
	 *
	 * @return mixed|bool|\WP_Error
	 */
	public function add_to_list( $sku );

	/**
	 * Removes one or more items from the shopping list.
	 *
	 * @param string $sku Single product ID used to identify item.
	 * @param int    $quantity Number of items to remove.
	 *
	 * @return mixed|bool|\WP_Error
	 */
	public function remove_from_list( $sku, $quantity = 1 );

	/**
	 * Returns the current shopping list.
	 *
	 * @return array (
	 *     array(
	 *         'sku'      => 1234,
	 *         'name'     => 'Item 1',
	 *         'quantity' => 1,
	 *     ),
	 *     array(
	 *         'sku'      => 5678,
	 *         'name'     => 'Item 2',
	 *         'quantity' => 5,
	 *     ),
	 * )
	 */
	public function get_list();
}
