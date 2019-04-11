<?php
/**
 * Store Locator Interface.
 *
 * @package rest-api-explained
 */

namespace REST\API\Explained\Routes;

/**
 * Interface for store locator interaction.
 */
interface Store_Locator {

	/**
	 * Initiate a store search based on geo location.
	 *
	 * @param string $latitude  64-bit floating-point value for latitude.
	 * @param string $longitude 64-bit floating-point value for longitude.
	 *
	 * @return bool True if stores are found, otherwise false.
	 */
	public function find_stores_by_geo( $latitude, $longitude );

	/**
	 * Initiate a store search based on zipcode.
	 *
	 * @param string $zip_code Zipcode.
	 *
	 * @return bool True if stores are found, otherwise false.
	 */
	public function find_stores_by_zip_code( $zip_code );

	/**
	 * Gets the stores found (if any).
	 *
	 * @return array (
	 *     array(
	 *         'id'        => 1234,
	 *         'name'      => 'Store 1',
	 *         'address_1' => '123 Address Road',
	 *         'address_2' => 'Moore, OK 73160',
	 *         'distance'  => 2,
	 *     ),
	 *     array(
	 *         'id'        => 5678,
	 *         'name'      => 'Store 2',
	 *         'address_1' => '123 Address Road',
	 *         'address_2' => 'Springfield, MO 65810',
	 *         'distance'  => 2,
	 *     ),
	 * )
	 */
	public function get_stores();

	/**
	 * Gets a store data based on the store ID.
	 *
	 * @param string $id Requested store ID.
	 *
	 * @return array(
	 *     'id'        => 1234,
	 *     'name'      => 'Store 1',
	 *     'address_1' => '123 Address Road',
	 *     'address_2' => 'Moore, OK 73160',
	 *     'distance'  => 2,
	 * )
	 */
	public function get_store( $id );

	/**
	 * Sets the current/selected store for later retrival with get_current_store().
	 *
	 * @param string $id Requested store ID.
	 *
	 * @return bool True for success otherwise false.
	 */
	public function set_current_store( $id );

	/**
	 * Gets the current/selected store data.
	 *
	 * @return array(
	 *     'id'        => 1234,
	 *     'name'      => 'Store 1',
	 *     'address_1' => '123 Address Road',
	 *     'address_2' => 'Moore, OK 73160',
	 *     'distance'  => 2,
	 * )
	 */
	public function get_current_store();

	/**
	 * Gets the selected store ID.
	 *
	 * @return string
	 */
	public function get_current_store_id();

}
