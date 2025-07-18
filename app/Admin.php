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
		$columns['user_approved'] 	= 'Approval Status';
    	$columns['approval_action'] = 'Action';

        return $columns;
    }

public function render_custom_user_column_data( $value, $column_name, $user_id ) {
    if ( $column_name === 'b2b_phone_number' ) {
        return get_user_meta( $user_id, 'b2b_phone_number', true );
    } elseif ( $column_name === 'b2b_commercial_name' ) {
        return get_user_meta( $user_id, 'b2b_commercial_name', true );
    } elseif ( $column_name === 'user_approved' ) {
        $approved = get_user_meta( $user_id, 'new_user_approve', true );
        return $approved === 'approved' ? '✅ Approved' : '❌ Pending';
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

	public function bulk_actions( $bulk_actions ) {
		$bulk_actions['approve_users'] = __( 'Approve Users', 'b2b-car-enhancements' );
		$bulk_actions['reject_users']  = __( 'Reject Users', 'b2b-car-enhancements' );
		return $bulk_actions;
	}

	public function handle_bulk_actions( $redirect_to, $doaction, $user_ids ) {
		if ( 'approve_users' === $doaction ) {
			foreach ( $user_ids as $user_id ) {
				update_user_meta( $user_id, 'approval_status', 'approved' );
				update_user_meta( $user_id, 'new_user_approve', 'approved' );
				update_user_meta( $user_id, 'account_status', 'approved' );
			}
			return add_query_arg( 'approved_users', count( $user_ids ), $redirect_to );
		}

		if ( 'reject_users' === $doaction ) {
			foreach ( $user_ids as $user_id ) {
				wp_delete_user( $user_id );
			}
			return add_query_arg( 'rejected_users', count( $user_ids ), $redirect_to );
		}

		return $redirect_to;
	}

	public function handle_individual_action() {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		if ( isset( $_GET['action'], $_GET['user_id'] ) ) {
			$user_id = absint( $_GET['user_id'] );

			if ( 'approve_user' === $_GET['action'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'approve_user_' . $user_id ) ) {
				update_user_meta( $user_id, 'approval_status', 'approved' );
				update_user_meta( $user_id, 'new_user_approve', 'approved' );
				update_user_meta( $user_id, 'account_status', 'approved' );

				wp_safe_redirect(
					add_query_arg(
						'user_approved_single',
						1,
						remove_query_arg( [ 'action', 'user_id', '_wpnonce' ] )
					)
				);
				exit;
			}

			if ( 'reject_user' === $_GET['action'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'reject_user_' . $user_id ) ) {
				wp_delete_user( $user_id );

				wp_safe_redirect(
					add_query_arg(
						'user_rejected_single',
						1,
						remove_query_arg( [ 'action', 'user_id', '_wpnonce' ] )
					)
				);
				exit;
			}
		}
	}

	public function admin_notice() {
		if ( isset( $_GET['user_approved_single'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User approved successfully.', 'b2b-car-enhancements' ) . '</p></div>';
		}

		if ( isset( $_GET['user_rejected_single'] ) ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'User rejected and deleted successfully.', 'b2b-car-enhancements' ) . '</p></div>';
		}
	}
	
}