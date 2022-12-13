<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to email addresses,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.4.0
 */
class Email extends \Codeception\Module
{
	/**
	 * Generates a unique email address for use in a test, comprising of a prefix,
	 * date + time and PHP version number.
	 *
	 * This ensures that if tests are run in parallel, the same email address
	 * isn't used for two tests across parallel testing runs.
	 *
	 * @since   1.4.0
	 */
	public function generateEmailAddress()
	{
		return 'wordpress-' . uniqid() . '-' . date( 'Y-m-d-H-i-s' ) . '-php-' . PHP_VERSION_ID . '@n7studios.com';
	}
}
