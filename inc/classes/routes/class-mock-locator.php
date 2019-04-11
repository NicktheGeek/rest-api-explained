<?php
/**
 * Mock Store Locator Controller.
 *
 * @package rest-api-explained
 */

namespace REST\API\Explained\Routes;

/**
 * Mock Store Locator Controller.
 *
 * This uses transients to maintain the current store.
 * Stores are groups into by_location, by_zipcode, and both.
 */
class Mock_Locator implements Store_Locator {

	/**
	 * Stores the found stores.
	 *
	 * @var array
	 */
	protected $found_stores = array();

	/**
	 * List of stores available only via geo locate function.
	 *
	 * @var array
	 */
	protected $geo_stores = array(
		array(
			'id'        => 1,
			'name'      => 'Geo Store 1',
			'address_1' => '1 Geo Road',
			'address_2' => 'Geo, FL 73160',
			'distance'  => 2,
		),
		array(
			'id'        => 2,
			'name'      => 'Geo Store 2',
			'address_1' => '2 Geo Road',
			'address_2' => 'Geo, FL 73160',
			'distance'  => 3,
		),
		array(
			'id'        => 3,
			'name'      => 'Geo Store 3',
			'address_1' => '3 Geo Road',
			'address_2' => 'Geo, FL 73160',
			'distance'  => 4,
		),
	);

	/**
	 * List of stores available only via zip code locate function.
	 *
	 * @var array
	 */
	protected $zip_code_stores = array(
		array(
			'id'        => 4,
			'name'      => 'Zip Code Store 1',
			'address_1' => '1 Zip Code Road',
			'address_2' => 'Zip Code, FL 73160',
			'distance'  => 2,
		),
		array(
			'id'        => 5,
			'name'      => 'Zip Code Store 2',
			'address_1' => '2 Zip Code Road',
			'address_2' => 'Zip Code, FL 73160',
			'distance'  => 3,
		),
		array(
			'id'        => 6,
			'name'      => 'Zip Code Store 3',
			'address_1' => '3 Zip Code Road',
			'address_2' => 'Zip Code, FL 73160',
			'distance'  => 4,
		),
	);

	/**
	 * List of stores available via both functions.
	 *
	 * @var array
	 */
	protected $shared_stores = array(
		array(
			'id'        => 7,
			'name'      => 'Shared Store 1',
			'address_1' => '1 Shared Road',
			'address_2' => 'Moore, OK 73160',
			'distance'  => 2,
		),
		array(
			'id'        => 8,
			'name'      => 'Shared Store 2',
			'address_1' => '2 Shared Road',
			'address_2' => 'Springfield, MO 65810',
			'distance'  => 3,
		),
		array(
			'id'        => 9,
			'name'      => 'Shared Store 3',
			'address_1' => '3 Shared Road',
			'address_2' => 'Shared, FL 73160',
			'distance'  => 4,
		),
	);

	/**
	 * Gets the transient key.
	 *
	 * @return string
	 */
	public function get_transient_key() {
		return md5( sprintf( '%1$s%2$s', __NAMESPACE__, __CLASS__ ) );
	}

	/**
	 * Initiate a store search based on geo location.
	 *
	 * @param string $latitude  64-bit floating-point value for latitude.
	 * @param string $longitude 64-bit floating-point value for longitude.
	 *
	 * @return bool True if stores are found, otherwise false.
	 */
	public function find_stores_by_geo( $latitude, $longitude ) {
		$this->found_stores = array_merge( $this->geo_stores, $this->shared_stores );
		return true;
	}

	/**
	 * Initiate a store search based on zipcode.
	 *
	 * @param string $zip_code Zipcode.
	 *
	 * @return bool True if stores are found, otherwise false.
	 */
	public function find_stores_by_zip_code( $zip_code ) {
		$this->found_stores = array_merge( $this->zip_code_stores, $this->shared_stores );
		return true;
	}

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
	public function get_stores() {
		return $this->found_stores;
	}

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
	public function get_store( $id ) {
		$stores = array_merge( $this->shared_stores, $this->geo_stores, $this->zip_code_stores );

		foreach ( $stores as $store ) {
			if ( isset( $store['id'] ) && (int) $id === (int) $store['id'] ) {
				return $store;
			}
		}

		return array();
	}

	/**
	 * Sets the current/selected store for later retrival with get_current_store().
	 *
	 * @param string $id Requested store ID.
	 *
	 * @return bool True for success otherwise false.
	 */
	public function set_current_store( $id ) {
		set_transient( $this->get_transient_key(), $id, WEEK_IN_SECONDS );

		return true;
	}

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
	public function get_current_store() {
		$store_id = $this->get_current_store_id();

		return $this->get_store( $store_id );
	}

	/**
	 * Gets the selected store ID.
	 *
	 * @return string
	 */
	public function get_current_store_id() {
		return get_transient( $this->get_transient_key() );
	}

}
