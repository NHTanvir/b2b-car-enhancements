<?php
/**
 * All public facing functions
 */
namespace Worzen\B2B_Car_Enhancements\App;
use Codexpert\Plugin\Base;
use Worzen\B2B_Car_Enhancements\Helper;
/**
 * if accessed directly, exit.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package Plugin
 * @subpackage Front
 * @author Codexpert <hi@codexpert.io>
 */
class Front extends Base {

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

	public function head() {}
	
	/**
	 * Enqueue JavaScripts and stylesheets
	 */
	public function enqueue_scripts() {
		$min = defined( 'PUA_DEBUG' ) && PUA_DEBUG ? '' : '.min';

		wp_enqueue_style( $this->slug, plugins_url( "/assets/css/front{$min}.css", PUA ), '', $this->version, 'all' );

		wp_enqueue_script( $this->slug . '-cookie', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js' );
		wp_enqueue_script( $this->slug, plugins_url( "/assets/js/front{$min}.js", PUA ), [ 'jquery' ], $this->version, true );
		
		$localized = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'b2b-favorites-nonce' )
		];
		wp_localize_script( $this->slug, 'PUA', apply_filters( "{$this->slug}-localized", $localized ) );
	}

	public function modal() {
		echo '
		<div id="plugin-client-modal" style="display: none">
			<img id="plugin-client-modal-loader" src="' . esc_attr( PUA_ASSET . '/img/loader.gif' ) . '" />
		</div>';
	}
}