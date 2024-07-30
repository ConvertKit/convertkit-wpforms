<?php
/**
 * ConvertKit WPForms class.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

/**
 * Class ConvertKit WPForms
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */
class Integrate_ConvertKit_WPForms extends WPForms_Provider {

	/**
	 * Holds API connections to ConvertKit.
	 *
	 * @since   1.5.0
	 *
	 * @var     array
	 */
	public $api = array();

	/**
	 * Holds the review request class.
	 *
	 * @since   1.5.5
	 *
	 * @var     bool|ConvertKit_Review_Request
	 */
	private $review_request = false;

	/**
	 * Holds the ConvertKit registration URL.
	 *
	 * @since   1.5.0
	 *
	 * @var     string
	 */
	private $register_url = 'https://app.convertkit.com/users/signup?utm_source=wordpress&utm_content=convertkit-wpforms';

	/**
	 * Initialize ConvertKit as a WPForms Provider.
	 *
	 * @since   1.5.0
	 */
	public function init() {

		// Define Provider details.
		$this->version  = INTEGRATE_CONVERTKIT_WPFORMS_VERSION;
		$this->name     = 'ConvertKit';
		$this->slug     = 'convertkit';
		$this->priority = 14;
		$this->icon     = INTEGRATE_CONVERTKIT_WPFORMS_URL . 'resources/backend/images/convertkit-logomark-red.svg';

		// Initialize classes.
		$this->review_request = new ConvertKit_Review_Request( 'ConvertKit for WPForms', 'integrate-convertkit-wpforms', INTEGRATE_CONVERTKIT_WPFORMS_PATH );

		// Run update routine.
		add_action( 'init', array( $this, 'update' ) );

		if ( is_admin() ) {
			add_action( 'init', array( $this, 'maybe_display_notice' ) );
			add_action( 'init', array( $this, 'maybe_get_and_store_access_token' ) );
			add_action( "wp_ajax_wpforms_settings_provider_disconnect_{$this->slug}", array( $this, 'delete_resource_cache' ), 1 );
			add_action( 'wpforms_settings_enqueue', array( $this, 'enqueue_assets' ) );
			add_filter( "wpforms_providers_provider_settings_formbuilder_display_content_default_screen_{$this->slug}", array( $this, 'builder_settings_default_content' ) );
		}

	}

	/**
	 * Runs update/upgrade routines between Plugin versions.
	 *
	 * @since   1.5.0
	 */
	public function update() {

		$setup = new Integrate_ConvertKit_WPForms_Setup();
		$setup->update();

	}

	/**
	 * Processes and submits a WPForms Form entry to ConvertKit,
	 * based on the Form's settings at Marketing > ConvertKit.
	 *
	 * @since   1.5.0
	 *
	 * @param   array $fields    List of fields with their data and settings.
	 * @param   array $entry     Submitted entry values.
	 * @param   array $form_data Form data and settings.
	 * @param   int   $entry_id  Saved entry ID.
	 */
	public function process_entry( $fields, $entry, $form_data, $entry_id = 0 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Only run if this form has a connection for this provider.
		if ( empty( $form_data['providers'][ $this->slug ] ) ) {
			return;
		}

		// Iterate through each ConvertKit connection.  A WPForms Form can have one or more
		// connection to a provider (i.e. ConvertKit) configured at Marketing > ConvertKit.
		foreach ( $form_data['providers'][ $this->slug ] as $connection ) {
			// Skip if the list_id (ConvertKit Form ID) isn't specified, as we cannot
			// subscribe the email address otherwise.
			if ( empty( $connection['list_id'] ) ) {
				wpforms_log(
					'ConvertKit',
					__( 'No Form ID was specified.', 'integrate-convertkit-wpforms' ),
					array(
						'type'    => array( 'provider', 'error' ),
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					)
				);
				continue;
			}

			// Load ConvertKit API instance for this WPForms connection.
			$api = $this->get_api_instance( $connection['account_id'] );

			// If an error occured, log it and continue to the next connection.
			if ( is_wp_error( $api ) ) {
				wpforms_log(
					'ConvertKit',
					$api->get_error_message(),
					array(
						'type'    => array( 'provider', 'error' ),
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					)
				);
				continue;
			}

			// Load resource classes with this API instance.
			$resource_forms = new Integrate_ConvertKit_WPForms_Resource_Forms( $api, $connection['account_id'] );
			$resource_tags  = new Integrate_ConvertKit_WPForms_Resource_Tags( $api, $connection['account_id'] );

			// Iterate through the WPForms Form field to ConvertKit field mappings, to build
			// the API query to subscribe to the ConvertKit Form.
			$args = array();
			foreach ( $connection['fields'] as $convertkit_field => $wpforms_field ) {
				// Skip if no ConvertKit mapping specified for this WPForms Form field.
				if ( empty( $wpforms_field ) ) {
					continue;
				}

				// Fetch the field's value from the WPForms Form entry.
				$value = $this->get_entry_field_value( $wpforms_field, $fields );

				// Depending on the field name, store the value in the $args array.
				switch ( $convertkit_field ) {
					/**
					 * Email
					 * Name
					 */
					case 'email':
					case 'name':
						$args[ $convertkit_field ] = $value;
						break;

					/**
					 * Tag
					 */
					case 'tag':
						// The WPForms field value might be any one of the following, depending on the WPForms field and its configuration:
						// - an integer (Tag ID) on a single line e.g. the mapped field is a <select> with separate <option> values defined
						// - a string (Tag Name) on a single line e.g. the mapped field is a <select> where values match <option> labels
						// - integers (Tag IDs), one on each line, separated by a newline e.g. the mapped field is a checkbox where values differ from labels
						// - strings (Tag Names), one on each line, separated by a newline e.g. the mapped field is a checkbox where values match labels.
						// We need to build an array of Tag IDs from the value.

						// Don't do anything if the value is empty.
						if ( empty( $value ) ) {
							break;
						}

						// Fetch tags, so we can convert any tag names to their tag IDs
						// for submission to form_subscribe().
						$api_tags = $resource_tags->get();

						// Define an array for Tag IDs to be stored in.
						$args['tags'] = array();

						// Iterate through the submitted value(s), to build an array of Tag IDs.
						foreach ( explode( "\n", $value ) as $tag ) {
							// Clean up any trailing spaces that might exist due to input.
							$tag = trim( $tag );

							// If the tag is a number, check it exists.
							if ( is_numeric( $tag ) && array_key_exists( (int) $tag, $api_tags ) ) {
								$args['tags'][] = (int) $tag;
								continue;
							}

							// The tag is a string, or a number that is not a tag ID; attempt to find its ID.
							foreach ( $api_tags as $tag_id => $api_tag ) {
								if ( $api_tag['name'] === $tag ) {
									$args['tags'][] = (int) $tag_id;
									continue;
								}
							}
						}

						// If no tags were assigned, remove the tag field.
						if ( count( $args['tags'] ) === 0 ) {
							unset( $args['tags'] );
						}
						break;

					/**
					 * Custom Fields
					 */
					default:
						// Sanity check this field is a ConvertKit Custom Field.
						if ( strpos( $convertkit_field, 'custom_field_' ) === false ) {
							break;
						}

						// Define custom field array if not yet defined.
						if ( ! array_key_exists( 'fields', $args ) ) {
							$args['fields'] = array();
						}

						// Add custom field.
						$args['fields'][ str_replace( 'custom_field_', '', $convertkit_field ) ] = $value;
						break;
				}
			}

			// Skip if no email address field was mapped.
			if ( ! array_key_exists( 'email', $args ) ) {
				continue;
			}

			// Skip if the email address field is blank.
			if ( empty( $args['email'] ) ) {
				continue;
			}

			// Maintain backward compatibility for form fields that specify tags or custom fields using CSS classes.
			// @link https://www.billerickson.net/setup-convertkit-wordpress-form/#custom-fields-and-tags.
			foreach ( $form_data['fields'] as $i => $field ) {
				if ( empty( $field['css'] ) ) {
					continue;
				}

				$value   = ! empty( $fields[ $i ]['value_raw'] ) ? $fields[ $i ]['value_raw'] : $fields[ $i ]['value'];
				$classes = explode( ' ', $field['css'] );
				foreach ( $classes as $class ) {

					// Custom Fields.
					if ( false !== strpos( $class, 'ck-custom-' ) ) {
						$key                    = str_replace( 'ck-custom-', '', $class );
						$args['fields'][ $key ] = $value;
					}

					// Tags.
					if ( 'ck-tag' === $class ) {
						$args['tags'][] = $value;
					}
				}
			}

			// Filter for customizing arguments.
			// @link https://www.billerickson.net/code/integrate-convertkit-wpforms-custom-fields/.
			$args = apply_filters( 'be_convertkit_form_args', $args, $fields, $form_data );

			// Filter for limiting integration.
			// @link https://www.billerickson.net/code/integrate-convertkit-wpforms-conditional-processing/.
			if ( ! apply_filters( 'be_convertkit_process_form', true, $fields, $form_data ) ) {
				continue;
			}

			// Send data to ConvertKit to subscribe the email address to the ConvertKit Form.
			// For Legacy Forms, a different endpoint is used.
			if ( $resource_forms->is_legacy( (int) $connection['list_id'] ) ) {
				$response = $api->legacy_form_subscribe(
					(int) $connection['list_id'],
					$args['email'],
					( isset( $args['name'] ) ? $args['name'] : '' ),
					( isset( $args['fields'] ) ? $args['fields'] : array() )
				);
			} else {
				$response = $api->form_subscribe(
					(int) $connection['list_id'],
					$args['email'],
					( isset( $args['name'] ) ? $args['name'] : '' ),
					( isset( $args['fields'] ) ? $args['fields'] : array() )
				);
			}

			// If the API response is an error, log it as an error.
			if ( is_wp_error( $response ) ) {
				wpforms_log(
					'ConvertKit',
					sprintf(
						'API Error: %s',
						$response->get_error_message()
					),
					array(
						'type'    => array( 'provider', 'error' ),
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					)
				);

				return;
			}

			// Assign tags to the subscriber, if any exist.
			if ( isset( $args['tags'] ) ) {
				foreach ( $args['tags'] as $tag_id ) {
					// Assign tag to subscriber.
					$response = $api->tag_subscriber( $tag_id, $response['subscriber']['id'] );

					// If the API response is an error, log it as an error.
					if ( is_wp_error( $response ) ) {
						wpforms_log(
							'ConvertKit',
							sprintf(
								'API Error: %s',
								$response->get_error_message()
							),
							array(
								'type'    => array( 'provider', 'error' ),
								'parent'  => $entry_id,
								'form_id' => $form_data['id'],
							)
						);
					}
				}
			}

			// Email subscribed to ConvertKit successfully; request a review.
			// This can safely be called multiple times, as the review request
			// class will ensure once a review request is dismissed by the user,
			// it is never displayed again.
			if ( $this->review_request ) {
				$this->review_request->request_review();
			}

			// Log successful API response.
			wpforms_log(
				'ConvertKit',
				$response,
				array(
					'type'    => array( 'provider', 'log' ),
					'parent'  => $entry_id,
					'form_id' => $form_data['id'],
				)
			);
		}

	}

	/**
	 * Returns the value for the given field in a WPForms form entry.
	 *
	 * @since   1.5.0
	 *
	 * @param   string $field      Field Name.
	 * @param   array  $fields     Fields and their submitted form values.
	 * @return  bool|string             Field Value
	 */
	private function get_entry_field_value( $field, $fields ) {

		$field = explode( '.', $field );
		$id    = $field[0];

		// Determine the field ID's key that stores the submitted value for this field.
		$key = 'value';
		if ( ! empty( $field[1] ) ) {
			$key = $field[1];
		} elseif ( array_key_exists( 'value_raw', $fields[ $id ] ) ) {
			// Some fields, such as checkboxes, radio buttons and select fields, may
			// have a different value defined vs. the label. Using 'value_raw' will
			// always fetch the value, if "Show Values" is enabled in WPForms,
			// falling back to the label if "Show Values" is disabled.
			$key = 'value_raw';
		}

		// Check if mapped form field has a value.
		if ( empty( $fields[ $id ][ $key ] ) ) {
			return false;
		}

		return $fields[ $id ][ $key ];

	}

	/**
	 * Output fields at WPForms > Settings > Integrations > ConvertKit,
	 * allowing the user to enter their ConvertKit API Key.
	 *
	 * @since   1.5.0
	 */
	public function integrations_tab_new_form() {

		// Initialize API to generate OAuth URL.
		$api = new Integrate_ConvertKit_WPForms_API(
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_CLIENT_ID,
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_REDIRECT_URI
		);

		require INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/views/backend/settings-integration.php';

	}

	/**
	 * Output groups for this provider.
	 *
	 * @since   1.5.0
	 *
	 * @param string $connection_id WPForms ConvertKit Connection ID.
	 * @param array  $connection    WPForms ConvertKit Connection Settings (API Key, Secret etc).
	 * @return string
	 */
	public function output_groups( $connection_id = '', $connection = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// ConvertKit doesn't have a concept of Groups, so we don't output anything for configuring this.
		return '';

	}

	/**
	 * Outputs instructions for connecting a form to a ConvertKit account when editing the WPForms Form
	 * at WPForms > Add/Edit a Form > Marketing > ConvertKit.
	 *
	 * @since   1.5.0
	 *
	 * @param   string $content    Content.
	 * @return  string              Content
	 */
	public function builder_settings_default_content( $content ) {

		ob_start();
		?>
		<p>
			<a href="<?php echo esc_url( $this->register_url ); ?>" class="wpforms-btn wpforms-btn-md wpforms-btn-orange" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Try ConvertKit for Free', 'integrate-convertkit-wpforms' ); ?>
			</a>
		</p>
		<?php

		return $content . ob_get_clean();

	}

	/**
	 * Outputs a <select> dropdown of ConvertKit Forms, allowing the user
	 * to choose which ConvertKit Form to send form submissions to.
	 *
	 * @since   1.5.0
	 *
	 * @param   string $connection_id WPForms ConvertKit Connection ID.
	 * @param   array  $connection    WPForms ConvertKit Connection Settings (API Key, Secret etc).
	 * @return  WP_Error|string
	 */
	public function output_lists( $connection_id = '', $connection = array() ) {

		// Don't output a dropdown list if no connection exists.
		if ( empty( $connection_id ) || empty( $connection['account_id'] ) ) {
			return '';
		}

		// Get API instance.
		$api = $this->get_api_instance( $connection['account_id'] );

		// Bail if we couldn't fetch the API instance.
		if ( is_wp_error( $api ) ) {
			return '';
		}

		// Fetch Forms.
		// We use refresh() to ensure we get the latest data, as we're in the admin interface
		// and need to populate the select dropdown.
		$forms = new Integrate_ConvertKit_WPForms_Resource_Forms( $api, $connection['account_id'] );
		$forms->refresh();

		// Fetch Tags.
		// We use refresh() to ensure we get the latest data, as we're in the admin interface.
		// When the frontend then queries the resource class, it'll get the most up to date
		// tag data without needing to make an API call.
		$tags = new Integrate_ConvertKit_WPForms_Resource_Tags( $api, $connection['account_id'] );
		$tags->refresh();

		// Get the selected ConvertKit subscribe setting, if one was already defined.
		$value = ! empty( $connection['list_id'] ) ? $connection['list_id'] : '';

		// Output <select> dropdown.
		ob_start();
		require INTEGRATE_CONVERTKIT_WPFORMS_PATH . '/views/backend/settings-form-marketing-forms-dropdown.php';
		return ob_get_clean();

	}

	/**
	 * Enqueue CSS for the integration screen.
	 *
	 * @since   1.7.0
	 */
	public function enqueue_assets() {

		// Enqueue CSS.
		wp_enqueue_style( 'ckwc-admin', INTEGRATE_CONVERTKIT_WPFORMS_URL . 'resources/backend/css/admin.css', array(), INTEGRATE_CONVERTKIT_WPFORMS_VERSION );

	}

	/**
	 * Displays a notice on the integration screen, depending on whether OAuth succeeded or not.
	 *
	 * @since   1.7.0
	 */
	public function maybe_display_notice() {

		// Display success message if required.
		if ( array_key_exists( 'success', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			\WPForms\Admin\Notice::success(
				esc_html__( 'ConvertKit: Account connected successfully.', 'integrate-convertkit-wpforms' )
			);
		}

		// Display error message if required.
		if ( array_key_exists( 'error_description', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			\WPForms\Admin\Notice::error(
				sprintf(
					'%s %s',
					esc_html__( 'ConvertKit: ', 'integrate-convertkit-wpforms' ),
					sanitize_text_field( $_REQUEST['error_description'] ) // phpcs:ignore WordPress.Security.NonceVerification
				)
			);
		}

	}

	/**
	 * Requests an access token via OAuth, if an authorization code and verifier are included in the request.
	 *
	 * @since   1.7.0
	 */
	public function maybe_get_and_store_access_token() {

		// Bail if we're not on the integration screen.
		if ( ! array_key_exists( 'page', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( $_REQUEST['page'] !== 'wpforms-settings' ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		if ( ! array_key_exists( 'view', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( $_REQUEST['view'] !== 'integrations' ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Bail if no authorization code is included in the request.
		if ( ! array_key_exists( 'code', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Sanitize token.
		$authorization_code = sanitize_text_field( $_REQUEST['code'] ); // phpcs:ignore WordPress.Security.NonceVerification

		// Exchange the authorization code and verifier for an access token.
		$api    = new Integrate_ConvertKit_WPForms_API(
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_CLIENT_ID,
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_REDIRECT_URI
		);
		$result = $api->get_access_token( $authorization_code );

		// Redirect with an error if we could not fetch the access token.
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				$this->get_integrations_url(
					array(
						'error' => $result->get_error_code(),
					)
				)
			);
			exit();
		}

		// Re-initialize the API with the tokens.
		$api = new Integrate_ConvertKit_WPForms_API(
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_CLIENT_ID,
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_REDIRECT_URI,
			sanitize_text_field( $result['access_token'] ),
			sanitize_text_field( $result['refresh_token'] )
		);

		// Fetch account.
		$account = $api->get_account();

		// Redirect with an error if we could not fetch the account.
		if ( is_wp_error( $account ) ) {
			wp_safe_redirect(
				$this->get_integrations_url(
					array(
						'error' => $account->get_error_code(),
					)
				)
			);
			exit();
		}

		// Update the provider's settings and return its unique ID.
		$id = uniqid();
		wpforms_update_providers_options(
			$this->slug,
			array(
				'access_token'  => sanitize_text_field( $result['access_token'] ),
				'refresh_token' => sanitize_text_field( $result['refresh_token'] ),
				'token_expires' => ( $result['created_at'] + $result['expires_in'] ),
				'label'         => $account['account']['name'],
				'date'          => time(),
			),
			$id
		);

		// Reload the integrations screen, which will now show the connection.
		wp_safe_redirect(
			$this->get_integrations_url(
				array(
					'success' => '1',
				)
			)
		);
		exit();

	}

	/**
	 * Deletes cached resources when a ConvertKit account is disconnected in WPForms
	 * by the user clicking `Disconnect`.
	 *
	 * @since   1.7.0
	 */
	public function delete_resource_cache() {

		// Run a security check.
		if ( ! check_ajax_referer( 'wpforms-admin', 'nonce', false ) ) {
			return;
		}

		// Check for permissions.
		if ( ! wpforms_current_user_can() ) {
			return;
		}

		// Bail if no provider supplied.
		if ( empty( $_POST['provider'] ) || empty( $_POST['key'] ) ) {
			return;
		}

		// Sanitize data.
		$account_id = sanitize_text_field( $_POST['key'] );

		// Get API instance.
		$api = $this->get_api_instance( $account_id );

		// Delete cached resources.
		$resource_forms         = new Integrate_ConvertKit_WPForms_Resource_Forms( $api, $account_id );
		$resource_tags          = new Integrate_ConvertKit_WPForms_Resource_Tags( $api, $account_id );
		$resource_custom_fields = new Integrate_ConvertKit_WPForms_Resource_Custom_Fields( $api, $account_id );
		$resource_forms->delete();
		$resource_tags->delete();
		$resource_custom_fields->delete();
	}

	/**
	 * Returns available field mappings between a WPForms Form and ConvertKit
	 * Form Fields (First Name, Email), Custom Fields and Tags.
	 *
	 * @since   1.5.0
	 *
	 * @param   string $connection_id      WPForms ConvertKit Connection ID (connection_ID, where ID is defined in api_auth()).
	 * @param   string $account_id         WPForms ConvertKit Account ID (the ID defined in api_auth()).
	 * @param   string $list_id            WPForms ConvertKit Form ID (the ConvertKit Form selected to send entries to).
	 * @return  WP_Error|array
	 */
	public function api_fields( $connection_id = '', $account_id = '', $list_id = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$provider_fields = array(
			array(
				'name'       => __( 'ConvertKit: Email', 'integrate-convertkit-wpforms' ),
				'field_type' => 'email',
				'req'        => '1',
				'tag'        => 'email',
			),
			array(
				'name'       => __( 'ConvertKit: First Name', 'integrate-convertkit-wpforms' ),
				'field_type' => 'text',
				'tag'        => 'name',
			),
			array(
				'name'       => __( 'ConvertKit: Tag', 'integrate-convertkit-wpforms' ),
				'field_type' => 'text',
				'tag'        => 'tag',
			),
		);

		// Get API instance.
		$api = $this->get_api_instance( $account_id );

		// Just return fields if we couldn't fetch the API instance.
		if ( is_wp_error( $api ) ) {
			return $provider_fields;
		}

		// Fetch Custom Fields.
		$resource_custom_fields = new Integrate_ConvertKit_WPForms_Resource_Custom_Fields( $api, $account_id );
		$custom_fields          = $resource_custom_fields->refresh();

		// Just return fields if no custom fields exist in ConvertKit.
		if ( ! count( $custom_fields ) ) {
			return $provider_fields;
		}

		// Add Custom Fields to available field mappings.
		foreach ( $custom_fields as $custom_field ) {
			$provider_fields[] = array(
				'name'       => sprintf(
					/* translators: ConvertKit Custom Field label */
					__( 'ConvertKit: Custom Field: %s', 'integrate-convertkit-wpforms' ),
					$custom_field['label']
				),
				'field_type' => 'text',
				'tag'        => 'custom_field_' . $custom_field['key'],
			);
		}

		return $provider_fields;

	}

	/**
	 * Fetches the API for the given WPForms Account ID.
	 *
	 * If it has not been initialized, performs initialization of the ConvertKit API
	 * class with the account's API Key and Secret.
	 *
	 * @since   1.5.0
	 *
	 * @param   string $account_id         WPForms ConvertKit Account ID (the ID defined in api_auth()).
	 * @return  WP_Error|Integrate_ConvertKit_WPForms_API
	 */
	private function get_api_instance( $account_id ) {

		// Return API instance if already configured.
		if ( array_key_exists( $account_id, $this->api ) ) {
			return $this->api[ $account_id ];
		}

		// Get all registered providers in WPForms.
		$providers = wpforms_get_providers_options();

		// Bail if no ConvertKit providers were registered.
		if ( ! array_key_exists( $this->slug, $providers ) ) {
			return $this->error( __( 'No ConvertKit connections exist.', 'integrate-convertkit-wpforms' ) );
		}

		// Bail if the requested connection does not exist.
		if ( ! array_key_exists( $account_id, $providers[ $this->slug ] ) ) {
			return $this->error(
				sprintf(
					/* translators: WPForms ConvertKit Account ID */
					__( 'The ConvertKit connection with ID %s was unregistered.', 'integrate-convertkit-wpforms' ),
					$account_id
				)
			);
		}

		// Setup the API instance for this connection.
		$this->api[ $account_id ] = new Integrate_ConvertKit_WPForms_API(
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_CLIENT_ID,
			INTEGRATE_CONVERTKIT_WPFORMS_OAUTH_REDIRECT_URI,
			$providers[ $this->slug ][ $account_id ]['access_token'],
			$providers[ $this->slug ][ $account_id ]['refresh_token']
		);

		// Return instance.
		return $this->api[ $account_id ];

	}

	/**
	 * Returns the URL for the WPForms > Settings > Integrations screen.
	 *
	 * @since   1.7.0
	 *
	 * @param   array $args   Optional URL arguments to include in the URL.
	 * @return  string
	 */
	private function get_integrations_url( $args = array() ) {

		return add_query_arg(
			array_merge(
				array(
					'page' => 'wpforms-settings',
					'view' => 'integrations',
				),
				$args
			),
			admin_url( 'admin.php' )
		);

	}

}

new Integrate_ConvertKit_WPForms();
