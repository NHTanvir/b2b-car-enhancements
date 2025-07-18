<?php
/**
 * All AJAX related functions
 */
namespace Worzen\B2B_Car_Enhancements\App;
use Codexpert\Plugin\Base;

/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage AJAX
 * @author Codexpert <hi@codexpert.io>
 */
class AJAX extends Base {

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

	public function handle_toggle_favorite_ajax() {
        check_ajax_referer( 'b2b-favorites-nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'You must be logged in to add favorites.' ] );
        }

        $post_id    = intval( $_POST['post_id'] );
        $user_id    = get_current_user_id();
        $favorites  = get_user_meta( $user_id, 'b2b_favorites', true );
        if ( ! is_array( $favorites ) ) {
            $favorites = [];
        }

        if ( in_array( $post_id, $favorites ) ) {
            // Remove from favorites
            $favorites = array_diff( $favorites, [ $post_id ] );
            $is_favorite = false;
        } else {
            // Add to favorites
            $favorites[] = $post_id;
            $is_favorite = true;
        }

        update_user_meta( $user_id, 'b2b_favorites', $favorites );

        wp_send_json_success( [
            'is_favorite'   => $is_favorite,
            'text'          => $is_favorite ? '★ Remove from Favorites' : '☆ Add to Favorites'
        ] );
    }
}