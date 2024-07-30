<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://google.com
 * @since             1.0.0
 * @package           Square_Togo_Orders_App
 *
 * @wordpress-plugin
 * Plugin Name:       Square To Go Orders App
 * Plugin URI:        https://ordersdashboard.cmadigital.com
 * Description:       This plugin pulls to-go order data from a square location and provides a corresponding Vue2 frontend with initial data, and ongoing data through a socket connection
 * Version:           1.0.0
 * Author:            Trevor Wagner
 * Author URI:        https://google.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       square-togo-orders-app
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SQUARE_TOGO_ORDERS_APP_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-square-togo-orders-app-activator.php
 */
function activate_square_togo_orders_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-square-togo-orders-app-activator.php';
	Square_Togo_Orders_App_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-square-togo-orders-app-deactivator.php
 */
function deactivate_square_togo_orders_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-square-togo-orders-app-deactivator.php';
	Square_Togo_Orders_App_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_square_togo_orders_app' );
register_deactivation_hook( __FILE__, 'deactivate_square_togo_orders_app' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-square-togo-orders-app.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_square_togo_orders_app() {

	$plugin = new Square_Togo_Orders_App();
	$plugin->run();

}
run_square_togo_orders_app();
