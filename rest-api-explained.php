<?php
/**
 * REST API Explained
 *
 * @package     NickTheGeek\RESTapiExplained
 * @author      Nick Croft
 * @copyright   2018 Nick Croft
 * @license     GPL-3.0+
 *
 * @wordpress-plugin
 * Plugin Name:       REST API Explained
 * Plugin URI:        https://github.com/NicktheGeek/rest-api-explained
 * Description:       Code examples from the REST API Explained talk.
 * Version:           1.2
 * Author:            Nick_theGeek
 * Author URI:        https://designsbynickthegeek.com/
 * Text Domain:       gfwa
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * GitHub Plugin URI: https://github.com/NicktheGeek/rest-api-explained
 * Requires PHP:      5.6
 * Requires WP:       5.0
 */

use REST\API\Explained\Request\Links;

// Load the autoloader.
require_once plugin_dir_path( __FILE__ ) . 'inc\classes\class-autoload.php';

// Load the routes functions file.
require_once plugin_dir_path( __FILE__ ) . 'inc\classes\routes\functions.php';

// Register the routes.
add_action( 'rest_api_init', 'REST\API\Explained\Routes\register_routes' );

/**
 * Get some links and output.
 */
function rest_api_explained_init() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	// The domain can include http:// if ssl is not available.
	// The route does not need to be specified, but can be to override the default.
	$links = new Links( 'example.com', [ 'keyword', 'another term' ], 3 );

	$links_found = $links->get();

	echo '<pre><code>';
	var_dump( $links_found ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
	echo '</code></pre>';
}
add_action( 'init', 'rest_api_explained_init' );

/**
 * Enqueues and localizes the RAE script.
 */
function rest_api_explained_enqueue() {
	wp_enqueue_script(
		'rest-api-explained-script',
		plugin_dir_url( __FILE__ ) . '/assets/src/js/locator.js',
		array( 'jquery', 'wp-api' ),
		'0.0.1',
		true
	);

	wp_localize_script(
		'rest-api-explained-script',
		'rest_api_explained_rest_uri',
		esc_url( trailingslashit( get_site_url() ) . 'wp-json/' )
	);
}
add_action( 'wp_enqueue_scripts', 'rest_api_explained_enqueue' );
