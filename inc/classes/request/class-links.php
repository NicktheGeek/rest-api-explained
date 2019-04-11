<?php
/**
 * Handles communication with Remote Site to search for related content and get links.
 *
 * @package rest-api-explained
 */

namespace REST\API\Explained\Request;

/**
 * Class Links
 *
 * @package REST\API\Explained\Request
 */
class Links {
	/**
	 * Keywords used to find related content.
	 *
	 * @var array
	 */
	public $keywords = [];

	/**
	 * The current keyword being processed in a loop.
	 *
	 * @var string
	 */
	public $keyword = '';

	/**
	 * Number of items to retrieve.
	 *
	 * @var int
	 */
	public $count = 2;

	/**
	 * The remote API URL.
	 *
	 * @var string
	 */
	public $api_uri = '';

	/**
	 * Posts returned from the API.
	 *
	 * @var array
	 */
	public $posts = [];

	/**
	 * Post IDs that have already been processed.
	 *
	 * @var array
	 */
	public $post_ids = [];

	/**
	 * The current post being processed in the loop.
	 *
	 * @var \stdClass
	 */
	public $post;

	/**
	 * The links data.
	 *
	 * @var array
	 */
	public $links = [];

	/**
	 * Max number of posts to retrieve.
	 *
	 * Used for the random posts API query.
	 *
	 * @var int
	 */
	public $posts_per_page = 20;

	/**
	 * The max number of pages available for random fallback posts.
	 *
	 * This value may need to be incremented over time to get all possible posts for random shuffle.
	 *
	 * @var int
	 */
	public $max_page = 9;

	/**
	 * Transient ID to store the max posts pages.
	 *
	 * @var string
	 */
	public $max_page_transient = 'rae_links_max_page';

	/**
	 * Group for caching post link backs.
	 *
	 * @var string
	 */
	public $cache_group = 'rae_link_back';

	/**
	 * Cache key for caching post link backs.
	 *
	 * MD5 of the imploded keywords.
	 *
	 * @var string
	 */
	public $cache_key = '';

	/**
	 * The timeout for the cached links.
	 *
	 * @var int
	 */
	public $cache_expires = DAY_IN_SECONDS;

	/**
	 * Links constructor.
	 *
	 * @param string $api_url  The domain to make the remote request. If the domain uses the standard WP API, no need to provide additional parts.
	 * @param array  $keywords Keywords used to find related content.
	 * @param int    $count    The max number of links to return. Default is 2.
	 */
	public function __construct( $api_url, $keywords = [], $count = 0 ) {
		$this->api_uri   = $this->build_api_base( $api_url );
		$this->keywords  = $keywords;
		$this->count     = empty( $count ) ? $this->count : $count;
		$this->cache_key = md5( implode( $keywords ) );

		$links = wp_cache_get( $this->cache_key, $this->cache_group );

		if ( ! empty( $links ) ) {
			$this->links = $links;
			return;
		}

		$this->get_posts();
		$this->update_cache();
	}

	/**
	 * Builds and returns the API URL so if a fully formed URL is provided, no changes are made.
	 *
	 * Enforces https if https? is not provided.
	 * Uses default API route wp-json/wp/v2/posts/ unless another route is specified.
	 *
	 * @param string $api_url The API URL.
	 *
	 * @return string
	 */
	public function build_api_base( $api_url ) {
		// Make sure the URL has https.
		$api_url = 0 === strpos( $api_url, 'http' ) ? $api_url : sprintf( 'https://%s', $api_url );

		// Make sure the URL ends in wp-json/wp/v2/posts/.
		return false !== strpos( $api_url, 'wp-json/wp' ) ? $api_url : sprintf( 'wp-json/wp/v2/posts/', trailingslashit( $api_url ) );
	}

	/**
	 * Loops the keywords in order to retrieve the remote posts.
	 */
	public function get_posts() {
		foreach ( $this->keywords as $this->keyword ) {
			$this->process_keyword();

			if ( count( $this->links ) >= $this->count ) {
				return;
			}
		}
		$this->get_random_links();
	}

	/**
	 * Processes a single keyword in order to build links from remote posts search.
	 */
	public function process_keyword() {
		$this->api_search();

		if ( ! empty( $this->posts ) ) {
			$this->set_links();
		}
	}

	/**
	 * This is a fall back to get random links if enough relevant links aren't available.
	 */
	public function get_random_links() {
		$this->api_randomize();

		if ( ! empty( $this->posts ) ) {
			$this->set_links();
		}

		if ( count( $this->links ) < $this->count ) {
			$this->max_page--;
			$this->get_random_links();
		}
	}

	/**
	 * Initiates a request to the remote API to retrieve posts via REST search.
	 */
	public function api_search() {
		$url = add_query_arg(
			[
				'search'  => $this->keyword,
				'_embed'  => 1,
				'orderby' => 'relevance',
			],
			$this->api_uri
		);

		$this->posts = $this->get_response( $url );
	}

	/**
	 * Gets 99 posts and shuffles them. Also selects a random page of posts in order to get older posts as well.
	 */
	public function api_randomize() {
		$url = add_query_arg(
			[
				'per_page' => $this->posts_per_page,
				'page'     => rand( 1, $this->get_max_page() ),
				'_embed'   => 1,
			],
			$this->api_uri
		);

		$this->posts = $this->get_response( $url, true );

		shuffle( $this->posts );
	}

	/**
	 * Gets the formatted response.
	 *
	 * @param  string $url        URL for the remote request.
	 * @param  bool   $update_max Indicates the maybe_update_max method should be run.
	 *
	 * @return array
	 */
	public function get_response( $url, $update_max = false ) {
		$response = wp_remote_get( $url );

		if ( $update_max ) {
			$this->maybe_update_max( wp_remote_retrieve_headers( $response ) );
		}

		return (array) ( is_wp_error( $response ) ? [] : json_decode( wp_remote_retrieve_body( $response ) ) );
	}

	/**
	 * Gets the max number of pages in the query.
	 *
	 * @return int
	 */
	public function get_max_page() {
		$max = get_transient( $this->max_page_transient );

		return empty( $max ) ? $this->max_page : $max;
	}

	/**
	 * Updates max page transient if it is expired and the header to set the page value is correctly set.
	 *
	 * @param array $headers The response headers as an array.
	 */
	public function maybe_update_max( $headers ) {
		if ( empty( get_transient( $this->max_page_transient ) ) && ! empty( absint( $headers['X-WP-TotalPages'] ) ) ) {
			set_transient( $this->max_page_transient, absint( $headers['X-WP-TotalPages'] ), WEEK_IN_SECONDS );
		}
	}

	/**
	 * Adds values to the links.
	 */
	public function set_links() {
		foreach ( $this->posts as $this->post ) {
			$this->set_link();
		}
	}

	/**
	 * Processes a post and sets the specific link.
	 */
	public function set_link() {
		$thumbnail = $this->get_thumbnail();
		$category  = $this->get_category();

		if (
			empty( $this->post->title ) ||
			empty( $this->post->link ) ||
			in_array( $this->post->id, $this->post_ids, true ) ||
			false === $thumbnail ||
			false === $category
		) {
			return;
		}

		$this->post_ids[] = $this->post->id;
		$this->links[]    = [
			'title'        => $this->post->title->rendered,
			'url'          => $this->post->link,
			'thumbnail'    => $thumbnail,
			'category'     => $category['title'],
			'category_url' => $category['url'],
		];
	}

	/**
	 * Gets the thumbnail for the current post.
	 *
	 * @return mixed|bool|string
	 */
	public function get_thumbnail() {
		if ( empty( $this->post->featured_media ) ) {
			return false;
		}

		foreach ( $this->post->_embedded->{ 'wp:featuredmedia' }  as $featured_image ) {
			if ( isset( $featured_image->media_details->sizes->medium->source_url ) ) {
				return $featured_image->media_details->sizes->medium->source_url;
			}

			return isset( $featured_image->source_url ) ? $featured_image->source_url : false;
		}

		return false;
	}

	/**
	 * Gets the category data for a post.
	 *
	 * @return mixed|bool|array
	 */
	public function get_category() {
		foreach ( $this->post->_embedded->{ 'wp:term' } as $taxonomy_group ) {
			foreach ( $taxonomy_group as $maybe_category ) {
				if ( isset( $maybe_category->taxonomy ) && 'category' === $maybe_category->taxonomy ) {
					return [
						'title' => $maybe_category->name,
						'url'   => $maybe_category->link,
					];
				}
			}
		}

		return false;
	}

	/**
	 * Updates the cache for the requested keywords.
	 */
	private function update_cache() {
		if ( ! empty( $this->links ) ) {
			wp_cache_add( $this->cache_key, $this->links, $this->cache_group, $this->cache_expires );
		}
	}

	/**
	 * Gets the $links property.
	 *
	 * @return array
	 */
	public function get() {
		return count( $this->links ) > $this->count ? array_slice( $this->links, 0, $this->count ) : $this->links;
	}
}
