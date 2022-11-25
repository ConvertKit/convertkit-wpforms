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
		$wpFormsID = $I->havePostInDatabase(
			[
				'post_type'    => 'wpforms',
				'post_status'  => 'publish',
				'post_title'   => 'Migrate form',
				'post_name'    => 'migrate-form',
				'post_content' => '{"fields":[{"id":"0","type":"name","label":"Name","format":"first-last","description":"","required":"1","size":"medium","simple_placeholder":"","simple_default":"","first_placeholder":"","first_default":"","middle_placeholder":"","middle_default":"","last_placeholder":"","last_default":"","css":""},{"id":"1","type":"email","label":"Email","description":"","required":"1","size":"medium","placeholder":"","confirmation_placeholder":"","default_value":false,"filter_type":"","allowlist":"","denylist":"","css":""},{"id":"2","type":"textarea","label":"Comment or Message","description":"","size":"medium","placeholder":"","limit_count":"1","limit_mode":"characters","default_value":"","css":""},{"id":"3","type":"text","label":"Tag ID","description":"","size":"medium","placeholder":"","limit_count":"1","limit_mode":"characters","default_value":"","input_mask":"","css":""}],"id":"2","field_id":4,"settings":{"be_convertkit_api":"'.$_ENV['CONVERTKIT_API_KEY'].'","be_convertkit_form_id":"'.$_ENV['CONVERTKIT_API_FORM_ID'].'","be_convertkit_field_first_name":"0","be_convertkit_field_email":"1","form_title":"Simple Contact Form","form_desc":"","submit_text":"Submit","submit_text_processing":"Sending...","form_class":"","submit_class":"","ajax_submit":"1","notification_enable":"1","notifications":{"1":{"email":"{admin_email}","subject":"New Entry: Simple Contact Form","sender_name":"convertkit","sender_address":"{admin_email}","replyto":"{field_id=\"1\"}","message":"{all_fields}"}},"confirmations":{"1":{"type":"message","message":"<p>Thanks for contacting us! We will be in touch with you shortly.<\/p>","message_scroll":"1","redirect":""}},"antispam":"1","form_tags":[]},"meta":{"template":"simple-contact-form-template"}}',
			]
		);

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
		$I->apiCheckSubscriberExists($I, $emailAddress, $firstName.' '.$lastName);
	}
}
