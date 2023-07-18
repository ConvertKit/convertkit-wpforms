<?php
/**
 * Tests that the ConvertKit Integration options work at WPForms > Settings > Integrations
 *
 * @since   1.5.0
 */
class IntegrationsCest
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
	 * Test that adding a ConvertKit account to the ConvertKit integration sections
	 * works with valid API credentials.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testAddIntegrationWithValidAPICredentials(AcceptanceTester $I)
	{
		// Load WPForms > Settings > Integrations.
		$I->amOnAdminPage('admin.php?page=wpforms-settings&view=integrations');

		// Expand ConvertKit integration section.
		$I->click('#wpforms-integration-convertkit');

		// Click Add New Account button.
		$I->click('#wpforms-integration-convertkit a[data-provider="convertkit"]');

		// Fill fields.
		$I->waitForElementVisible('.wpforms-settings-provider-accounts-connect input[name="api_key"]');
		$I->fillField('api_key', $_ENV['CONVERTKIT_API_KEY']);
		$I->fillField('api_secret', $_ENV['CONVERTKIT_API_SECRET']);

		// Click Connect to ConvertKit button.
		$I->click('Connect to ConvertKit');

		// Confirm that the 'Connected' element is visible.
		$I->waitForElementVisible('#wpforms-integration-convertkit .wpforms-settings-provider-info .connected-indicator');
		$I->see('Connected on:');

		// Confirm that the API Key and Secret were saved to the database.
		// This sanity checks that we didn't accidentally save the API Key to the API Secret field as we did in 1.5.7 and lower.
		$I->assertTrue($I->checkWPFormsIntegrationExists($I, $_ENV['CONVERTKIT_API_KEY'], $_ENV['CONVERTKIT_API_SECRET']));

		// Confirm that the connection can be disconnected.
		$I->click('Disconnect');

		// Confirm that we want to disconnect.
		$I->waitForElementVisible('.jconfirm-box');
		$I->click('.jconfirm-box button.btn-confirm');

		// Confirm no connection is listed.
		$I->wait(1);
		$I->dontSee('Connected on:');
	}

	/**
	 * Test that adding a ConvertKit account to the ConvertKit integration sections
	 * shows the expected error message when supplying invalid API credentials.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testAddIntegrationWithInvalidAPICredentials(AcceptanceTester $I)
	{
		// Load WPForms > Settings > Integrations.
		$I->amOnAdminPage('admin.php?page=wpforms-settings&view=integrations');

		// Expand ConvertKit integration section.
		$I->click('#wpforms-integration-convertkit');

		// Click Add New Account button.
		$I->click('#wpforms-integration-convertkit a[data-provider="convertkit"]');

		// Fill fields.
		$I->waitForElementVisible('.wpforms-settings-provider-accounts-connect input[name="api_key"]');
		$I->fillField('api_key', 'invalidApiKey');
		$I->fillField('api_secret', 'invalidApiSecret');

		// Click Connect to ConvertKit button.
		$I->click('Connect to ConvertKit');

		// Confirm the expected error message displays.
		$I->waitForElementVisible('.jconfirm-box');
		$I->see('Could not authenticate with the provider');
		$I->see('Authorization Failed: API Key not valid');
	}

	/**
	 * Test that adding a ConvertKit account to the ConvertKit integration sections
	 * shows the expected error message when supplying no API credentials.
	 *
	 * @since   1.5.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testAddIntegrationWithNoAPICredentials(AcceptanceTester $I)
	{
		// Load WPForms > Settings > Integrations.
		$I->amOnAdminPage('admin.php?page=wpforms-settings&view=integrations');

		// Expand ConvertKit integration section.
		$I->click('#wpforms-integration-convertkit');

		// Click Add New Account button.
		$I->click('#wpforms-integration-convertkit a[data-provider="convertkit"]');

		// Click Connect to ConvertKit button.
		$I->waitForElementVisible('.wpforms-settings-provider-accounts-connect input[name="api_key"]');
		$I->click('Connect to ConvertKit');

		// Confirm the expected error message displays.
		$I->waitForElementVisible('.jconfirm-box');
		$I->see('Could not authenticate with the provider');
		$I->see('The API Key is required');
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
