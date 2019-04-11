<?php
/**
 * Mock Shopping List Controller.
 *
 * @package rest-api-explained
 */

namespace REST\API\Explained\Routes;

/**
 * Mock Shopping List Controller.
 *
 * This uses transients to maintain the list. The transient will be tied to a store ID so it behaves as expected.
 * The mock object is used to build the UI independent of the remote API. It can also be used for test purposes.
 */
class Mock_List implements Shopping_List {
	/**
	 * Gets the current store ID.
	 *
	 * @return string|\WP_Error
	 */
	public function get_store_id() {
		$locator = get_locator();

		if ( ! is_a( $locator, __NAMESPACE__ . '\\Store_Locator' ) ) {
			return new \WP_Error( 1, 'Invalid locator' );
		}

		$store_id = $locator->get_current_store_id();

		if ( empty( $store_id ) ) {
			return new \WP_Error( 2, 'Store not set' );
		}

		return $store_id;
	}

	/**
	 * Builds and returns a transient key using the namespace, class, and current store ID.
	 *
	 * @return string|\WP_Error
	 */
	public function get_transient_key() {
		$store_id = $this->get_store_id();

		if ( is_wp_error( $store_id ) ) {
			return $store_id;
		}

		return md5( sprintf( '%1$s%2$s%3$s', __NAMESPACE__, __CLASS__, $store_id ) );
	}

	/**
	 * Gets the current list so it can be modified.
	 *
	 * @return array|mixed|string|\WP_Error
	 */
	public function get_current_list() {
		$transient_key = $this->get_transient_key();

		if ( is_wp_error( $transient_key ) ) {
			return $transient_key;
		}

		$current_list = get_transient( $transient_key );

		return empty( $current_list ) || ! is_array( $current_list ) ? array() : $current_list;
	}

	/**
	 * Sets the current list in the transient.
	 *
	 * @param array $list The list.
	 *
	 * @return bool|\WP_Error
	 */
	public function set_transient( $list ) {
		$transient_key = $this->get_transient_key();

		if ( is_wp_error( $transient_key ) ) {
			return $transient_key;
		}

		set_transient( $transient_key, $list, WEEK_IN_SECONDS );

		return true;
	}

	/**
	 * Adds an item to the shopping list.
	 *
	 * @param string $sku Single product ID used to identify item.
	 *
	 * @return mixed|bool|\WP_Error
	 */
	public function add_to_list( $sku ) {
		$current_list = $this->get_current_list();

		if ( is_wp_error( $current_list ) ) {
			return $current_list;
		}

		if ( isset( $current_list[ $sku ] ) ) {
			$current_list[ $sku ]++;
		} else {
			$current_list[ $sku ] = 1;
		}

		$set = $this->set_transient( $current_list );

		return $set;
	}

	/**
	 * Removes one or more items from the shopping list.
	 *
	 * @param string $sku Single product ID used to identify item.
	 * @param int    $quantity Number of items to remove.
	 *
	 * @return mixed|bool|\WP_Error
	 */
	public function remove_from_list( $sku, $quantity = 1 ) {
		$current_list = $this->get_current_list();

		if ( is_wp_error( $current_list ) ) {
			return $current_list;
		}

		if ( isset( $current_list[ $sku ] ) ) {
			$current_list[ $sku ] -= $quantity;

			if ( $current_list[ $sku ] <= 0 ) {
				unset( $current_list[ $sku ] );
			}
		}

		$set = $this->set_transient( $current_list );

		if ( is_wp_error( $set ) ) {
			return $set;
		}

		return true;
	}

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
	public function get_list() {
		$current_list = $this->get_current_list();

		if ( is_wp_error( $current_list ) ) {
			return $current_list;
		}

		if ( empty( $current_list ) ) {
			return array();
		}

		$list = array();

		foreach ( $current_list as $sku => $quantity ) {
			$product = wp_cache_get( md5( sprintf( '%s%s%s', __NAMESPACE__, __CLASS__, $sku ) ) );

			if ( empty( $quantity ) ) {
				continue;
			}

			if ( empty( $product ) ) {
				$products = new \WP_Query(
					array(
						'post_type'      => 'rae-product',
						'post_status'    => 'publish',
						'no_found_rows'  => true,
						'posts_per_page' => 1,
						'meta_query'     => array(
							array(
								'key'     => 'rae-product_fields',
								'value'   => $sku,
								'compare' => 'LIKE', // It's in an array so I'm doing a loose comparison.
							),
						),
					)
				); // WPCS: slow query ok.

				if ( $products->have_posts() ) {
					$product = array(
						'sku'      => $sku,
						'name'     => $products->post->post_title,
						'quantity' => $quantity,
					);
				}
			}

			if ( empty( $product ) ) {
				continue;
			}

			$list[] = $product;
		}

		return $list;
	}

}
