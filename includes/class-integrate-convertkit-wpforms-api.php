<?php
/**
 * ConvertKit API class for WPForms.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

/**
 * ConvertKit API class for WPForms.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */
class Integrate_ConvertKit_WPForms_API extends ConvertKit_API {

	/**
	 * Holds the log class for writing to the log file
	 *
	 * @var bool
	 */
	public $log = false; // @phpstan-ignore-line

	/**
	 * Holds an array of error messages, localized to the plugin
	 * using this API class.
	 *
	 * @var bool|array
	 */
	public $error_messages = false;

	/**
	 * Sets up the API with the required credentials.
	 *
	 * @since   1.4.1
	 *
	 * @param   bool|string $api_key        ConvertKit API Key.
	 * @param   bool|string $api_secret     ConvertKit API Secret.
	 * @param   bool|object $debug          Save data to log.
	 */
	public function __construct( $api_key = false, $api_secret = false, $debug = false ) {

		// Set API credentials, debugging and logging class.
		$this->api_key        = $api_key;
		$this->api_secret     = $api_secret;
		$this->debug          = $debug;
		$this->plugin_name    = ( defined( 'CKWC_PLUGIN_NAME' ) ? CKWC_PLUGIN_NAME : false );
		$this->plugin_path    = ( defined( 'CKWC_PLUGIN_PATH' ) ? CKWC_PLUGIN_PATH : false );
		$this->plugin_url     = ( defined( 'CKWC_PLUGIN_URL' ) ? CKWC_PLUGIN_URL : false );
		$this->plugin_version = ( defined( 'CKWC_PLUGIN_VERSION' ) ? CKWC_PLUGIN_VERSION : false );

		// Setup logging class if the required parameters exist.
		if ( $this->debug && $this->plugin_path !== false ) {
			$this->log = new WC_Logger();
		}

		// Define translatable / localized error strings.
		// WordPress requires that the text domain be a string (e.g. 'integrate-convertkit-wpforms') and not a variable,
		// otherwise localization won't work.
		$this->error_messages = array(
			'form_subscribe_form_id_empty'                => __( 'form_subscribe(): the form_id parameter is empty.', 'integrate-convertkit-wpforms' ),
			'form_subscribe_email_empty'                  => __( 'form_subscribe(): the email parameter is empty.', 'integrate-convertkit-wpforms' ),

			'sequence_subscribe_sequence_id_empty'        => __( 'sequence_subscribe(): the sequence_id parameter is empty.', 'integrate-convertkit-wpforms' ),
			'sequence_subscribe_email_empty'              => __( 'sequence_subscribe(): the email parameter is empty.', 'integrate-convertkit-wpforms' ),

			'tag_subscribe_tag_id_empty'                  => __( 'tag_subscribe(): the tag_id parameter is empty.', 'integrate-convertkit-wpforms' ),
			'tag_subscribe_email_empty'                   => __( 'tag_subscribe(): the email parameter is empty.', 'integrate-convertkit-wpforms' ),

			'get_subscriber_by_email_email_empty'         => __( 'get_subscriber_by_email(): the email parameter is empty.', 'integrate-convertkit-wpforms' ),
			/* translators: Email Address */
			'get_subscriber_by_email_none'                => __( 'No subscriber(s) exist in ConvertKit matching the email address %s.', 'integrate-convertkit-wpforms' ),

			'get_subscriber_by_id_subscriber_id_empty'    => __( 'get_subscriber_by_id(): the subscriber_id parameter is empty.', 'integrate-convertkit-wpforms' ),

			'get_subscriber_tags_subscriber_id_empty'     => __( 'get_subscriber_tags(): the subscriber_id parameter is empty.', 'integrate-convertkit-wpforms' ),

			'unsubscribe_email_empty'                     => __( 'unsubscribe(): the email parameter is empty.', 'integrate-convertkit-wpforms' ),

			'get_all_posts_posts_per_request_bound_too_low' => __( 'get_all_posts(): the posts_per_request parameter must be equal to or greater than 1.', 'integrate-convertkit-wpforms' ),
			'get_all_posts_posts_per_request_bound_too_high' => __( 'get_all_posts(): the posts_per_request parameter must be equal to or less than 50.', 'integrate-convertkit-wpforms' ),

			'get_posts_page_parameter_bound_too_low'      => __( 'get_posts(): the page parameter must be equal to or greater than 1.', 'integrate-convertkit-wpforms' ),
			'get_posts_per_page_parameter_bound_too_low'  => __( 'get_posts(): the per_page parameter must be equal to or greater than 1.', 'integrate-convertkit-wpforms' ),
			'get_posts_per_page_parameter_bound_too_high' => __( 'get_posts(): the per_page parameter must be equal to or less than 50.', 'integrate-convertkit-wpforms' ),

			/* translators: HTTP method */
			'request_method_unsupported'                  => __( 'API request method %s is not supported in ConvertKit_API class.', 'integrate-convertkit-wpforms' ),
			'request_rate_limit_exceeded'                 => __( 'Rate limit hit.', 'integrate-convertkit-wpforms' ),
			'response_type_unexpected'                    => __( 'The response from the API is not of the expected type array.', 'integrate-convertkit-wpforms' ),
		);

	}

}
