<?php
/**
 * Plugin Name: Integrate ConvertKit and WPForms
 * Plugin URI:  https://convertkit.com
 * Description: Create ConvertKit signup forms using WPForms
 * Version:     1.6.3
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
define( 'INTEGRATE_CONVERTKIT_WPFORMS_VERSION', '1.7.0' );
define( 'INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_CLIENT_ID', '147qqKJeENYp5MqgL6AOShDDcLK3UQeClmcIV1ij3gI' );
define( 'INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_REDIRECT_URI', 'https://cktestplugins.wpengine.com/' );

// Load shared classes, if they have not been included by another ConvertKit Plugin.
if ( ! trait_exists( 'ConvertKit_API_Traits' ) ) {
	require_once INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-api-traits.php';
}
if ( ! class_exists( 'ConvertKit_API_V4' ) ) {
	require_once INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-api-v4.php';
}
if ( ! class_exists( 'ConvertKit_Log' ) ) {
	require_once INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-log.php';
}
if ( ! class_exists( 'ConvertKit_Resource_V4' ) ) {
	require_once INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/vendor/convertkit/convertkit-wordpress-libraries/src/class-convertkit-resource-v4.php';
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
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-resource.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-resource-custom-fields.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-resource-forms.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-resource-tags.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms-setup.php';
	require_once plugin_dir_path( __FILE__ ) . '/includes/class-integrate-convertkit-wpforms.php';

}
add_action( 'wpforms_loaded', 'integrate_convertkit_wpforms' );

/**
 * Saves the new access token, refresh token and its expiry when the API
 * class automatically refreshes an outdated access token.
 *
 * @since   1.7.0
 *
 * @param   array  $result                  New Access Token, Refresh Token and Expiry.
 * @param   string $client_id               OAuth Client ID used for the Access and Refresh Tokens.
 * @param   string $previous_access_token   Existing (expired) Access Token.
 */
add_action(
	'convertkit_api_refresh_token',
	function ( $result, $client_id, $previous_access_token ) {

		// Don't save these credentials if they're not for this Client ID.
		// They're for another ConvertKit Plugin that uses OAuth.
		if ( $client_id !== INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_CLIENT_ID ) {
			return;
		}

		// Get all registered providers in WPForms.
		$providers = wpforms_get_providers_options();

		// Bail if no ConvertKit providers were registered.
		if ( ! array_key_exists( 'convertkit', $providers ) ) {
			return;
		}

		// Iterate through providers to find the specific connection containing the now expired Access and Refresh Tokens.
		foreach ( $providers['convertkit'] as $id => $settings ) {
			// Skip if this isn't the connection.
			if ( $settings['access_token'] !== $previous_access_token ) {
				continue;
			}

			// Store the new credentials.
			wpforms_update_providers_options(
				'convertkit',
				array(
					'access_token'  => sanitize_text_field( $result['access_token'] ),
					'refresh_token' => sanitize_text_field( $result['refresh_token'] ),
					'token_expires' => ( $result['created_at'] + $result['expires_in'] ),
					'label'         => $settings['label'],
					'date'          => $settings['date'],
				),
				$id
			);
		}

	},
	10,
	3
);
