<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the WPForms Plugin,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.4.0
 */
class WPForms extends \Codeception\Module
{
	/**
	 * Helper method to setup a ConvertKit integration in WPForms with a valid API Key and Secret
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I  AcceptanceTester.
	 */
	public function setupWPFormsIntegration($I)
	{
		$I->haveOptionInDatabase(
			'wpforms_providers',
			[
				'convertkit' => [
					'63725bdcceea3' => [
						'api_key'    => $_ENV['CONVERTKIT_API_KEY'],
						'api_secret' => $_ENV['CONVERTKIT_API_SECRET'],
						'label'      => 'ConvertKit',
						'date'       => strtotime('now'),
					],
				],
			]
		);
	}

	/**
	 * Helper method to check that a ConvertKit integration is registered as a provider with the given
	 * API Key and Secret
	 *
	 * @since   1.5.8
	 *
	 * @param   AcceptanceTester $I         AcceptanceTester.
	 * @param   string           $apiKey    API Key.
	 * @param   string           $apiSecret API Secret.
	 */
	public function checkWPFormsIntegrationExists($I, $apiKey, $apiSecret)
	{
		$providers = $I->grabOptionFromDatabase('wpforms_providers');
		foreach ($providers['convertkit'] as $provider) {
			if ($provider['api_key'] === $apiKey && $provider['api_secret'] === $apiSecret) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Creates a WPForms Form with ConvertKit Settings, as if it were created
	 * in 1.4.1 or older.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I AcceptanceTester.
	 * @return  int                 Form ID.
	 */
	public function createWPFormsFormForMigration($I)
	{
		// Create Form, as if it were created with this Plugin < 1.5.0.
		return $I->havePostInDatabase(
			[
				'post_type'    => 'wpforms',
				'post_status'  => 'publish',
				'post_title'   => 'Migrate form',
				'post_name'    => 'migrate-form',
				'post_content' => json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions
					array(
						'fields'   => array(
							array(
								'id'                 => '0',
								'type'               => 'name',
								'label'              => 'Name',
								'format'             => 'first-last',
								'description'        => '',
								'required'           => '1',
								'size'               => 'medium',
								'simple_placeholder' => '',
								'simple_default'     => '',
								'first_placeholder'  => '',
								'first_default'      => '',
								'middle_placeholder' => '',
								'middle_default'     => '',
								'last_placeholder'   => '',
								'last_default'       => '',
								'css'                => '',
							),
							array(
								'id'                       => '1',
								'type'                     => 'email',
								'label'                    => 'Email',
								'description'              => '',
								'required'                 => '1',
								'size'                     => 'medium',
								'placeholder'              => '',
								'confirmation_placeholder' => '',
								'default_value'            => false,
								'filter_type'              => '',
								'allowlist'                => '',
								'denylist'                 => '',
								'css'                      => '',
							),
							array(
								'id'            => '2',
								'type'          => 'textarea',
								'label'         => 'Comment or Message',
								'description'   => '',
								'size'          => 'medium',
								'placeholder'   => '',
								'limit_count'   => '1',
								'limit_mode'    => 'characters',
								'default_value' => '',
								'css'           => '',
							),
							array(
								'id'            => '3',
								'type'          => 'text',
								'label'         => 'Tag ID',
								'description'   => '',
								'size'          => 'medium',
								'placeholder'   => '',
								'limit_count'   => '1',
								'limit_mode'    => 'characters',
								'default_value' => '',
								'input_mask'    => '',
								'css'           => '',
							),
						),
						'id'       => '2',
						'field_id' => 4,
						'settings' => array(
							'be_convertkit_api'         => $_ENV['CONVERTKIT_API_KEY'],
							'be_convertkit_form_id'     => $_ENV['CONVERTKIT_API_FORM_ID'],
							'be_convertkit_field_first_name' => '0',
							'be_convertkit_field_email' => '1',
							'form_title'                => 'Simple Contact Form',
							'form_desc'                 => '',
							'submit_text'               => 'Submit',
							'submit_text_processing'    => 'Sending...',
							'form_class'                => '',
							'submit_class'              => '',
							'ajax_submit'               => '1',
							'notification_enable'       => '1',
							'notifications'             => array(
								1 => array(
									'email'          => '{admin_email}',
									'subject'        => 'New Entry: Simple Contact Form',
									'sender_name'    => 'convertkit',
									'sender_address' => '{admin_email}',
									'replyto'        => '{field_id="1"}',
									'message'        => '{all_fields}',
								),
							),
							'confirmations'             => array(
								1 => array(
									'type'           => 'message',
									'message'        => '<p>Thanks for contacting us! We will be in touch with you shortly.</p>',
									'message_scroll' => '1',
									'redirect'       => '',
								),
							),
							'antispam'                  => '1',
							'form_tags'                 => array(),
						),
						'meta'     => array(
							'template' => 'simple-contact-form-template',
						),
					)
				),
			]
		);
	}

	/**
	 * Creates a WPForms Form.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I          AcceptanceTester.
	 * @param   bool|array       $tagValues  Array of values for tag checkbox (Tag IDs or Tag names).
	 * @return  int                          Form ID.
	 */
	public function createWPFormsForm($I, $tagValues = false)
	{
		// Define settings in options table so the first time wizard in WPForms doesn't display.
		$I->haveOptionInDatabase(
			'wpforms_challenge',
			[
				'status'              => 'skipped',
				'step'                => 0,
				'user_id'             => 1,
				'form_id'             => 0,
				'embed_page'          => 0,
				'embed_page_title'    => '',
				'started_date_gmt'    => '',
				'finished_date_gmt'   => '',
				'seconds_spent'       => 0,
				'seconds_left'        => 0,
				'feedback_sent'       => false,
				'feedback_contact_me' => false,
				'window_closed'       => '',
			]
		);

		// Navigate to Forms > Add New.
		$I->amOnAdminPage('admin.php?page=wpforms-builder&view=setup');

		// Select a Template.
		$I->waitForElementVisible('#wpforms-setup-templates-list');
		$I->moveMouseOver('#wpforms-template-simple-contact-form-template');
		$I->click('#wpforms-template-simple-contact-form-template a.wpforms-template-select');

		// Add Tag Field as checkboxes or text field, depending on whether tag values are specified.
		if ($tagValues) {
			// Use checkbox field.

			// Wait for form editor to load.
			$I->waitForElementVisible('button#wpforms-add-fields-checkbox');

			$I->click('button#wpforms-add-fields-checkbox');
			$I->waitForElementVisible('.wpforms-field-checkbox');
			$I->click('.wpforms-field-checkbox');
			$I->fillField('.wpforms-field-option-checkbox .active .wpforms-field-option-row-label input[type=text]', 'Tag ID');

			// Define options.
			for ( $i = 0; $i <= 2; $i++ ) {
				if ( isset( $tagValues[ $i ] ) ) {
					$I->fillField('.wpforms-field-option-checkbox .active .wpforms-field-option-row-choices ul li[data-key="' . ( $i + 1 ) . '"] input[type=text]', $tagValues[ $i ]);
				} else {
					$I->click('.wpforms-field-option-checkbox .active .wpforms-field-option-row-choices ul li[data-key="' . ( $i + 1 ) . '"] a.remove');
				}
			}
		} else {
			// Use freeform text field.

			// Wait for form editor to load.
			$I->waitForElementVisible('button#wpforms-add-fields-text');

			// Add Tag text field for backward compat. tests.
			$I->click('button#wpforms-add-fields-text');
			$I->waitForElementVisible('.wpforms-field-text');
			$I->click('.wpforms-field-text');
			$I->fillField('.wpforms-field-option-text .active input[type=text]', 'Tag ID');
		}

		// Click Save.
		$I->waitForElementVisible('#wpforms-save');
		$I->click('#wpforms-save');

		// Wait for save to complete.
		$I->waitForElementVisible('#wpforms-save:not(:disabled)');

		// Return Form ID.
		return (int) $I->grabAttributeFrom('#wpforms-builder-form', 'data-id');
	}

	/**
	 * Configures ConvertKit Settings for the given WPForms Form.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $wpFormID      WPForms Form ID.
	 * @param   bool|string      $formName      ConvertKit Form Name.
	 * @param   bool|string      $nameField     First Name Field.
	 * @param   bool|string      $emailField    Email Address Field.
	 * @param   bool|array       $customFields  Custom Fields.
	 * @param   bool|string      $tagField      Tag Field.
	 */
	public function configureConvertKitSettingsOnForm($I, $wpFormID, $formName, $nameField = false, $emailField = false, $customFields = false, $tagField = false)
	{
		// Load WPForms Editor.
		$I->amOnAdminPage('admin.php?page=wpforms-builder&view=fields&form_id=' . $wpFormID);

		// Click Marketing icon.
		$I->waitForElementVisible('.wpforms-panel-providers-button');
		$I->click('.wpforms-panel-providers-button');

		// Click ConvertKit tab.
		$I->click('#wpforms-panel-providers a.wpforms-panel-sidebar-section-convertkit');

		// Click Add New Connection.
		$I->click('Add New Connection');

		// Define name for connection.
		$I->waitForElementVisible('.jconfirm-content');
		$I->fillField('#provider-connection-name', 'ConvertKit');
		$I->click('OK');

		// Get the connection ID.
		$I->waitForElementVisible('.wpforms-provider-connections .wpforms-provider-connection');
		$connectionID = $I->grabAttributeFrom('.wpforms-provider-connections .wpforms-provider-connection', 'data-connection_id');

		// Specify field values.
		$I->waitForElementVisible('div[data-connection_id="' . $connectionID . '"] .wpforms-provider-fields');

		if ($formName) {
			// Confirm that Forms are in ascending alphabetical order.
			$I->checkSelectFormOptionOrder($I, '[name="providers[convertkit][' . $connectionID . '][list_id]"]');

			// Select Form.
			$I->selectOption('providers[convertkit][' . $connectionID . '][list_id]', $formName);

			// Wait for field mappings to reload, as the ConvertKit Form has changed.
			$I->waitForElementVisible('div[data-connection_id="' . $connectionID . '"] .wpforms-provider-fields');
		}
		if ($emailField) {
			$I->selectOption('providers[convertkit][' . $connectionID . '][fields][email]', $emailField);
		}
		if ($nameField) {
			$I->selectOption('providers[convertkit][' . $connectionID . '][fields][name]', $nameField);
		}
		if ($tagField) {
			$I->selectOption('providers[convertkit][' . $connectionID . '][fields][tag]', $tagField);
		}

		// Custom Fields.
		if ($customFields) {
			// Confirm that Custom Fields are listed in ascending alphabetical order in the table.
			$I->assertEquals(
				$I->grabTextFrom('.wpforms-provider-fields table tbody tr:nth-child(4) td:first-child'), // First Custom Field after Email, First Name, Tag.
				'ConvertKit: Custom Field: Billing Address'
			);
			$I->assertEquals(
				$I->grabTextFrom('.wpforms-provider-fields table tbody tr:last-child td:first-child'), // Last Custom Field.
				'ConvertKit: Custom Field: Test'
			);

			foreach ($customFields as $customField => $customFieldValue) {
				$I->selectOption('providers[convertkit][' . $connectionID . '][fields][custom_field_' . $customField . ']', $customFieldValue);
			}
		}

		// Click Save.
		$I->click('#wpforms-save');

		// Wait for save to complete.
		$I->waitForElementVisible('#wpforms-save:not(:disabled)');
	}

	/**
	 * Configures ConvertKit Settings for the given WPForms Form.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $wpFormID      WPForms Form ID.
	 * @param   bool|string      $customField   Custom Field (if specified, adds a field whose value will be used as a ConvertKit Custom Field Value).
	 * @param   bool|string      $tagField      Tag Field (if specified, adds a field whose value will be used as a ConvertKit Tag).
	 */
	public function configureWPFormsBackwardCompatClasses($I, $wpFormID, $customField = false, $tagField = false)
	{
		// Load WPForms Editor.
		$I->amOnAdminPage('admin.php?page=wpforms-builder&view=fields&form_id=' . $wpFormID);

		// Custom Field.
		if ($customField) {
			// Click field.
			$I->click('.wpforms-field-textarea');

			// Click Advanced tab.
			$I->click('.wpforms-field-option-textarea .wpforms-field-option-group-advanced a.wpforms-field-option-group-toggle');

			// Add CSS class to tell Plugin that the value of this field is a custom field.
			$I->waitForElementVisible('.wpforms-field-option-textarea .active');
			$I->fillField('.wpforms-field-option-textarea .wpforms-field-option-row-css input[type=text]', 'ck-custom-' . $customField);
		}

		// Tag Field.
		if ($tagField) {
			// Click field.
			$I->click('.wpforms-field-text');

			// Click Advanced tab.
			$I->click('.wpforms-field-option-text .wpforms-field-option-group-advanced a.wpforms-field-option-group-toggle');

			// Add CSS class to tell Plugin that the value of this field is a tag field.
			$I->waitForElementVisible('.wpforms-field-option-text .active');
			$I->fillField('.wpforms-field-option-text .wpforms-field-option-row-css input[type=text]', 'ck-tag');
		}

		// Click Save.
		$I->click('#wpforms-save');

		// Wait for save to complete.
		$I->waitForElementVisible('#wpforms-save:not(:disabled)');
	}

	/**
	 * Creates a WordPress Page with the WPForms shortcode as the content
	 * to render the WPForms Form.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I      AcceptanceTester.
	 * @param   int              $formID WPForms Form ID.
	 * @return  int                         Page ID
	 */
	public function createPageWithWPFormsShortcode($I, $formID)
	{
		return $I->havePostInDatabase(
			[
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => 'wpforms-form-' . $formID,
				'post_title'   => 'WPForms Form #' . $formID,
				'post_content' => '[wpforms id="' . $formID . '"]',
			]
		);
	}
}
