<?php
/**
 * Tests Plugin activation and deactivation.
 *
 * @since   1.4.0
 */
class ActivateDeactivatePluginCest
{
	/**
	 * Test that activating the Plugin and the WPForms Plugins works
	 * with no errors.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testPluginActivationDeactivation(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->activateThirdPartyPlugin($I, 'wpforms-lite');
		$I->deactivateConvertKitPlugin($I);
		$I->deactivateThirdPartyPlugin($I, 'wpforms-lite');
	}

	/**
	 * Test that activating the Plugin, without activating the WPForms Plugin, works
	 * with no errors.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I  Tester.
	 */
	public function testPluginActivationDeactivationWithoutWPForms(AcceptanceTester $I)
	{
		$I->activateConvertKitPlugin($I);
		$I->deactivateConvertKitPlugin($I);

	}
}
