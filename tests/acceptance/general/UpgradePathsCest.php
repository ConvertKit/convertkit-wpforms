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
	}

	/**
	 * Tests that an Access Token and Refresh Token are obtained using an API Key and Secret
	 * when upgrading to 1.7.0 or later.
	 *
	 * @since   1.7.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testGetAccessTokenByAPIKeyAndSecret(AcceptanceTester $I)
	{
		// Setup Plugin's settings with an API Key and Secret.
		$I->setupWPFormsIntegrationWithAPIKeyAndSecret($I);

		// Activate Plugins.
		$I->activateThirdPartyPlugin($I, 'wpforms-lite');
		$I->activateConvertKitPlugin($I);

		// Confirm the options table now contains an Access Token and Refresh Token.
		$providers = $I->grabOptionFromDatabase('wpforms_providers');
		$I->assertArrayHasKey('convertkit', $providers);
		$I->assertArrayHasKey('access_token', reset($providers['convertkit']));
		$I->assertArrayHasKey('refresh_token', reset($providers['convertkit']));
		$I->assertArrayHasKey('token_expires', reset($providers['convertkit']));
		$I->assertArrayHasKey('label', reset($providers['convertkit']));
		$I->assertArrayHasKey('date', reset($providers['convertkit']));

		// Load the integrations screen.
		$I->amOnAdminPage('admin.php?page=wpforms-settings&view=integrations');

		// Confirm that the 'Connected' element is visible.
		$I->seeElementInDOM('#wpforms-integration-convertkit .wpforms-settings-provider-info .connected-indicator');
		$I->click('#wpforms-integration-convertkit');
		$I->wait(3);
		$I->waitForElementVisible('#wpforms-integration-convertkit .wpforms-settings-provider-accounts-list');
		$I->see('Connected on:');
	}
}
