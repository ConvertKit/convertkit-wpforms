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
	 * Creates a WPForms Form.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I AcceptanceTester.
	 * @return  int                 Form ID.
	 */
	public function createWPFormsForm($I)
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

		// Wait for form editor to load.
		$I->waitForElementVisible('button#wpforms-add-fields-text');

		// Add Tag Field.
		$I->click('button#wpforms-add-fields-text');
		$I->waitForElementVisible('.wpforms-field-text');
		$I->click('.wpforms-field-text');
		$I->fillField('.wpforms-field-option-text .active input[type=text]', 'Tag ID');

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
