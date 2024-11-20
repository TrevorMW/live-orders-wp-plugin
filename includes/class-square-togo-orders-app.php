<?php

$plugin_name = dirname( __FILE__, 2 ) ;

require_once $plugin_name . '/includes/class-ajax-response.php';
require_once $plugin_name . '/includes/class-utilities.php';
require_once $plugin_name . '/public/class-appuser.php';
require_once $plugin_name . '/includes/class-location.php';
require_once $plugin_name . '/includes/class-system.php';
require_once $plugin_name . '/public/class-order.php';

require_once $plugin_name . '/vendor/autoload.php';

use Square\SquareClient;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://google.com
 * @since      1.0.0
 *
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/includes
 * @author     Trevor Wagner <trevor@cmadigital.com>
 */
class Square_Togo_Orders_App {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Square_Togo_Orders_App_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'SQUARE_TOGO_ORDERS_APP_VERSION' ) ) {
			$this->version = SQUARE_TOGO_ORDERS_APP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'square-togo-orders-app';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Square_Togo_Orders_App_Loader. Orchestrates the hooks of the plugin.
	 * - Square_Togo_Orders_App_i18n. Defines internationalization functionality.
	 * - Square_Togo_Orders_App_Admin. Defines all hooks for the admin area.
	 * - Square_Togo_Orders_App_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-square-togo-orders-app-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-square-togo-orders-app-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-square-togo-orders-app-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-square-togo-orders-app-public.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-restaurant.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-merchant.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-pos-system.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-appuser.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-money.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-oauth.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-order.php';

		$this->loader = new Square_Togo_Orders_App_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Square_Togo_Orders_App_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Square_Togo_Orders_App_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Square_Togo_Orders_App_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action('init', $plugin_admin, 'createPostTypes', 10, 1);
		$this->loader->add_action('admin_init', $plugin_admin, 'createExtraSettings', 20, 1);
		$this->loader->add_action('init', $plugin_admin, 'createAppRoles', 10, 1);

		//$this->loader->add_filter('acf/load_field/name=location_id', $plugin_admin, 'acf_load_color_field_choices', 10, 1);		
		$this->loader->add_filter('acf/load_field/name=pos_system_type', $plugin_admin, 'acf_load_pos_system_choices');		
	}
	

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Square_Togo_Orders_App_Public( $this->get_plugin_name(), $this->get_version() );

		$oAuth = new OAuth();

		// Lets create custom pages for Oauth redirection & callbacks here without creating the pages in the admin.
		$this->loader->add_action('init', $plugin_public, 'addOAuthRewrites', 10, 1);
		$this->loader->add_filter('template_include', $plugin_public, 'addOauthTemplates', 99);

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action('wp_ajax_nopriv_load_global_data', $plugin_public, 'loadGlobalData');		
		$this->loader->add_action('wp_ajax_load_global_data', $plugin_public, 'loadGlobalData');

		$this->loader->add_action('wp_ajax_nopriv_load_data', $plugin_public, 'loadData');
		$this->loader->add_action('wp_ajax_load_data', $plugin_public, 'loadData');

		$this->loader->add_action('wp_ajax_nopriv_recalc_report_data', $plugin_public, 'recalculateReportData');		
		$this->loader->add_action('wp_ajax_recalc_report_data', $plugin_public, 'recalculateReportData');
		

		$appUser = new AppUser();

		$this->loader->add_action('wp_ajax_nopriv_log_user_in', $appUser, 'logUserIn');
		$this->loader->add_action('wp_ajax_log_user_in', $appUser, 'logUserIn');

		$this->loader->add_action('wp_ajax_nopriv_log_user_out', $appUser, 'logUserOut');
		$this->loader->add_action('wp_ajax_log_user_out', $appUser, 'logUserOut');

		$this->loader->add_action('wp_ajax_nopriv_register_user', $appUser, 'registerUser');
		$this->loader->add_action('wp_ajax_log_register_user', $appUser, 'registerUser');

		$this->loader->add_action('wp_ajax_nopriv_reset_password', $appUser, 'resetPassword');
		$this->loader->add_action('wp_ajax_reset_password', $appUser, 'resetPassword');

		$merchant = new Merchant();

		$this->loader->add_action('wp_ajax_nopriv_load_merchant_data', $merchant, 'loadMerchantData');
		$this->loader->add_action('wp_ajax_load_merchant_data', $merchant, 'loadMerchantData');


		$restaurant = new Restaurant();

		$this->loader->add_action('wp_ajax_nopriv_create_new_restaurant', $restaurant, 'createNewRestaurant');
		$this->loader->add_action('wp_ajax_log_create_new_restaurant', $restaurant, 'createNewRestaurant');

		$this->loader->add_action('wp_ajax_nopriv_set_current_restaurant', $restaurant, 'setCurrentRestaurant');		
		$this->loader->add_action('wp_ajax_set_current_restaurant', $restaurant, 'setCurrentRestaurant');
		
		$this->loader->add_action('wp_ajax_nopriv_unset_current_restaurant', $restaurant, 'unsetCurrentRestaurant');		
		$this->loader->add_action('wp_ajax_unset_current_restaurant', $restaurant, 'unsetCurrentRestaurant');

		$this->loader->add_action('wp_ajax_nopriv_load_restaurant_data', $restaurant, 'loadRestaurantData');
		$this->loader->add_action('wp_ajax_load_restaurant_data', $restaurant, 'loadRestaurantData');

		$this->loader->add_action('wp_ajax_nopriv_save_restaurant_settings', $restaurant, 'saveRestaurantSettings');
		$this->loader->add_action('wp_ajax_save_restaurant_settings', $restaurant, 'saveRestaurantSettings');

		$this->loader->add_action('wp_ajax_nopriv_disconnect_oauth', $restaurant, 'disconnectRestaurantOauth');
		$this->loader->add_action('wp_ajax_disconnect_oauth', $restaurant, 'disconnectRestaurantOauth');

		$this->loader->add_action('wp_ajax_nopriv_reauthorize_oauth', $restaurant, 'reauthorizeOauth');
		$this->loader->add_action('wp_ajax_reauthorize_oauth', $restaurant, 'reauthorizeOauth');
		
		$this->loader->add_action('wp_ajax_nopriv_delete_restaurant', $restaurant, 'deleteRestaurant');		
		$this->loader->add_action('wp_ajax_delete_restaurant', $restaurant, 'deleteRestaurant');

		$order = new Order();
		
		$this->loader->add_action('wp_ajax_nopriv_load_orders_data', $order, 'loadOrdersData');		
		$this->loader->add_action('wp_ajax_load_orders_data', $order, 'loadOrdersData');

		$this->loader->add_action('wp_ajax_nopriv_load_order_data', $order, 'loadOrderData');		
		$this->loader->add_action('wp_ajax_load_order_data', $order, 'loadOrderData');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Square_Togo_Orders_App_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
