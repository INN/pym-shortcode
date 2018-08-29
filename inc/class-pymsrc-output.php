<?php
/**
 * The class and related methods for tracking which pymsrc tags will be output upon the page
 */

/**
 * Provide a singleton class to store all pymsrc URLs that will be output upon the page, and output them.
 *
 * Includes warning messages in the event that something strange is happening.
 *
 * Influenced by https://ttmm.io/tech/the-case-for-singletons/
 *
 * @since 1.3.2.1
 */
class Pymsrc_Output{
	/**
	 * @var Pymsrc_Output $instance The singleton of Pymsrc_Output.
	 */
	private static $instance;

	/**
	 * Get the singleton.
	 * @return Pymsrc_Output
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Set us up the vars
	 */
	private function __construct() {
		add_action( 'wp_footer', array( $this, 'output' ) );
	}

	/**
	 * The array of ids and sources
	 * @var Array
	 */
	private $sources = array();

	/**
	 * Add an item to the array of sources
	 *
	 * @param string $id The ID of the script tag to be added
	 * @param string $url The URL of the script tag to be added
	 * @return Bool whether there was a success.
	 *
	 * @todo validate these URLs somehow
	 * @link https://github.com/INN/pym-shortcode/issues/8 arbitrary js
	 * @link https://github.com/INN/pym-shortcode/issues/31 force CDN version
	 */
	public function add( $id, $url ) {
		$this->sources[$id] = $url;
	}

	/**
	 * Reset the array of sources to an empty array.
	 *
	 * Implemented because at some point we'll write tests for this? ¯\_(ツ)_/¯
	 */
	public function reset() {
		$this->sources = array();
	}

	/**
	 * Output the pymsrc script(s)
	 *
	 * @uses $this->warning_message()
	 */
	public function output() {
		$this->maybe_warning_message();
		foreach ( $this->sources as $id => $url ) {
			wp_enqueue_script(
				esc_attr( $id ),
				$url,
				array(),
				null,
				true
			);
		}
	}

	/**
	 * Determine whether warning messages should be output, based on $this->sources.
	 *
	 * This runs during wp_footer, as part of $this-output();
	 *
	 * @uses $this->warning_message_debug
	 * @uses $this->warning_message_footer
	 * @return bool Whether or not warning messages were output.
	 */
	public function maybe_warning_message() {
		if ( 1 < count( $this->sources ) ) {
			if ( WP_DEBUG ) {
				// to avoid cluttering up production logs on every page load
				$this->warning_message_debug();
			}
			$this->warning_message_footer();

			return true;
		} else {
			return false;
		}
	}

	public function warning_message_debug() {
		error_log(
			sprintf(
				'%1$s %2$s',
				__( 'There are more than one pym source URLs set on this page! The list:', 'pym_shortcode' ),
				var_export( $this->sources, true )
			)
		);
	}

	/**
	 * Output a thing in the footer that shows up in the browser console, to assist in debugging
	 *
	 * This has to support IE 9 because Pym supports IE 9, but `console.log` and `console.error` aren't available in IE 9 unless the dev tools are open. Thus, the check `window.console`.
	 * @link https://stackoverflow.com/questions/8002116/should-i-be-removing-console-log-from-production-code/15771110
	 */
	public function warning_message_footer() {
		printf(
			'<script type="text/javascript">window.console && console.log( \'%1$s\', %2$s );</script>',
			wp_json_encode( __( 'Hi Pym user! It looks like your post has multiple values for pymsrc for the blocks and shortcodes in use on this page. This may be causing problems for your Pym embeds.', 'pym_shortcode' ) ),
			wp_json_encode( $this->sources )
		);
	}

}
