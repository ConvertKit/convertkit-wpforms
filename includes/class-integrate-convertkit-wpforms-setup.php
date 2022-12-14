<?php
/**
 * Plugin activation, update and deactivation class.
 *
 * @package Integrate_ConvertKit_WPForms
 * @author  ConvertKit
 */

/**
 * Runs any steps required on plugin activation, update and deactivation.
 *
 * @package Integrate_ConvertKit_WPForms
 * @author  ConvertKit
 * @version 1.5.0
 */
class Integrate_ConvertKit_WPForms_Setup {

	/**
	 * Runs routines when the Plugin version has been updated.
	 *
	 * @since   1.5.0
	 */
	public function update() {

		// Get installed Plugin version.
		$current_version = get_option( 'integrate_convertkit_wpforms_version' );

		// If the version number matches the plugin version, no update routines
		// need to run.
		if ( $current_version === INTEGRATE_CONVERTKIT_WPFORMS_VERSION ) {
			return;
		}

		/**
		 * 1.5.0: Migrate API settings from individual forms to integrations.
		 */
		if ( ! $current_version || version_compare( $current_version, '1.5.0', '<' ) ) {
			$this->migrate_settings_from_forms_to_integrations();
		}

		// Update the installed version number in the options table.
		update_option( 'integrate_convertkit_wpforms_version', INTEGRATE_CONVERTKIT_WPFORMS_VERSION );

	}

	/**
	 * 1.5.0+: For each WPForms Form that has ConvertKit settings defined (API Key, Form ID etc), migrate
	 * those settings to a provider connection (WPForms > ), and configures the WPForms Form to use the
	 * integration connection instead of the form settings.
	 *
	 * @since   1.5.0
	 */
	private function migrate_settings_from_forms_to_integrations() {

		// Bail if the WPForms handler class isn't available.
		if ( ! class_exists( 'WPForms_Form_Handler' ) ) {
			return;
		}

		// Get all forms.
		$form_handler = new WPForms_Form_Handler();
		$forms        = $form_handler->get();

		// Bail if no WPForms Forms exist.
		if ( ! is_array( $forms ) ) {
			return;
		}
		if ( count( $forms ) === 0 ) {
			return;
		}

		// For each form, inspect its settings to determine if < 1.5.0 ConvertKit settings exist in the Form.
		foreach ( $forms as $form ) {
			// Decode settings into array.
			$data = wpforms_decode( $form->post_content );

			// Skip if this Form already has a ConvertKit provider configured.
			// This shouldn't be the case for upgrades to 1.5.0+, but it's a useful
			// sanity check.
			if ( array_key_exists( 'providers', $data ) && array_key_exists( 'convertkit', $data['providers'] ) ) {
				continue;
			}

			// Bail if the API Key or Form ID settings don't exist or are empty.
			if ( ! array_key_exists( 'be_convertkit_api', $data['settings'] ) ) {
				continue;
			}
			if ( empty( $data['settings']['be_convertkit_api'] ) ) {
				continue;
			}
			if ( ! array_key_exists( 'be_convertkit_form_id', $data['settings'] ) ) {
				continue;
			}
			if ( empty( $data['settings']['be_convertkit_form_id'] ) ) {
				continue;
			}

			// Search WPForms provider accounts to see if an account exists for this ConvertKit API Key.
			$account_id = $this->wpforms_get_provider_account_by_api_key( $data['settings']['be_convertkit_api'] );

			// If no account exists, register a new ConvertKit provider account using this API Key.
			if ( ! $account_id ) {
				$account_id = $this->wpforms_register_account( $data['settings']['be_convertkit_api'] );
			}

			// Define the ConvertKit provider settings in the form.
			$data['providers']['convertkit'] = array(
				'connection_' . uniqid() => array(
					'connection_name' => 'ConvertKit',
					'account_id'      => $account_id,
					'list_id'         => $data['settings']['be_convertkit_form_id'],
					'fields'          => array(
						'email' => $data['settings']['be_convertkit_field_email'] . '.value.email',
						'name'  => $data['settings']['be_convertkit_field_first_name'] . '.value.text',
					),
				),
			);

			// Remove deprecated ConvertKit form settings.
			unset(
				$data['settings']['be_convertkit_api'],
				$data['settings']['be_convertkit_form_id'],
				$data['settings']['be_convertkit_field_first_name'],
				$data['settings']['be_convertkit_field_email']
			);

			// Save data back to the form.
			$form_handler->update( $form->ID, $data );
		}

	}

	/**
	 * Returns the account ID for the ConvertKit provider registered in WPForms
	 * for the given ConvertKit API Key.
	 *
	 * Returns false if no provider is registered with the given API Key.
	 *
	 * @since   1.5.0
	 *
	 * @param   string $api_key    ConvertKit API Key.
	 * @return  bool|string         false | Account ID.
	 */
	private function wpforms_get_provider_account_by_api_key( $api_key ) {

		// Get all ConvertKit connections that are registered.
		$connections = wpforms_get_providers_options( 'convertkit' );

		// If no existing connections exist, register one now and return its ID.
		if ( ! $connections ) {
			return false;
		}

		foreach ( $connections as $id => $connection ) {
			// If the connection's API Key matches the one we want to register a connection for,
			// we can use the existing connection.
			if ( $connection['api_key'] === $api_key ) {
				return $id;
			}
		}

		// If here, we have no existing ConvertKit connection for this API Key.
		return false;

	}

	/**
	 * Returns a ConvertKit provider account in WPForms for the given ConvertKit API Key.
	 *
	 * @since   1.5.0
	 *
	 * @param   string $api_key    ConvertKit API Key.
	 * @return  string              Account ID
	 */
	private function wpforms_register_account( $api_key ) {

		// wpforms_update_providers_options() doesn't return the generated ID
		// for a new provider account, so we mimic how they generate an ID
		// and return it.
		$id = uniqid();

		wpforms_update_providers_options(
			'convertkit',
			array(
				'api_key'    => $api_key,
				'api_secret' => '',
				'label'      => 'ConvertKit',
				'date'       => strtotime( 'now' ),
			),
			$id
		);

		return $id;

	}

}
