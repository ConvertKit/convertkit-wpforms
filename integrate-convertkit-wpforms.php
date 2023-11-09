<?php
/**
 * Plugin Name: Integrate ConvertKit and WPForms
 * Plugin URI:  https://convertkit.com
 * Description: Create ConvertKit signup forms using WPForms
 * Version:     1.6.1
 * Author:      ConvertKit
 * Author URI:  https://convertkit.com
 * Text Domain: integrate-convertkit-wpforms
 * Domain Path: /languages
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    Integrate_ConvertKit_WPForms
 * @since      1.0.0
 * @copyright  Copyright (c) 2017, Bill Erickson
 * @license    GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define ConverKit Plugin paths and version number.
define( 'INTEGRATE_CONVERTKIT_WPFORMS_NAME', 'ConvertKitWPForms' ); // Used for user-agent in API class.
define( 'INTEGRATE_CONVERTKIT_WPFORMS_FILE', plugin_basename( __FILE__ ) );
define( 'INTEGRATE_CONVERTKIT_WPFORMS_URL', plugin_dir_url( __FILE__ ) );
define( 'INTEGRATE_CONVERTKIT_WPFORMS_PATH', __DIR__ );
define( 'INTEGRATE_CONVERTKIT_WPFORMS_VERSION', '1.6.1' );

// Load shared classes, if they have not been included by another ConvertKit Plugin.
if ( ! class_exists( 'ConvertKit_API' ) ) {
	require_once INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-api.php';
}
if ( ! class_exists( 'ConvertKit_Log' ) ) {
	require_once INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-log.php';
}
if ( ! class_exists( 'ConvertKit_Review_Request' ) ) {
	require_once INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-review-request.php';
}

/**
 * Load the class
 */
function integrate_convertkit_wpforms() {

	load_plugin_textdomain( 'integrate-convertkit-wpforms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-api.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-creator-network-recommendations.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-setup.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms.php';

}
add_action( 'wpforms_loaded', 'integrate_convertkit_wpforms' );
