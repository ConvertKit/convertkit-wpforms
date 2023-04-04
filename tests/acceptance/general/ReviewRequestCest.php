<?php
/**
 * Tests the ConvertKit Review Notification.
 *
 * @since   1.5.5
 */
class ReviewRequestCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.5.5
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
	 * sets a review request in the options table when valid API credentials are
	 * specified.
	 *
	 * @since   1.5.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testReviewRequestOnSaveValidAPICredentials(AcceptanceTester $I)
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

		// Check that a review request was created.
		$I->reviewRequestExists($I);
	}

	/**
	 * Test that no review request is set in the options table when the Plugin's
	 * Settings are saved with no Forms specified in the Settings.
	 *
	 * @since   1.5.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testReviewRequestOnSaveInvalidAPICredentials(AcceptanceTester $I)
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

		// Check that no review request was created.
		$I->reviewRequestDoesNotExist($I);
	}

	/**
	 * Test that the review request is displayed when the options table entries
	 * have the required values to display the review request notification.
	 *
	 * @since   1.5.5
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testReviewRequestNotificationDisplaysAndDismisses(AcceptanceTester $I)
	{
		// Set review request option with a timestamp in the past, to emulate
		// the Plugin having set this a few days ago.
		$I->haveOptionInDatabase('integrate-convertkit-wpforms-review-request', time() - 3600 );

		// Navigate to a screen in the WordPress Administration.
		$I->amOnAdminPage('index.php');

		// Confirm the review displays.
		$I->seeElementInDOM('div.review-integrate-convertkit-wpforms');

		// Confirm links are correct.
		$I->seeInSource('<a href="https://wordpress.org/support/plugin/integrate-convertkit-wpforms/reviews/?filter=5#new-post" class="button button-primary" rel="noopener" target="_blank">');
		$I->seeInSource('<a href="https://convertkit.com/support" class="button" rel="noopener" target="_blank">');

		// Dismiss the review request.
		$I->click('div.review-integrate-convertkit-wpforms button.notice-dismiss');

		// Navigate to a screen in the WordPress Administration.
		$I->amOnAdminPage('index.php');

		// Confirm the review notification no longer displays.
		$I->dontSeeElementInDOM('div.review-integrate-convertkit-wpforms');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.5.5
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
