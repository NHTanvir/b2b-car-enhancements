<?php
/**
 * All admin facing functions
 */
namespace Worzen\B2B_Car_Enhancements\App;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Metabox;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Admin
 * @author Codexpert <hi@codexpert.io>
 */
class Admin extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->server	= $this->plugin['server'];
		$this->version	= $this->plugin['Version'];

		add_filter( 'manage_users_columns', [ $this, 'add_custom_user_columns' ] );
        add_action( 'manage_users_custom_column', [ $this, 'render_custom_user_column_data' ], 10, 3 );
        add_filter( 'manage_edit-' . B2B_RESERVATION_POST_TYPE . '_columns', [ $this, 'add_reservation_columns' ] );
        add_action( 'manage_' . B2B_RESERVATION_POST_TYPE . '_posts_custom_column', [ $this, 'render_reservation_column_data' ], 10, 2 );
        
        add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widget' ] );
	}

	/**
	 * Internationalization
	 */
	public function i18n() {
		load_plugin_textdomain( 'plugin-client', false, PUA_DIR . '/languages/' );
	}

	/**
	 * Installer. Runs once when the plugin in activated.
	 *
	 * @since 1.0
	 */
	public function install() {

		if( ! get_option( 'plugin-client_version' ) ){
			update_option( 'plugin-client_version', $this->version );
		}
		
		if( ! get_option( 'plugin-client_install_time' ) ){
			update_option( 'plugin-client_install_time', time() );
		}
	}

	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'PUA_DEBUG' ) && PUA_DEBUG ? '' : '.min';
		
		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/admin{$min}.css", PUA ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/admin{$min}.js", PUA ), [ 'jquery' ], $this->version, true );
	}

	public function footer_text( $text ) {
		if( get_current_screen()->parent_base != $this->slug ) return $text;

		return sprintf( __( 'Built with %1$s by the folks at <a href="%2$s" target="_blank">Codexpert, Inc</a>.' ), '&hearts;', 'https://codexpert.io' );
	}

	public function modal() {
		echo '
		<div id="plugin-client-modal" style="display: none">
			<img id="plugin-client-modal-loader" src="' . esc_attr( PUA_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}

	public function add_custom_user_columns( $columns ) {
        $columns['b2b_phone_number'] 	= 'Phone Number';
        $columns['b2b_commercial_name'] = 'Company Name';
        return $columns;
    }

    public function render_custom_user_column_data( $value, $column_name, $user_id ) {
        switch ( $column_name ) {
            case 'b2b_phone_number':
                return get_user_meta( $user_id, 'b2b_phone_number', true );
            case 'b2b_commercial_name':
                return get_user_meta( $user_id, 'b2b_commercial_name', true );
        }
        return $value;
    }
    
    public function add_reservation_columns( $columns ) {
        $columns['b2b_phone_number'] 	= 'Client Phone';
        $columns['b2b_commercial_name'] = 'Client Company';
        return $columns;
    }

    public function render_reservation_column_data( $column_name, $post_id ) {
        $user_id = get_post_field( 'post_author', $post_id ); // Assuming reservation is created by the client
        if ( !$user_id ) return;

        switch ( $column_name ) {
            case 'b2b_phone_number':
                echo get_user_meta( $user_id, 'b2b_phone_number', true );
                break;
            case 'b2b_commercial_name':
                echo get_user_meta( $user_id, 'b2b_commercial_name', true );
                break;
        }
    }

	public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'b2b_general_metrics_widget',
            'B2B General Metrics',
            [ $this, 'render_dashboard_widget_content' ]
        );
    }

    public function render_dashboard_widget_content() {
        // To prevent caching issues, these queries run every time.
        $client_count 		= count_users()['total_users']; // Or filter by a specific role if needed
        $car_reservations 	= wp_count_posts( B2B_RESERVATION_POST_TYPE )->publish; // Assumes 'publish' status

        echo "<p><strong>Total Clients:</strong> " . intval($client_count) . "</p>";
        echo "<p><strong>Total Car Reservations:</strong> " . intval($car_reservations) . "</p>";
        echo "<p><small>Data refreshed on page load.</small></p>";
    }
}