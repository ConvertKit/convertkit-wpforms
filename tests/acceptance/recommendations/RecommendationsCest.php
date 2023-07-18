<?php
/**
 * Tests that the Creator Network Recommendations settings work with a WPForms Form
 *
 * @since   1.5.8
 */
class RecommendationsCest
{
	/**
	 * Run common actions before running the test functions in this class.
	 *
	 * @since   1.5.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function _before(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'wpforms-lite');
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option on a Form's settings
	 * is not displayed when no API Key and Secret are specified at WPForms > Settings > Integrations > ConvertKit.
	 *
	 * @since   1.5.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreatorNetworkRecommendationsOptionWhenNoAPIKeyAndSecret(AcceptanceTester $I)
	{
		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I);

		// Confirm that the Form Settings display the expected error message.
		$I->seeWPFormsSettingMessage(
			$I,
			$wpFormsID,
			'Please connect your ConvertKit account on the <a href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-admin/admin.php?page=wpforms-settings&amp;view=integrations" target="_blank">integrations screen</a>'
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Confirm the recommendations script was not loaded.
		$I->dontSeeCreatorNetworkRecommendationsScript($I, $pageID);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option on a Form's settings
	 * is not displayed when invalid API Key and Secret are specified at WPForms > Settings > Integrations > ConvertKit.
	 *
	 * @since   1.5.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreatorNetworkRecommendationsOptionWhenInvalidAPIKeyAndSecret(AcceptanceTester $I)
	{
		// Setup Plugin with invalid API Key and Secret.
		$I->setupConvertKitPlugin($I, 'fakeApiKey', 'fakeApiSecret');

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I);

		// Confirm that the Form Settings display the expected error message.
		$I->seeWPFormsSettingMessage(
			$I,
			$wpFormsID,
			'Please connect your ConvertKit account on the <a href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-admin/admin.php?page=wpforms-settings&amp;view=integrations" target="_blank">integrations screen</a>'
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Confirm the recommendations script was not loaded.
		$I->dontSeeCreatorNetworkRecommendationsScript($I, $pageID);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option on a Form's settings
	 * is not displayed when valid API Key and Secret are specified at WPForms > Settings > Integrations > ConvertKit.
	 * but the ConvertKit account does not have the Creator Network enabled.
	 *
	 * @since   1.5.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreatorNetworkRecommendationsOptionWhenDisabledOnConvertKitAccount(AcceptanceTester $I)
	{
		// Setup Plugin with API Key and Secret for ConvertKit Account that does not have the Creator Network enabled.
		$I->setupConvertKitPlugin($I, $_ENV['CONVERTKIT_API_KEY_NO_DATA'], $_ENV['CONVERTKIT_API_SECRET_NO_DATA']);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I);

		// Confirm that the Form Settings display the expected error message.
		$I->seeWPFormsSettingMessage(
			$I,
			$wpFormsID,
			'Please connect your ConvertKit account on the <a href="' . $_ENV['TEST_SITE_WP_URL'] . '/wp-admin/admin.php?page=wpforms-settings&amp;view=integrations" target="_blank">integrations screen</a>'
		);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Confirm the recommendations script was not loaded.
		$I->dontSeeCreatorNetworkRecommendationsScript($I, $pageID);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option on a Form's settings
	 * is displayed and saves correctly when valid API Key and Secret are specified at WPForms > Settings > Integrations > ConvertKit,
	 * and the ConvertKit account has the Creator Network enabled.  Viewing and submitting the Form does not
	 * display the Creator Network Recommendations modal, because the form submission will reload the page,
	 * which isn't supported right now.
	 *
	 * @since   1.5.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreatorNetworkRecommendationsWithAJAXDisabled(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I);

		// Enable Creator Network Recommendations on the form's settings.
		$I->enableWPFormsSettingCreatorNetworkRecommendations($I, $wpFormsID);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Confirm the recommendations script was not loaded.
		$I->dontSeeCreatorNetworkRecommendationsScript($I, $pageID);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Confirm the recommendations script was not loaded.
		$I->dontSeeCreatorNetworkRecommendationsScript($I, $pageID);
	}

	/**
	 * Tests that the 'Enable Creator Network Recommendations' option on a Form's settings
	 * is displayed and saves correctly when valid API Key and Secret are specified at WPForms > Settings > Integrations > ConvertKit,
	 * and the ConvertKit account has the Creator Network enabled.  Viewing and submitting the Form then correctly
	 * displays the Creator Network Recommendations modal.
	 *
	 * @since   1.5.8
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testCreatorNetworkRecommendationsWithAJAXEnabled(AcceptanceTester $I)
	{
		// Setup Plugin.
		$I->setupConvertKitPlugin($I);

		// Create Form.
		$wpFormsID = $I->createWPFormsForm($I);

		// Enable Creator Network Recommendations on the form's settings.
		$I->enableWPFormsSettingCreatorNetworkRecommendations($I, $wpFormsID);

		// Create a Page with the WPForms shortcode as its content.
		$pageID = $I->createPageWithWPFormsShortcode($I, $wpFormsID);

		// Confirm the recommendations script was loaded.
		$I->seeCreatorNetworkRecommendationsScript($I, $pageID);

		// Complete Form Fields.
		// @TODO.
		$I->fillField('.name_first input[type=text]', 'First');
		$I->fillField('.name_last input[type=text]', 'Last');
		$I->fillField('.ginput_container_email input[type=email]', $I->generateEmailAddress());

		// Submit Form.
		$I->click('Submit');

		// Wait for Creator Network Recommendations modal to display.
		$I->waitForElementVisible('.formkit-modal');
		$I->switchToIFrame('.formkit-modal iframe');
		$I->waitForElementVisible('div[data-component="Page"]');
		$I->switchToIFrame();

		// Close the modal.
		$I->click('.formkit-modal button.formkit-close');

		// Confirm that the underlying WPForms Form submitted successfully.
		$I->waitForElementNotVisible('.formkit-modal');
		$I->seeElementInDOM('.gform_confirmation_message');
		$I->see('Thanks for contacting us! We will get in touch with you shortly.');
	}

	/**
	 * Deactivate and reset Plugin(s) after each test, if the test passes.
	 * We don't use _after, as this would provide a screenshot of the Plugin
	 * deactivation and not the true test error.
	 *
	 * @since   1.5.8
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
