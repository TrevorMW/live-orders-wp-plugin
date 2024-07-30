<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://google.com
 * @since      1.0.0
 *
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Square_Togo_Orders_App
 * @subpackage Square_Togo_Orders_App/includes
 * @author     Trevor Wagner <trevor@cmadigital.com>
 */
class Square_Togo_Orders_App_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'square-togo-orders-app',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
