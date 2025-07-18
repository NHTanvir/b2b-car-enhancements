<?php
/**
 * Plugin Name: B2B Car Enhancements
 * Description: Adds B2B functionality for user registration, user approval, profile completion, car favorites, and admin reporting based on client requirements.
 * Plugin URI:  https://worzen.com/
 * Author:      Al Imran Akash
 * Author URI:  https://profiles.wordpress.org/al-imran-akash/
 * Version: 	1.2
 * Text Domain: b2b-car-enhancements
 * Domain Path: /languages
 */

namespace Worzen\B2B_Car_Enhancements;
use Codexpert\Plugin\Notice;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for the plugin
 * @package Plugin
 * @author Codexpert <hi@codexpert.io>
 */
final class Plugin {
	
	/**
	 * Plugin instance
	 * 
	 * @access private
	 * 
	 * @var Plugin
	 */
	private static $_instance;

	/**
	 * The constructor method
	 * 
	 * @access private
	 * 
	 * @since 0.9
	 */
	private function __construct() {
		/**
		 * Includes required files
		 */
		$this->include();

		/**
		 * Defines contants
		 */
		$this->define();

		/**
		 * Runs actual hooks
		 */
		$this->hook();
	}

	/**
	 * Includes files
	 * 
	 * @access private
	 * 
	 * @uses composer
	 * @uses psr-4
	 */
	private function include() {
		require_once( dirname( __FILE__ ) . '/vendor/autoload.php' );
	}

	/**
	 * Define variables and constants
	 * 
	 * @access private
	 * 
	 * @uses get_plugin_data
	 * @uses plugin_basename
	 */
	private function define() {

		/**
		 * Define some constants
		 * 
		 * @since 0.9
		 */
		define( 'PUA', __FILE__ );
		define( 'PUA_DIR', dirname( PUA ) );
		define( 'PUA_ASSET', plugins_url( 'assets', PUA ) );
		define( 'PUA_DEBUG', apply_filters( 'plugin-client_debug', true ) );

		define( 'B2B_CAR_POST_TYPE', 'voiture' );
		define( 'B2B_RESERVATION_POST_TYPE', 'reservation' );
		define( 'B2B_PROFILE_PAGE_SLUG', 'profile' );

		/**
		 * The plugin data
		 * 
		 * @since 0.9
		 * @var $plugin
		 */
		$this->plugin					= get_plugin_data( PUA );
		$this->plugin['basename']		= plugin_basename( PUA );
		$this->plugin['file']			= PUA;
		$this->plugin['server']			= apply_filters( 'plugin-client_server', 'https://codexpert.io/dashboard' );
		$this->plugin['min_php']		= '5.6';
		$this->plugin['min_wp']			= '4.0';
		$this->plugin['icon']			= PUA_ASSET . '/img/icon.png';
		$this->plugin['depends']		= [ 'woocommerce/woocommerce.php' => 'WooCommerce' ];
		
	}

	/**
	 * Hooks
	 * 
	 * @access private
	 * 
	 * Executes main plugin features
	 *
	 * To add an action, use $instance->action()
	 * To apply a filter, use $instance->filter()
	 * To register a shortcode, use $instance->register()
	 * To add a hook for logged in users, use $instance->priv()
	 * To add a hook for non-logged in users, use $instance->nopriv()
	 * 
	 * @return void
	 */
	private function hook() {

		if( is_admin() ) :

			/**
			 * Admin facing hooks
			 */
			$admin = new App\Admin( $this->plugin );
			$admin->activate( 'install' );
			$admin->action( 'admin_footer', 'modal' );
			$admin->action( 'plugins_loaded', 'i18n' );
			$admin->action( 'admin_enqueue_scripts', 'enqueue_scripts' );

			/**
			 * Settings related hooks
			 */
			$settings = new App\Settings( $this->plugin );
			$settings->action( 'plugins_loaded', 'init_menu' );

			/**
			 * Renders different notices
			 * 
			 * @package Codexpert\Plugin
			 * 
			 * @author Codexpert <hi@codexpert.io>
			 */
			// $notice = new Notice( $this->plugin );

		else : // ! is_admin() ?

			/**
			 * Front facing hooks
			 */
			$front = new App\Front( $this->plugin );
			$front->action( 'wp_head', 'head' );
			$front->action( 'wp_footer', 'modal' );
			$front->action( 'wp_enqueue_scripts', 'enqueue_scripts' );

			/**
			 * Shortcode related hooks
			 */
			$shortcode = new App\Shortcode( $this->plugin );
			$shortcode->register( 'b2b_profile', 'render_complete_profile_form' );
			$shortcode->register( 'b2b_favorites_button', 'render_favorites_button' );
			$shortcode->register( 'b2b_my_favorites', 'render_my_favorites_list' );
			$shortcode->register( 'b2b_user_vat', 'get_user_vat_shortcode' );
			$shortcode->register( 'b2b_user_company', 'get_user_company_shortcode' );
			$shortcode->register( 'b2b_client_list', 'client_list' );
			$shortcode->register( 'b2b_client_count', 'client_count' );
			$shortcode->register( 'b2b_reservation_list', 'reservation_list' );
			$shortcode->register( 'b2b_reservation_count', 'reservation_count' );
			$shortcode->register( 'b2b_voiture_count', 'b2b_voiture_count' );
		endif;

		/**
		 * Cron facing hooks
		 */
		$cron = new App\Cron( $this->plugin );
		$cron->activate( 'install' );
		$cron->deactivate( 'uninstall' );

		/**
		 * Common hooks
		 *
		 * Executes on both the admin area and front area
		 */
		$common = new App\Common( $this->plugin );
		$common->action( 'user_register', 'user_register', 10, 2 );
		$common->action( 'template_redirect', 'redirect_to_complete_profile' );
		$common->filter( 'auth_cookie_expiration', 'extend_auth_cookie_duration', 99, 3 );
		$common->action( 'template_redirect', 'protect_car_content' );

		/**
		 * AJAX related hooks
		 */
		$ajax = new App\AJAX( $this->plugin );
		$ajax->priv( 'toggle_favorite', 'handle_toggle_favorite_ajax' );
	}

	/**
	 * Cloning is forbidden.
	 * 
	 * @access public
	 */
	public function __clone() { }

	/**
	 * Unserializing instances of this class is forbidden.
	 * 
	 * @access public
	 */
	public function __wakeup() { }

	/**
	 * Instantiate the plugin
	 * 
	 * @access public
	 * 
	 * @return $_instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

Plugin::instance();