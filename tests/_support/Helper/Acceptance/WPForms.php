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
		$I->waitForElementVisible('#wpforms-builder-form');

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
	 * @param   bool|string      $apiKey        API Key.
	 * @param   bool|int         $formID        ConvertKit Form ID.
	 * @param   bool|string      $nameField     First Name Field.
	 * @param   bool|string      $emailField    Email Address Field.
	 * @param   bool|string      $customField   Custom Field (if specified, adds a field whose value will be used as a ConvertKit Custom Field Value).
	 * @param   bool|string      $tagField      Tag Field (if specified, adds a field whose value will be used as a ConvertKit Tag).
	 */
	public function configureConvertKitSettingsOnForm($I, $wpFormID, $apiKey = false, $formID = false, $nameField = false, $emailField = false, $customField = false, $tagField = false)
	{
		// Load WPForms Editor.
		$I->amOnAdminPage('admin.php?page=wpforms-builder&view=fields&form_id=' . $wpFormID);

		// Click Settings icon.
		$I->waitForElementVisible('.wpforms-panel-settings-button');
		$I->click('.wpforms-panel-settings-button');

		// Click ConvertKit tab.
		$I->click('.wpforms-panel-sidebar a.wpforms-panel-sidebar-section-be_convertkit');

		// Specify field values.
		if ($apiKey) {
			$I->fillField('#wpforms-panel-field-settings-be_convertkit_api', $apiKey);
		}
		if ($formID) {
			$I->fillField('#wpforms-panel-field-settings-be_convertkit_form_id', $formID);
		}
		if ($nameField) {
			$I->selectOption('#wpforms-panel-field-settings-be_convertkit_field_first_name', $nameField);
		}
		if ($emailField) {
			$I->selectOption('#wpforms-panel-field-settings-be_convertkit_field_email', $emailField);
		}

		// Custom Field.
		if ($customField) {
			// Click Fields icon.
			$I->click('.wpforms-panel-fields-button');

			// Add Single Linke Text field.
			$I->click('#wpforms-add-fields-text');

			// Click field.
			$I->waitForElementVisible('.wpforms-field-text');
			$I->click('.wpforms-field-text');

			// Enter field name.
			$I->waitForElementVisible('.wpforms-field-option-text .active');
			$I->fillField('.wpforms-field-option-text .active input[type=text]', 'Custom Field Value');

			// Click Advanced tab.
			$I->click('.wpforms-field-option-text .wpforms-field-option-group-advanced a.wpforms-field-option-group-toggle');

			// Add CSS class to tell Plugin that the value of this field is a custom field.
			$I->waitForElementVisible('.wpforms-field-option-text .active');
			$I->fillField('.wpforms-field-option-text .wpforms-field-option-row-css input[type=text]', 'ck-custom-' . $customField);
		}

		// Tag Field.
		if ($tagField) {
			// Click Fields icon.
			$I->click('.wpforms-panel-fields-button');

			// Add Single Linke Text field.
			$I->click('#wpforms-add-fields-text');

			// Click field.
			$I->waitForElementVisible('.wpforms-field-text');
			$I->click('.wpforms-field-text');

			// Enter field name.
			$I->waitForElementVisible('.wpforms-field-option-text .active');
			$I->fillField('.wpforms-field-option-text .active input[type=text]', 'Tag ID');

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
