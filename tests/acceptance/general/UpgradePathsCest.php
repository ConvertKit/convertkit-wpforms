<?php
/**
 * Tests edge cases when upgrading between specific ConvertKit Plugin versions.
 *
 * @since   1.5.0
 */
class UpgradePathsCest
{
	/**
	 * Check that ConvertKit Settings stored on a WPForms Form using < 1.5.0 are correctly
	 * migrated to the WPForms > Settings > Integrations tab.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testMigrateFormSettingsToIntegration(AcceptanceTester $I)
	{
		// Activate WPForms.
		$I->activateThirdPartyPlugin($I, 'wpforms-lite');

		// Create Form, as if it were created with this Plugin < 1.5.0.
		$wpFormsID = $I->createWPFormsFormForMigration($I);

		// Activate Plugin, which triggers the automatic settings to integrations migration process.
		$I->activateConvertKitPlugin($I);

		// Confirm that the version number now exists in the options table.
		$I->seeOptionInDatabase('integrate_convertkit_wpforms_version');

		// Confirm that an integration is now registered for ConvertKit.
		$providers = $I->grabOptionFromDatabase('wpforms_providers');
		$I->assertArrayHasKey('convertkit', $providers);

		// Get first integration for ConvertKit, and confirm it has the expected array structure and values.
		$account = reset( $providers['convertkit'] );
		$I->assertArrayHasKey('api_key', $account);
		$I->assertArrayHasKey('api_secret', $account);
		$I->assertArrayHasKey('label', $account);
		$I->assertArrayHasKey('date', $account);
		$I->assertEquals($_ENV['CONVERTKIT_API_KEY'], $account['api_key']);
		$I->assertEquals('ConvertKit', $account['label']);

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
		$I->apiCheckSubscriberExists($I, $emailAddress, $firstName . ' ' . $lastName);
	}
}
