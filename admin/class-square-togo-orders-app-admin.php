<?php

$plugin_name = dirname(__FILE__, 2);

require_once $plugin_name . '/includes/class-ajax-response.php';
require_once $plugin_name . '/includes/class-utilities.php';

require_once $plugin_name . '/vendor/autoload.php';

use Square\SquareClient;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://google.com
 * @since      1.0.0
 *
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/admin
 * @author     Trevor Wagner <trevor@cmadigital.com>
 */
class Square_Togo_Orders_App_Admin
{

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

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

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/square-togo-orders-app-admin.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

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

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/square-togo-orders-app-admin.js', array('jquery'), $this->version, false);

	}

	public function acf_load_pos_system_choices( $field )
	{
		// Reset choices
		$field['choices'] = array(
			'square' => 'Square',
			'toast' => 'Toast',
			'clover' => 'Clover',
		);

		// Return the field data
		return $field;

	}


	public function createAppRoles()
	{
		$admin_role = get_role('contributor');
		$admin_capabilities = $admin_role->capabilities;
		add_role(
			'appclient',
			'Application Client',
			$admin_capabilities
		);
	}

	/**
	 * 
	 */
	public function createPostTypes()
	{
		$allTypes = [
			'restaurant' => array(
				'icon' => 'dashicons-food'				
			),
			'report' => array(
				'icon' => 'dashicons-text-page'	
			),
			'merchant' => array(
				'icon' => 'dashicons-store'
			),
			'POS' => array(
				'icon' => 'dashicons-calculator'
			),
		];

		foreach ($allTypes as $k => $typeData) {
			$singular = $k;
			$plural   = $singular . 's';

			// Create Hotels Post Type
			if (!post_type_exists($singular)) {

				$publiclyQueryable = true;
				$searchExclude 	   = false;

				register_post_type(
					$singular,
					array(
						'label'                 => __(ucfirst($singular), 'text_domain'),
						'description'           => __('Custom Post Types for all ' . $singular . ' based details', 'text_domain'),
						'labels'                => array(
							'name'                  => _x(ucfirst($plural), 'Post Type General Name', 'text_domain'),
							'singular_name'         => _x(ucfirst($singular), 'Post Type Singular Name', 'text_domain'),
							'menu_name'             => __(ucfirst($plural), 'text_domain'),
							'name_admin_bar'        => __(ucfirst($plural), 'text_domain'),
							'archives'              => __(ucfirst($singular) . ' Archives', 'text_domain'),
							'attributes'            => __(ucfirst($singular) . ' Attributes', 'text_domain'),
							'parent_item_colon'     => __('Parent ' . ucfirst($singular) . ':', 'text_domain'),
							'all_items'             => __('All ' . ucfirst($plural), 'text_domain'),
							'add_new_item'          => __('Add New ' . ucfirst($singular), 'text_domain'),
							'add_new'               => __('Add New ' . ucfirst($singular), 'text_domain'),
							'new_item'              => __('New ' . ucfirst($singular), 'text_domain'),
							'edit_item'             => __('Edit ' . ucfirst($singular), 'text_domain'),
							'update_item'           => __('Update ' . ucfirst($singular), 'text_domain'),
							'view_item'             => __('View ' . ucfirst($singular), 'text_domain'),
							'view_items'            => __('View ' . ucfirst($singular), 'text_domain'),
							'search_items'          => __('Search ' . ucfirst($plural), 'text_domain'),
							'not_found'             => __(ucfirst($singular) . ' Not found', 'text_domain'),
							'not_found_in_trash'    => __(ucfirst($singular) . ' Not found in Trash', 'text_domain'),
							'featured_image'        => __('Featured Image', 'text_domain'),
							'set_featured_image'    => __('Set featured image', 'text_domain'),
							'remove_featured_image' => __('Remove featured image', 'text_domain'),
							'use_featured_image'    => __('Use as featured image', 'text_domain'),
							'insert_into_item'      => __('Insert into ' . ucfirst($singular), 'text_domain'),
							'uploaded_to_this_item' => __('Uploaded to this ' . ucfirst($singular), 'text_domain'),
							'items_list'            => __(ucfirst($plural) . ' list', 'text_domain'),
							'items_list_navigation' => __(ucfirst($plural) . ' list navigation', 'text_domain'),
							'filter_items_list'     => __('Filter ' . ucfirst($plural) . ' list', 'text_domain'),
						),
						'supports'              => array('title', 'editor', 'thumbnail'),
						'hierarchical'          => false,
						'public'                => true,
						'show_ui'               => true,
						'show_in_menu'          => true,
						'menu_position'         => 10,
						'menu_icon'             => $typeData['icon'],
						'show_in_admin_bar'     => true,
						'show_in_nav_menus'     => true,
						'can_export'            => true,
						'has_archive'           => true,
						'exclude_from_search'   => $searchExclude,
						'publicly_queryable'    => $publiclyQueryable,
						'capability_type'       => 'page',
					)
				);
			}
		}
	}

	/**
	 *  Creates extra settings select fields in the "Reading" page. 
	 *  Here we define a global "settings" page so we can attach ACF data to it.
	 */
	public function createExtraSettings()
	{

		function settingsPageCallback()
		{
			// get saved project page ID
			$project_page_id = get_option('page_for_settings');

			// get all pages
			$args = array(
				'posts_per_page' => -1,
				'orderby' => 'name',
				'order' => 'ASC',
				'post_type' => 'page',
			);

			$items = get_posts($args);

			echo '<select id="settingsPageSelect" name="page_for_settings">';
			// empty option as default
			echo '<option value="0">' . __('— Select —', 'wordpress') . '</option>';

			// foreach page we create an option element, with the post-ID as value
			foreach ($items as $item) {

				// add selected to the option if value is the same as $project_page_id
				$selected = ($project_page_id == $item->ID) ? 'selected="selected"' : '';

				echo '<option value="' . $item->ID . '" ' . $selected . '>' . $item->post_title . '</option>';
			}

			echo '</select>';
		}

		// register our setting
		register_setting(
			'reading', // option group "reading", default WP group
			'page_for_settings', // option name
			array(
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => NULL
			)
		);

		// add our new setting
		add_settings_field(
			'page_for_settings', // ID
			__('Theme Settings Page', 'textdomain'), // Title
			'settingsPageCallback', // Callback
			'reading', // page
			'default', // section
			array('label_for' => 'page_for_settings')
		);
	}

	/**
	 *  Sets the proper data in the settings select fields.
	 */
	// public function settingsPagePostStates($states) {
	// 	global $post;

	// 	// get saved project page ID
	// 	$project_page_id = get_option('page_for_settings');

	// 	// add our custom state after the post title only,
	// 	// if post-type is "page",
	// 	// "$post->ID" matches the "$project_page_id",
	// 	// and "$project_page_id" is not "0"
	// 	if( 'page' == get_post_type($post->ID) && $post->ID == $project_page_id && $project_page_id != '0') {
	// 		$states[] = __('Global Settings Page', 'textdomain');
	// 	}

	// 	// get saved project page ID
	// 	$id = get_option('page_for_quiz');

	// 	// add our custom state after the post title only,
	// 	// if post-type is "page",
	// 	// "$post->ID" matches the "$project_page_id",
	// 	// and "$project_page_id" is not "0"
	// 	if( 'page' == get_post_type($post->ID) && $post->ID == $id && $id != '0') {
	// 		$states[] = __('Quiz Page', 'textdomain');
	// 	}

	// 	return $states;
	// }

	/**
	 * Lets import the ID of the current restaurant we are connecting, no matter the POS system.
	 */
	function acf_load_color_field_choices($field)
	{
		global $post;

		// Reset choices
		$field['choices'] = array();

		// grab the type of POS system so we know what type of API call to make
		$posType = get_field('pos_system_type', $post->ID);

		// if we have square, make an API call to the square API
		if ($posType['value'] === 'square') {

			$token = get_field('square_api_access_token', $post->ID);

			$client = new SquareClient([
				'accessToken' => $token,
				'environment' => 'production',
			]);

			try {
				$locationsApi = $client->getLocationsApi();
				$apiResponse = $locationsApi->listLocations();

				if ($apiResponse->isSuccess()) {
					$listLocationsResponse = $apiResponse->getResult();

					$locations = $listLocationsResponse->getLocations();

					foreach ($locations as $location) {
						$field['choices'][$location->getID()] = $location->getName();
					}
				} else {
					$errors = $apiResponse->getErrors();
				}
			} catch (Exception $e) {
				var_dump($e);
			}
		}

		// Return the field data
		return $field;
	}

}
