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
 * @package           Service_Charge_App
 *
 * @wordpress-plugin
 * Plugin Name:       Service Charge App
 * Plugin URI:        https://google.com
 * Description:       Creates a connection to the Square API to pull restaurant employee info.
 * Version:           1.0.0
 * Author:            Trevor
 * Author URI:        https://google.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       service-charge-app
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
define( 'SERVICE_CHARGE_APP_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-service-charge-app-activator.php
 */
function activate_service_charge_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-service-charge-app-activator.php';
	Service_Charge_App_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-service-charge-app-deactivator.php
 */
function deactivate_service_charge_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-service-charge-app-deactivator.php';
	Service_Charge_App_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_service_charge_app' );
register_deactivation_hook( __FILE__, 'deactivate_service_charge_app' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-service-charge-app.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_service_charge_app() {

	$plugin = new Service_Charge_App();
	$plugin->run();

}
run_service_charge_app();
