<?php

$plugin_name = dirname( __FILE__, 2 ) ;

require_once $plugin_name . '/includes/class-ajax-response.php';
require_once $plugin_name . '/includes/class-utilities.php';
require_once $plugin_name . '/public/class-pos-system.php';
require_once $plugin_name . '/public/class-oauth.php';

require_once $plugin_name . '/vendor/autoload.php';

use Square\SquareClient;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://google.com
 * @since      1.0.0
 *
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/public
 * @author     Trevor Wagner <trevor@cmadigital.com>
 */
class Square_Togo_Orders_App_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Square_Togo_Orders_App_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Square_Togo_Orders_App_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/square-togo-orders-app-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Square_Togo_Orders_App_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Square_Togo_Orders_App_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/square-togo-orders-app-public.js', array( 'jquery' ), $this->version, false );

	}

	public function addOAuthRewrites()
	{	
		add_rewrite_rule('^oauth?$', 'index.php?pagename=oauth', 'bottom');
		add_rewrite_rule('^callback?$', 'index.php?pagename=callback', 'bottom');
	}

	public function addOauthTemplates($template){
		global $wp;
        if ( stripos( $wp->request, 'oauth' ) !== false ) {
            $custom_template = locate_template('page-oauth.php');
    
			if ( locate_template( 'page-oauth.php' ) ) {
				$template = locate_template( 'page-oauth.php' );
			} else {
				// Template not found in theme's folder, use plugin's template as a fallback
				$template = dirname( __FILE__ ) . '/templates/' . 'page-oauth.php';
			}
        }

		if ( stripos( $wp->request, 'callback' ) !== false ) {    
			if ( locate_template( 'page-callback.php' ) ) {
				$template = locate_template( 'page-callback.php' );
			} else {
				// Template not found in theme's folder, use plugin's template as a fallback
				$template = dirname( __FILE__ ) . '/templates/' . 'page-callback.php';
			}
        }
        return $template;
	}

	public function loadGlobalData()
	{	
		$user = wp_get_current_user();
		$settingsID = (int) get_option('page_for_settings');

		$userDeets = get_user_meta($user->data->ID);
		$userID = $user->data->ID;
		$merchants = get_field('connected_merchant_accounts', 'user_' . $userID);
		$rest = get_field('current_restaurant', 'user_' . $user->ID);

		$data = array(
			'isLoggedIn' => is_user_logged_in(),
			'logoutURL' => wp_logout_url(home_url() . '&customer-logout=true'),
			'imageURL' => get_template_directory_uri() . '/assets/images/',
			'baseURL' => site_url(),
			'user' => array(
				'username' => $user->data->display_name,
				'firstName' => $userDeets['first_name'][0],
				'lastName' => $userDeets['last_name'][0],
				'login' => $user->data->user_login,
				'email' => $user->data->user_email,
				'id' => $user->data->ID,
				'isAdmin' => true,
				'isSuperAdmin' => false
			),
			'posSystems' => [],
			'isRestaurantSet' => false,
			'currentRestaurant' => null,
			'merchantAccounts' => [],
			'restaurants' => [],
			'endpoints' => array(
				'login' => array(
					'action' => 'log_user_in',
					'nonce' => wp_create_nonce('log_user_in')
				),
				'register' => array(
					'action' => 'register_user',
					'nonce' => wp_create_nonce('register_user')
				),
				'password' => array(
					'action' => 'reset_password',
					'nonce' => wp_create_nonce('reset_password')
				),
				'logout' => array(
					'action' => 'log_user_out',
					'nonce' => wp_create_nonce('log_user_out')
				),
				'set_current_restaurant' => array(
					'action' => 'set_current_restaurant',
					'nonce' => wp_create_nonce('set_current_restaurant')
				),
				'unset_current_restaurant' => array(
					'action' => 'unset_current_restaurant',
					'nonce' => wp_create_nonce('unset_current_restaurant')
				),
				'save_restaurant_settings' => array(
					'action' => 'save_restaurant_settings',
					'nonce' => wp_create_nonce('save_restaurant_settings')
				),
				'disconnect_oauth' => array(
					'action' => 'disconnect_oauth',
					'nonce' => wp_create_nonce('disconnect_oauth')
				),
				'reauthorize_oauth' => array(
					'action' => 'reauthorize_oauth',
					'nonce' => wp_create_nonce('reauthorize_oauth')
				),
				'delete_restaurant' => array(
					'action' => 'delete_restaurant',
					'nonce' => wp_create_nonce('delete_restaurant')
				),
				'load_merchant_data' => array(
					'action' => 'load_merchant_data',
					'nonce' => wp_create_nonce('load_merchant_data')
				)
			)
		);

		$systems = get_field('supported_pos_systems', $settingsID);

		if ($systems) {
			foreach ($systems as $k => $system) {
				$pos = new POS_System($system->ID);
				$link = null;

				$data['posSystems'][$k]['authorizeLink'] = $link;

				$data['posSystems'][$k] = array(
					'ID' => $pos->ID,
					'name' => $pos->name,
					'type' => strtolower($pos->name),
					'enabled' => $pos->enabled,
					'active' => $pos->active,
					'icon' => $pos->icon,
					'authorizeLink' => ''
				);

				if (strtolower($pos->name) === 'square') {
					$data['posSystems'][$k]['authorizeLink'] = OAuth::getSquareOauthAuthorizeLink(null, array('userID' => $user->data->ID));
				}

				// if($system['value'] === 'toast'){
				// 	$link = '';
				// }

				// if($system['value'] === 'clover'){
				// 	$link = '';
				// }
			}
		}

		if (is_array($merchants) && count($merchants) > 0) {
			foreach ($merchants as $k => $merch) {
				$oauth = new OAuth();
				$conn = $oauth->testOauthConnection($merch->ID);

				$merchant = new Merchant($merch->ID);

				$data['merchantAccounts'][] = array(
					'id' => $merch->ID,
					'name' => html_entity_decode($merch->post_title),
					'type' => $merchant->posType,
					'expires_on' => Utilities::convertDateToUserFriendly($merchant->tokenExpiresAt),
					'connected' => $conn['connected'],
				);
			}
		}

		if ($rest) {
			$post = get_post((int) $rest);

			if ($post instanceof WP_Post) {
				$data['isRestaurantSet'] = true;
				$data['currentRestaurant'] = $post;
				$data['currentRestaurant']->post_title = html_entity_decode($post->post_title);
			}
		}

		echo json_encode($data);

		die(0);
	}

}
