<?php
/**
 * Functions used for interacting with the list and store APIs.
 *
 * @package rest-api-explained
 */

namespace REST\API\Explained\Routes;

/**
 * Gets the Store_Locator object.
 *
 * @return Store_Locator
 */
function get_locator() {
	static $locator;

	if ( empty( $locator ) ) {
		$class = __NAMESPACE__ . '\\Mock_Locator';

		$locator = new $class();
	}

	return $locator;
}

/**
 * Gets the Rest object.
 *
 * @return Rest
 */
function get_rest() {
	static $rest;

	if ( empty( $rest ) ) {
		$rest = new Rest();
	}

	return $rest;
}

/**
 * Triggers the Rest->register_routes() method.
 */
function register_routes() {
	get_rest()->register_routes();
}
