<?php
/**
 * Plugin Name: Escape Hatch - Fly Image Generator
 * Plugin URI: https://escapehatch.co
 * Description: Generates Fly Images when created in Gutenberg.
 * Author: Escape Hatch
 * Author URI: https://escapehatch.co
 * Version: 1.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Escape Hatch Fly Generator
 *
 * Class Esc_Fly_Image_Generator
 */
class Esc_Fly_Image_Generator {

	/**
	 * Initialization of Plugin
	 */
	public function init() {
		add_filter( 'wp', array( $this, 'render_response' ) );
		add_filter( 'init', array( $this, 'rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * Outputs Image
	 */
	public function render_response() {
		$generate_image = get_query_var( 'esc-fly-image-generate' );

		//If Generate Image Exists, generate and return image.
		if ( $generate_image ) {
			$this->generate_image();

			//The above function will exit, so this will only be called if the image could not be generated.
			$this->generate_404();
		}
	}

	/**
	 * Sets Up Query Vars
	 *
	 * @param array $vars Allowed Variables.
	 *
	 * @return array
	 */
	public function query_vars( $vars ): array {
		array_push( $vars, 'esc-fly-image-generate' );
		return $vars;
	}

	/**
	 * Adds & Flushes Rewrite Rules.
	 */
	public function flush_rules() {
		$this->rewrite_rules();
		flush_rewrite_rules();
	}

	/**
	 * Adds Rewrite Rules.
	 */
	public function rewrite_rules() {
		add_rewrite_rule( 'esc-fly-image/generate$', 'index.php?esc-fly-image-generate=1', 'top' );
	}

	/**
	 * Generates & Returns Image from Fly based upon URL
	 */
	public function generate_image() {
		preg_match( '/wp-content\/uploads\/(sites\/\d+\/)?fly-images\/(\d+)\/.+-(\d+)x(\d+)(-[lrc]?[tcb])?/', $_SERVER['REQUEST_URI'] ?? '', $image_args );
		if ( ! $image_args ) {
			$this->generate_404();
			return;
		}

		$id     = (int) $image_args[2];
		$width  = (int) $image_args[3];
		$height = (int) $image_args[4];
		$crop   = $image_args[5] ?? '';

		$crop = ltrim( $crop, '-' );

		switch ( $crop ) {
			case 'c':
				$crop_arg = true;
				break;
			case 'ct':
				$crop_arg = [ 'center', 'top' ];
				break;
			case 'cb':
				$crop_arg = [ 'center', 'bottom' ];
				break;
			case 'cc':
				$crop_arg = [ 'center', 'center' ];
				break;
			case 'lt':
				$crop_arg = [ 'left', 'top' ];
				break;
			case 'lc':
				$crop_arg = [ 'left', 'center' ];
				break;
			case 'lb':
				$crop_arg = [ 'left', 'bottom' ];
				break;
			case 'rt':
				$crop_arg = [ 'right', 'top' ];
				break;
			case 'rc':
				$crop_arg = [ 'right', 'center' ];
				break;
			case 'rb':
				$crop_arg = [ 'right', 'bottom' ];
				break;
			default:
				$crop_arg = false;
		}

		/** Generate Image */
		$source = fly_get_attachment_image_src( $id, [ $width, $height ], $crop_arg );

		$uri = $source['src'] ?? '';
		if ( ! $uri ) {
			$this->generate_404();
			return;
		}

		/**
		 * Original File Path
		 *
		 * @var string $original Original Image.
		 */
		$original = str_replace( trim( home_url(), '/' ), ABSPATH, $uri );

		if ( ! file_exists( $original ) ) {
			$this->generate_404();
			return;
		}

		/**
		 * Returns File
		 */
		$resource  = fopen( $original, 'rb' );
		$mime_type = mime_content_type( $resource );

		header( sprintf( 'Content-Type: %s', $mime_type ) );
		header( sprintf( 'Content-Length: %d', filesize( $original ) ) );
		fpassthru( $resource );
		exit( 0 );
	}

	/**
	 * Sets up Template to return 404.
	 */
	public function generate_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}

add_action( 'plugins_loaded', [ new Esc_Fly_Image_Generator(), 'init' ] );

/** Activation function */
register_activation_hook( __FILE__, [ new Esc_Fly_Image_Generator(), 'flush_rules' ] );
