<?php
namespace Helper\Acceptance;

/**
 * Helper methods and actions related to the ConvertKit API,
 * which are then available using $I->{yourFunctionName}.
 *
 * @since   1.4.0
 */
class ConvertKitAPI extends \Codeception\Module
{
	/**
	 * Returns an encoded `state` parameter compatible with OAuth.
	 *
	 * @since   1.7.0
	 *
	 * @param   string $returnTo   Return URL.
	 * @param   string $clientID   OAuth Client ID.
	 * @return  string
	 */
	public function apiEncodeState($returnTo, $clientID)
	{
		$str = json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			array(
				'return_to' => $returnTo,
				'client_id' => $clientID,
			)
		);

		// Encode to Base64 string.
		$str = base64_encode( $str ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions

		// Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”.
		$str = strtr( $str, '+/', '-_' );

		// Remove padding character from the end of line.
		$str = rtrim( $str, '=' );

		return $str;
	}

	/**
	 * Check the given email address exists as a subscriber, and optionally
	 * checks that the first name and custom fields contain the expected data.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress  Email Address.
	 * @param   bool|string      $firstName     First Name.
	 * @param   bool|array       $customFields  Custom Fields.
	 * @return  int                             Subscriber ID.
	 */
	public function apiCheckSubscriberExists($I, $emailAddress, $firstName = false, $customFields = false)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,

				// Some test email addresses might bounce, so we want to check all subscriber states.
				'status'              => 'all',
			]
		);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['pagination']['total_count']);
		$I->assertEquals($emailAddress, $results['subscribers'][0]['email_address']);

		// If a first name was provided, check it matches.
		if ($firstName) {
			$I->assertEquals($firstName, $results['subscribers'][0]['first_name']);
		}

		// If custom fields are provided, check they exist.
		if ($customFields) {
			foreach ($customFields as $customField => $customFieldValue) {
				$I->assertEquals($results['subscribers'][0]['fields'][ $customField ], $customFieldValue);
			}
		}

		// Return subscriber ID.
		return $results['subscribers'][0]['id'];
	}

	/**
	 * Check the given email address does not exists as a subscriber.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 */
	public function apiCheckSubscriberDoesNotExist($I, $emailAddress)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address'       => $emailAddress,
				'include_total_count' => true,

				// Some test email addresses might bounce, so we want to check all subscriber states.
				'status'              => 'all',
			]
		);

		// Check no subscribers are returned by this request.
		$I->assertEquals(0, $results['pagination']['total_count']);
	}

	/**
	 * Check the given subscriber ID has been assigned to the given sequence ID.
	 *
	 * @since   1.7.2
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $sequenceID         Sequence ID.
	 */
	public function apiCheckSubscriberHasSequence($I, $subscriberID, $sequenceID)
	{
		// Run request.
		$results = $this->apiRequest(
			'sequences/' . $sequenceID . '/subscribers',
			'GET'
		);

		// Iterate through subscribers.
		$subscriberHasSequence = false;
		foreach ($results['subscribers'] as $subscriber) {
			if ($subscriber['id'] === $subscriberID) {
				$subscriberHasSequence = true;
				break;
			}
		}

		// Assert if the subscriber has the sequence.
		$this->assertTrue($subscriberHasSequence);
	}

	/**
	 * Check the given subscriber ID has been assigned to the given tag ID.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   int              $subscriberID  Subscriber ID.
	 * @param   int              $tagID         Tag ID.
	 */
	public function apiCheckSubscriberHasTag($I, $subscriberID, $tagID)
	{
		// Get subscriber tags.
		$subscriberTags = $this->apiGetSubscriberTags($subscriberID);

		$subscriberTagged = false;
		foreach ($subscriberTags as $tag) {
			if ( (int) $tag['id'] === (int) $tagID) {
				$subscriberTagged = true;
				break;
			}
		}

		// Check that the Subscriber is tagged.
		$I->assertTrue($subscriberTagged);
	}

	/**
	 * Checks if the given email address has no tags in ConvertKit.
	 *
	 * @since   1.5.4
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 * @param   int              $subscriberID   Subscriber ID.
	 */
	public function apiCheckSubscriberHasNoTags($I, $subscriberID)
	{
		// Get subscriber tags.
		$subscriberTags = $this->apiGetSubscriberTags($subscriberID);

		// Confirm no tags exist.
		$I->assertCount(0, $subscriberTags);
	}

	/**
	 * Returns all tags for the given subscriber ID from the API.
	 *
	 * @since   1.5.4
	 *
	 * @param   int $subscriberID  Subscriber ID.
	 * @return  array
	 */
	public function apiGetSubscriberTags($subscriberID)
	{
		$tags = $this->apiRequest('subscribers/' . $subscriberID . '/tags');
		return $tags['tags'];
	}

	/**
	 * Sends a request to the ConvertKit API, typically used to read an endpoint to confirm
	 * that data in an Acceptance Test was added/edited/deleted successfully.
	 *
	 * @since   1.4.0
	 *
	 * @param   string $endpoint   Endpoint.
	 * @param   string $method     Method (GET|POST|PUT).
	 * @param   array  $params     Endpoint Parameters.
	 */
	public function apiRequest($endpoint, $method = 'GET', $params = array())
	{
		// Send request.
		$client = new \GuzzleHttp\Client();
		switch ($method) {
			case 'GET':
				$result = $client->request(
					$method,
					'https://api.convertkit.com/v4/' . $endpoint . '?' . http_build_query($params),
					[
						'headers' => [
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
							'timeout'       => 5,
						],
					]
				);
				break;

			default:
				$result = $client->request(
					$method,
					'https://api.convertkit.com/v4/' . $endpoint,
					[
						'headers' => [
							'Accept'        => 'application/json',
							'Content-Type'  => 'application/json; charset=utf-8',
							'Authorization' => 'Bearer ' . $_ENV['CONVERTKIT_OAUTH_ACCESS_TOKEN'],
							'timeout'       => 5,
						],
						'body'    => (string) json_encode($params), // phpcs:ignore WordPress.WP.AlternativeFunctions
					]
				);
				break;
		}

		// Return JSON decoded response.
		return json_decode($result->getBody()->getContents(), true);
	}
}
