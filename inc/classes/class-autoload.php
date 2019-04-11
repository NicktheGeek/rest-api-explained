<?php
/**
 * Autoload the classes.
 *
 * @package rest-api-explained
 */

namespace REST\API\Explained;

/**
 * Handles autoloading for the Rest API Explained namespace.
 */
class Autoload {

	/**
	 * Path starting from this file.
	 *
	 * @var string
	 */
	public $path_base;

	/**
	 * The class being autoloaded.
	 *
	 * @var string
	 */
	public $class;

	/**
	 * The constructed path to the class file.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Default seperator for folders.
	 *
	 * @var string
	 */
	public $separator = '/';

	/**
	 * Autoload constructor.
	 *
	 * Sets the $path_base variable.
	 */
	public function __construct() {
		$this->path_base = dirname( __FILE__ );
		$this->separator = defined( 'DIRECTORY_SEPARATOR' ) ? DIRECTORY_SEPARATOR : '/';
	}

	/**
	 * Callback for the spl_autoload_register function.
	 *
	 * @param string $class The class being checked.
	 */
	public function callback( $class ) {
		if ( false === strpos( $class, __NAMESPACE__ ) ) {
			return; // It's not in our namespace so ignore it.
		}
		$this->class = trim( str_replace( __NAMESPACE__, '', $class ), '\\' );

		if ( ! $this->verify_path() ) {
			return; // The path cannot be verified.
		}

		$this->require_file();
	}

	/**
	 * Verifies the file exists and is a valid file path.
	 *
	 * @return bool
	 */
	public function verify_path() {
		$this->set_path();

		if ( file_exists( $this->path ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Builds the path from the namespace parts.
	 */
	public function set_path() {
		$path_parts  = explode( '\\', $this->class );
		$this->path  = $this->trailingslashit( $this->path_base );
		$parts_count = count( $path_parts );

		foreach ( $path_parts as $part ) {
			$parts_count--;

			$part = strtolower( str_replace( '_', '-', $part ) );

			$this->path .= 0 === $parts_count ? sprintf( 'class-%s.php', $part ) : sprintf( '%1$s%2$s', $part, $this->separator );
		}
	}

	/**
	 * Requires the path.
	 */
	public function require_file() {
		require $this->path;
	}

	/**
	 * Adds a slash or backslash to the end of a string.
	 *
	 * @param string $it The thing that may need a trailing slash.
	 *
	 * @return string
	 */
	public function trailingslashit( $it ) {
		return rtrim( $it, $this->separator ) . $this->separator;
	}
}

$rest_api_explained_autoload = new Autoload();

spl_autoload_register( array( $rest_api_explained_autoload, 'callback' ) );
