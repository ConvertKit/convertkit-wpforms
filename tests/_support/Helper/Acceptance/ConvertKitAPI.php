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
	 * Check the given email address exists as a subscriber, and optionally
	 * checks that the first name and custom fields contain the expected data.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I             AcceptanceTester.
	 * @param   string           $emailAddress  Email Address.
	 * @param   bool|string      $firstName     First Name.
	 * @param   bool|array       $customFields  Custom Fields.
	 */
	public function apiCheckSubscriberExists($I, $emailAddress, $firstName = false, $customFields = false)
	{
		// Run request.
		$results = $this->apiRequest(
			'subscribers',
			'GET',
			[
				'email_address' => $emailAddress,
			]
		);

		// Check at least one subscriber was returned and it matches the email address.
		$I->assertGreaterThan(0, $results['total_subscribers']);
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
				'email_address' => $emailAddress,
			]
		);

		// Check no subscribers are returned by this request.
		$I->assertEquals(0, $results['total_subscribers']);
	}

	/**
	 * Unsubscribes the given email address. Useful for clearing the API
	 * between tests.
	 *
	 * @since   1.4.0
	 *
	 * @param   string $emailAddress   Email Address.
	 */
	public function apiUnsubscribe($emailAddress)
	{
		// Run request.
		$this->apiRequest(
			'unsubscribe',
			'PUT',
			[
				'email' => $emailAddress,
			]
		);
	}

	/**
	 * Checks if the given email address has the given tag.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 * @param   string           $tagID          Tag ID.
	 */
	public function apiCheckSubscriberHasTag($I, $emailAddress, $tagID)
	{
		// Get Subscribers.
		$subscribers = $this->apiGetSubscribersByTagID($tagID);

		$subscriberTagged = false;
		foreach ($subscribers as $subscriber) {
			if ($subscriber['subscriber']['email_address'] === $emailAddress) {
				$subscriberTagged = true;
				break;
			}
		}

		// Check that the Subscriber is tagged.
		$I->assertTrue($subscriberTagged);
	}

	/**
	 * Checks if the given email address does not have the given tag.
	 *
	 * @since   1.4.0
	 *
	 * @param   AcceptanceTester $I              AcceptanceTester.
	 * @param   string           $emailAddress   Email Address.
	 * @param   string           $tagID          Tag ID.
	 */
	public function apiCheckSubscriberDoesNotHaveTag($I, $emailAddress, $tagID)
	{
		// Get Subscribers.
		$subscribers = $this->apiGetSubscribersByTagID($tagID);

		$subscriberTagged = false;
		foreach ($subscribers as $subscriber) {
			if ($subscriber['subscriber']['email_address'] === $emailAddress) {
				$subscriberTagged = true;
				break;
			}
		}

		// Check that the Subscriber is not tagged.
		$I->assertFalse($subscriberTagged);
	}

	/**
	 * Returns all subscribers to the given Tag ID from the API.
	 *
	 * @since   1.4.0
	 *
	 * @param   int $tagID  Tag ID.
	 * @return  array
	 */
	public function apiGetSubscribersByTagID($tagID)
	{
		// Get first page of subscribers.
		$subscribers = $this->apiRequest('tags/' . $tagID . '/subscriptions', 'GET');
		$data        = $subscribers['subscriptions'];
		$totalPages  = $subscribers['total_pages'];

		if ($totalPages === 1) {
			return $data;
		}

		// Get additional pages of purchases.
		for ($page = 2; $page <= $totalPages; $page++) {
			$subscribers = $this->apiRequest(
				'tags/' . $tagID . '/subscriptions',
				'GET',
				[
					'page' => $page,
				]
			);

			$data = array_merge($data, $subscribers['subscriptions']);
		}

		return $data;
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
		// Build query parameters.
		$params = array_merge(
			$params,
			[
				'api_key'    => $_ENV['CONVERTKIT_API_KEY'],
				'api_secret' => $_ENV['CONVERTKIT_API_SECRET'],
			]
		);

		// Send request.
		try {
			$client = new \GuzzleHttp\Client();
			$result = $client->request(
				$method,
				'https://api.convertkit.com/v3/' . $endpoint . '?' . http_build_query($params),
				[
					'headers' => [
						'Accept-Encoding' => 'gzip',
						'timeout'         => 5,
					],
				]
			);

			// Return JSON decoded response.
			return json_decode($result->getBody()->getContents(), true);
		} catch (\GuzzleHttp\Exception\ClientException $e) {
			return [];
		}
	}
}
