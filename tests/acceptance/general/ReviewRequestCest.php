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
		$I->seeInSource('<a href="https://kit.com/support" class="button" rel="noopener" target="_blank">');

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
