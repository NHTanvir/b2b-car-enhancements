<?php
/**
 * All settings related functions
 */
namespace Worzen\B2B_Car_Enhancements\App;
use Worzen\B2B_Car_Enhancements\Helper;
use Codexpert\Plugin\Base;
use Codexpert\Plugin\Settings as Settings_API;

/**
 * @package Plugin
 * @subpackage Settings
 * @author Codexpert <hi@codexpert.io>
 */
class Settings extends Base {

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
	
	public function init_menu() {

		$settings = [
			'id'            => $this->slug,
			'label'         => $this->name,
			'title'         => "{$this->name} v{$this->version}",
			'header'        => $this->name,
			// 'parent'     => 'woocommerce',
			// 'priority'   => 10,
			// 'capability' => 'manage_options',
			// 'icon'       => 'dashicons-wordpress',
			// 'position'   => 25,
			// 'topnav'	=> true,
			'sections'      => [
				'b2b_basic'	=> [
					'id'        => 'b2b_basic',
					'label'     => __( 'Basic Settings', 'plugin-client' ),
					'icon'      => 'dashicons-admin-tools',
					// 'color'		=> '#4c3f93',
					'sticky'	=> false,
					'fields'    => [
						'b2b_profile' => [
							'id'      => 'b2b_profile',
							'label'     => __( 'Profile', 'mobile-sign-delivery' ),
							'type'      => 'select',
							'desc'      => __( 'Select your profile page', 'mobile-sign-delivery' ),
							// 'class'     => '',
							'options'   => Helper::get_posts( [ 'post_type' => 'page' ], false, true ),
							// 'default'   => 2,
							'disabled'  => false, // true|false
							'multiple'  => false, // true|false
							'select2'    => true
						],
					]
				],
				'b2b_email'	=> [
					'id'        => 'b2b_email',
					'label'     => __( 'Email Settings', 'plugin-client' ),
					'icon'      => 'dashicons-admin-tools',
					// 'color'		=> '#4c3f93',
					'sticky'	=> false,
					'fields'    => [
						'admin_email_subject' => [
							'id'      => 'admin_email_subject',
							'label'     => __( 'Email Subject', 'plugin-client' ),
							'type'      => 'text',
							// 'class'     => '',
							'default'       => 'New User Registration Pending Approval'
						],
						'admin_email_notice' => [
							'id'      => 'admin_email_notice',
							'label'     => __( 'Email Body', 'plugin-client' ),
							'type'      => 'wysiwyg',
							'desc'      => __( "Admin email notice when user registration. Placeholder: %user%, %username%, %display_name%", 'plugin-client' ),
							// 'class'     => '',
							'width'     => '100%',
							'rows'      => 5,
							'teeny'     => true,
							'text_mode'     => false, // true|false
							'media_buttons' => false, // true|false
							'default'       => 'A new user %display_name% has registered and is pending approval.'
						],
					]
				],
			],
		];

		new Settings_API( $settings );
	}
}