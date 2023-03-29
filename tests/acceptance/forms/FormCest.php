<?php
/**
 * Tests that the Plugin works when configuring and using WPForms.
 *
 * @since   1.5.0
 */
class FormCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'wpforms-lite');
	}

	/**
	 * Test that the Plugin works when:
	 * - Creating a WPForms Form,
	 * - Adding a valid ConvertKit Connection,
	 * - Submitting the Form on the frontend web site results in the email address subscribing to the ConvertKit Form.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreateForm(AcceptanceTester $I)
	{
		// Define connection with valid API credentials.
		$I->setupWPFormsIntegration($I);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I);

		// Configure ConvertKit on Form.
		$I->configureConvertKitSettingsOnForm(
			$I,
			$wpFormsID,
			$_ENV['CONVERTKIT_API_FORM_NAME'],
			'Name (First)',
			'Email'
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Define Name and Email Address for this Test.
		$firstName    = 'First';
		$lastName     = 'Last';
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Load the Page on the frontend site.
		$I->amOnPage('/?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Form Fields.
		$I->fillField('input.wpforms-field-name-first', $firstName);
		$I->fillField('input.wpforms-field-name-last', $lastName);
		$I->fillField('.wpforms-field-email input[type=email]', $emailAddress);

		// Submit Form.
		$I->click('Submit');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm submission was successful.
		$I->waitForElementVisible('.wpforms-confirmation-scroll');
		$I->seeInSource('Thanks for contacting us! We will be in touch with you shortly.');

		// Check API to confirm subscriber was sent.
		$I->apiCheckSubscriberExists($I, $emailAddress, $firstName);
	}

	/**
	 * Test that the Plugin works when:
	 * - Creating a WPForms Form,
	 * - Adding a valid ConvertKit Connection,
	 * - Adding a field whose value will be a valid ConvertKit Tag ID.
	 * - Submitting the Form on the frontend web site results works.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreateFormWithTagID(AcceptanceTester $I)
	{
		// Define connection with valid API credentials.
		$I->setupWPFormsIntegration($I);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I, [
			$_ENV['CONVERTKIT_API_TAG_ID']
		]);

		// Configure ConvertKit on Form.
		$I->configureConvertKitSettingsOnForm(
			$I,
			$wpFormsID,
			$_ENV['CONVERTKIT_API_FORM_NAME'],
			'Name (First)',
			'Email',
			false, // Custom Fields.
			'Tag ID' // Name of Tag Field in WPForms.
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Define Name and Email Address for this Test.
		$firstName    = 'First';
		$lastName     = 'Last';
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Load the Page on the frontend site.
		$I->amOnPage('/?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Form Fields.
		$I->fillField('input.wpforms-field-name-first', $firstName);
		$I->fillField('input.wpforms-field-name-last', $lastName);
		$I->fillField('.wpforms-field-email input[type=email]', $emailAddress);
		$I->checkOption('.wpforms-field-checkbox input[value="' . $_ENV['CONVERTKIT_API_TAG_ID'] . '"]');

		// Submit Form.
		$I->click('Submit');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm submission was successful.
		$I->waitForElementVisible('.wpforms-confirmation-scroll');
		$I->seeInSource('Thanks for contacting us! We will be in touch with you shortly.');

		// Check API to confirm subscriber was sent.
		$I->apiCheckSubscriberExists($I, $emailAddress, $firstName);

		// Check API to confirm subscriber has Tag set.
		$I->apiCheckSubscriberHasTag($I, $emailAddress, $_ENV['CONVERTKIT_API_TAG_ID']);
	}

	/**
	 * Test that the Plugin works when:
	 * - Creating a WPForms Form,
	 * - Adding a valid ConvertKit Connection,
	 * - Adding a field whose value will be a valid ConvertKit Tag Name.
	 * - Submitting the Form on the frontend web site results works.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreateFormWithTagName(AcceptanceTester $I)
	{
		// Define connection with valid API credentials.
		$I->setupWPFormsIntegration($I);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I, [
			$_ENV['CONVERTKIT_API_TAG_NAME']
		]);

		// Configure ConvertKit on Form.
		$I->configureConvertKitSettingsOnForm(
			$I,
			$wpFormsID,
			$_ENV['CONVERTKIT_API_FORM_NAME'],
			'Name (First)',
			'Email',
			false, // Custom Fields.
			'Tag ID' // Name of Tag Field in WPForms.
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Define Name and Email Address for this Test.
		$firstName    = 'First';
		$lastName     = 'Last';
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Load the Page on the frontend site.
		$I->amOnPage('/?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Form Fields.
		$I->fillField('input.wpforms-field-name-first', $firstName);
		$I->fillField('input.wpforms-field-name-last', $lastName);
		$I->fillField('.wpforms-field-email input[type=email]', $emailAddress);
		$I->checkOption('.wpforms-field-checkbox input[value="' . $_ENV['CONVERTKIT_API_TAG_NAME'] . '"]');

		// Submit Form.
		$I->click('Submit');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm submission was successful.
		$I->waitForElementVisible('.wpforms-confirmation-scroll');
		$I->seeInSource('Thanks for contacting us! We will be in touch with you shortly.');

		// Check API to confirm subscriber was sent.
		$I->apiCheckSubscriberExists($I, $emailAddress, $firstName);

		// Check API to confirm subscriber has Tag set.
		$I->apiCheckSubscriberHasTag($I, $emailAddress, $_ENV['CONVERTKIT_API_TAG_ID']);
	}

	/**
	 * Test that the Plugin works when:
	 * - Creating a WPForms Form,
	 * - Adding a valid API Key and valid Form ID,
	 * - Adding a field whose value will be an invalid ConvertKit Tag ID.
	 * - Submitting the Form on the frontend web site results works.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreateFormWithInvalidTagID(AcceptanceTester $I)
	{
		// Define connection with valid API credentials.
		$I->setupWPFormsIntegration($I);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I, [
			'1111' // A fake Tag ID.
		]);

		// Configure ConvertKit on Form.
		$I->configureConvertKitSettingsOnForm(
			$I,
			$wpFormsID,
			$_ENV['CONVERTKIT_API_FORM_NAME'],
			'Name (First)',
			'Email',
			false, // Custom Fields.
			'Tag ID' // Name of Tag Field in WPForms.
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Define Name and Email Address for this Test.
		$firstName    = 'First';
		$lastName     = 'Last';
		$emailAddress = $I->generateEmailAddress();

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Load the Page on the frontend site.
		$I->amOnPage('/?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Form Fields.
		$I->fillField('input.wpforms-field-name-first', $firstName);
		$I->fillField('input.wpforms-field-name-last', $lastName);
		$I->fillField('.wpforms-field-email input[type=email]', $emailAddress);
		$I->checkOption('.wpforms-field-checkbox input[value="1111"]');

		// Submit Form.
		$I->click('Submit');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm submission was successful.
		$I->waitForElementVisible('.wpforms-confirmation-scroll');
		$I->seeInSource('Thanks for contacting us! We will be in touch with you shortly.');

		// Check API to confirm subscriber was sent.
		$I->apiCheckSubscriberExists($I, $emailAddress, $firstName);
	}

	/**
	 * Test that the Plugin works when:
	 * - Creating a WPForms Form,
	 * - Adding a valid API Key and valid Form ID,
	 * - Adding a field whose value will be stored against a ConvertKit Custom Field.
	 * - Submitting the Form on the frontend web site results works.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreateFormWithCustomField(AcceptanceTester $I)
	{
		// Define connection with valid API credentials.
		$I->setupWPFormsIntegration($I);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I);

		// Configure ConvertKit on Form.
		$I->configureConvertKitSettingsOnForm(
			$I,
			$wpFormsID,
			$_ENV['CONVERTKIT_API_FORM_NAME'],
			'Name (First)',
			'Email',
			array(  // Custom Fields.
				$_ENV['CONVERTKIT_API_CUSTOM_FIELD_NAME'] => 'Comment or Message', // ConvertKit Custom Field --> WPForms Field Name mapping.
			)
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Define Name and Email Address for this Test.
		$firstName    = 'First';
		$lastName     = 'Last';
		$emailAddress = $I->generateEmailAddress();
		$customFields = [
			$_ENV['CONVERTKIT_API_CUSTOM_FIELD_NAME'] => 'Notes',
		];

		// Logout as the WordPress Administrator.
		$I->logOut();

		// Load the Page on the frontend site.
		$I->amOnPage('/?p=' . $pageID);

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Complete Form Fields.
		$I->fillField('input.wpforms-field-name-first', $firstName);
		$I->fillField('input.wpforms-field-name-last', $lastName);
		$I->fillField('.wpforms-field-email input[type=email]', $emailAddress);
		$I->fillField('.wpforms-field-textarea textarea', $customFields[ $_ENV['CONVERTKIT_API_CUSTOM_FIELD_NAME'] ]);

		// Submit Form.
		$I->click('Submit');

		// Check that no PHP warnings or notices were output.
		$I->checkNoWarningsAndNoticesOnScreen($I);

		// Confirm submission was successful.
		$I->waitForElementVisible('.wpforms-confirmation-scroll');
		$I->seeInSource('Thanks for contacting us! We will be in touch with you shortly.');

		// Check API to confirm subscriber was sent and data mapped to fields correctly.
		$I->apiCheckSubscriberExists($I, $emailAddress, $firstName, $customFields);
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _passed(AcceptanceTester $I)
	{
		$I->deactivateConvertKitPlugin($I);

		// We don't use deactivateThirdPartyPlugin(), as this checks for PHP warnings/errors.
		// WPForms throws a 502 bad gateway on deactivation, which is outside of our control
		// and would result in the test not completing.
		$I->amOnPluginsPage();
		$I->deactivatePlugin('wpforms-lite');
	}
}
