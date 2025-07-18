<?php
if( ! function_exists( 'get_plugin_data' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
use Worzen\B2B_Car_Enhancements\Helper;

/**
 * Gets the site's base URL
 * 
 * @uses get_bloginfo()
 * 
 * @return string $url the site URL
 */
if( ! function_exists( 'pc_site_url' ) ) :
function pc_site_url() {
	$url = get_bloginfo( 'url' );

	return $url;
}
endif;
add_action('elementor/query/filter_by_post_ids', function($query) {
    if ( isset($_GET['show_favorites']) && $_GET['show_favorites'] == '1' ) {
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $favorites = get_user_meta($user_id, 'b2b_favorites', true);

            if ( is_array($favorites) && !empty($favorites) ) {
                $query->set('post_type', 'voiture');
                $query->set('post__in', $favorites);
                $query->set('orderby', 'post__in');
                $query->set('posts_per_page', -1);
            } else {
                $query->set('post__in', [0]);
            }
        } else {
            $query->set('post__in', [0]);
        }
    }
});
