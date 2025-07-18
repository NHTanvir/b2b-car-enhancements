<?php
/**
 * All common functions to load in both admin and front
 */
namespace Worzen\B2B_Car_Enhancements\App;
use Worzen\B2B_Car_Enhancements\Helper;
use Codexpert\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Common
 * @author Codexpert <hi@codexpert.io>
 */
class Common extends Base {

	public $plugin;

	/**
	 * Constructor function
	 */
	public function __construct( $plugin ) {
		$this->plugin	= $plugin;
		$this->slug		= $this->plugin['TextDomain'];
		$this->name		= $this->plugin['Name'];
		$this->version	= $this->plugin['Version'];
	}

	public function user_register( $user_id, $userdata ) {

		$subject = Helper::get_option( 'b2b_email', 'admin_email_subject' );
		$_notice = Helper::get_option( 'b2b_email', 'admin_email_notice' );

		if ( isset( $_POST['b2b_phone_number'] ) ) {
			update_user_meta( $user_id, 'b2b_phone_number', sanitize_text_field( $_POST['b2b_phone_number'] ) );
		}

		if ( isset( $_POST['b2b_vat_number'] ) ) {
			update_user_meta( $user_id, 'b2b_vat_number', sanitize_text_field( $_POST['b2b_vat_number'] ) );
		}

		if ( isset( $_POST['b2b_commercial_name'] ) ) {
			update_user_meta( $user_id, 'b2b_commercial_name', sanitize_text_field( $_POST['b2b_commercial_name'] ) );
		}

		update_user_meta( $user_id, 'new_user_approve', '0' );

		// ✅ Set cookie for 30 days
		setcookie( 'b2b_pending_approval', $user_id, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );

		// Email part
		$admin_email = get_option( 'admin_email' );
		$user = get_userdata( $user_id );
		$placeholders = [ '%user%', '%username%', '%display_name%' ];
		$replacements = [ $user->user_email, $user->user_login, $user->display_name ];
		$notice = str_replace( $placeholders, $replacements, $_notice );

		wp_mail( $admin_email, $subject, $notice );
	}

	public function is_profile_complete( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        if ( ! $user_id ) {
            return false;
        }

        $phone 		= get_user_meta( $user_id, 'b2b_phone_number', true );
        $vat 		= get_user_meta( $user_id, 'b2b_vat_number', true );
        $company 	= get_user_meta( $user_id, 'b2b_commercial_name', true );

        return ! empty( $phone ) && ! empty( $vat ) && ! empty( $company );
    }

    public function redirect_to_complete_profile() {
        if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
            if ( ! $this->is_profile_complete() ) {

	        	$b2b_profile = Helper::get_option( 'b2b_basic', 'b2b_profile' );
	        	wp_redirect( get_permalink( $b2b_profile ) );
	            exit;
            }
        }
    }

    public function extend_auth_cookie_duration( $expiration, $user_id, $remember ) {
        return 14 * DAY_IN_SECONDS; // 14 days
    }

    public function protect_car_content() {
        if ( is_singular( B2B_CAR_POST_TYPE ) || is_post_type_archive( B2B_CAR_POST_TYPE ) ) {
            if ( ! is_user_logged_in() ) {
                auth_redirect();
            }
        }
    }
}