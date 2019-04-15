<?php
/**
 * List and Locator REST API class.
 *
 * @package rest-api-explained
 */

/**
 * Todo:
 *  - Initialize the `register_routes` method.
 *  - Add error handling.
 */

namespace REST\API\Explained\Routes;

/**
 * Adds custom Rest routes and controls for the shopping list and store locator functionality.
 */
class Rest {

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'rae/v' . $version;

		$base = '/store/';
		// Get Current Store.
		register_rest_route(
			$namespace, $base, array(
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_current_store' ),
			)
		);

		// Get Stores By Geo.
		$regex = 'geo/(?P<lat>[a-z0-9 .\-]+)/(?P<long>[a-z0-9 .\-]+)';
		register_rest_route(
			$namespace, $base . $regex, array(
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_stores_by_geo' ),
			)
		);

		// Get Stores By Zip Code.
		$regex = '/zipcode/(?P<zipcode>[\d]+)';
		register_rest_route(
			$namespace, $base . $regex, array(
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_stores_by_zip_code' ),
			)
		);

		// Set current store.
		$regex = '/(?P<id>[\d]+)';
		register_rest_route(
			$namespace, $base . $regex, array(
				'methods'  => \WP_REST_Server::EDITABLE,
				'callback' => array( $this, 'set_current_store' ),
			)
		);

		// Get Store by ID.
		// Note: This uses the same regex as the route to set the current store.
		// The difference is the method. The prior route uses POST where this uses GET.
		register_rest_route(
			$namespace, $base . $regex, array(
				'methods'  => \WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_store_by_id' ),
			)
		);
	}

	/**
	 * Gets the current store.
	 *
	 * @return array
	 */
	public function get_current_store() {
		return get_locator()->get_current_store();
	}

	/**
	 * Finds and gets stores by geo location.
	 *
	 * @param \WP_REST_Request $data The REST request data.
	 *
	 * @return array
	 */
	public function get_stores_by_geo( $data ) {
		get_locator()->find_stores_by_geo( $data['lat'], $data['long'] );
		return $this->get_stores();
	}

	/**
	 * Finds and gets stores by zip code.
	 *
	 * @param \WP_REST_Request $data The REST request data.
	 *
	 * @return array
	 */
	public function get_stores_by_zip_code( $data ) {
		get_locator()->find_stores_by_zip_code( $data['zipcode'] );
		return $this->get_stores();
	}

	/**
	 * Sets the current store.
	 *
	 * @param \WP_REST_Request $data The REST request data.
	 *
	 * @return bool
	 */
	public function set_current_store( $data ) {
		return get_locator()->set_current_store( $data['id'] );
	}

	/**
	 * Gets store by ID.
	 *
	 * @param \WP_REST_Request $data The REST request data.
	 * @return array
	 */
	public function get_store_by_id( $data ) {
		return get_locator()->get_store( $data['id'] );
	}

	/**
	 * Invokes the get_locators()->get_stores() method and sorts by distance.
	 *
	 * @return array
	 */
	public function get_stores() {
		$stores = get_locator()->get_stores();

		if ( is_wp_error( $stores ) ) {
			return $stores;
		}

		usort(
			$stores, function( $a, $b ) {
				return $a['distance'] - $b['distance'];
			}
		);

		return $stores;
	}

}
