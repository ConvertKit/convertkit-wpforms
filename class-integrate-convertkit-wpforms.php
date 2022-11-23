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
class Integrate_ConvertKit_WPForms {

	/**
	 * Primary Class Constructor
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		add_filter( 'wpforms_builder_settings_sections', array( $this, 'settings_section' ), 20, 1 );
		add_filter( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );
		add_action( 'wpforms_process_complete', array( $this, 'send_data_to_convertkit' ), 10, 4 );

	}

	/**
	 * Add Settings Section
	 *
	 * @since   1.0.0
	 *
	 * @param   array $sections   WPForms Settings sections.
	 * @return  array               WPForms Settings sections
	 */
	public function settings_section( $sections ) {

		$sections['be_convertkit'] = __( 'ConvertKit', 'integrate-convertkit-wpforms' );
		return $sections;

	}

	/**
	 * ConvertKit Settings Content
	 *
	 * @since   1.0.0
	 *
	 * @param   object $instance   Settings instance.
	 */
	public function settings_section_content( $instance ) {

		echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-be_convertkit">';
		echo '<div class="wpforms-panel-content-section-title">' . esc_html__( 'ConvertKit', 'integrate-convertkit-wpforms' ) . '</div>';

		if ( empty( $instance->form_data['settings']['be_convertkit_api'] ) ) {
			printf(
				'<p>%s <a href="http://mbsy.co/convertkit/28981746" target="_blank" rel="noopener noreferrer">%s</a></p>',
				esc_html__( 'Don\'t have an account?', 'integrate-convertkit-wpforms' ),
				esc_html__( 'Sign up now!', 'integrate-convertkit-wpforms' )
			);
		}

		wpforms_panel_field(
			'text',
			'settings',
			'be_convertkit_api',
			$instance->form_data,
			__( 'ConvertKit API Key', 'integrate-convertkit-wpforms' )
		);

		wpforms_panel_field(
			'text',
			'settings',
			'be_convertkit_form_id',
			$instance->form_data,
			__( 'ConvertKit Form ID', 'integrate-convertkit-wpforms' )
		);

		wpforms_panel_field(
			'select',
			'settings',
			'be_convertkit_field_first_name',
			$instance->form_data,
			__( 'First Name', 'integrate-convertkit-wpforms' ),
			array(
				'field_map'   => array( 'text', 'name' ),
				'placeholder' => __( '-- Select Field --', 'integrate-convertkit-wpforms' ),
			)
		);

		wpforms_panel_field(
			'select',
			'settings',
			'be_convertkit_field_email',
			$instance->form_data,
			__( 'Email Address', 'integrate-convertkit-wpforms' ),
			array(
				'field_map'   => array( 'email' ),
				'placeholder' => __( '-- Select Field --', 'integrate-convertkit-wpforms' ),
			)
		);

		echo '</div>';

	}

	/**
	 * Send form data to ConvertKit when a WPForms form is submitted.
	 *
	 * @since   1.0.0
	 *
	 * @param   array $fields     WPForms Fields.
	 * @param   array $entry      Submitted form data.
	 * @param   array $form_data  WPForms Form configuration.
	 * @param   int   $entry_id   WPForms submitted form data ID.
	 */
	public function send_data_to_convertkit( $fields, $entry, $form_data, $entry_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Get API key and CK Form ID.
		$api_key    = false;
		$ck_form_id = false;
		if ( ! empty( $form_data['settings']['be_convertkit_api'] ) ) {
			$api_key = esc_html( $form_data['settings']['be_convertkit_api'] );
		}
		if ( ! empty( $form_data['settings']['be_convertkit_form_id'] ) ) {
			$ck_form_id = intval( $form_data['settings']['be_convertkit_form_id'] );
		}

		if ( ! ( $api_key && $ck_form_id ) ) {
			return;
		}

		$args = array(
			'api_key' => $api_key,
		);

		// Return early if no email.
		$email_field_id = $form_data['settings']['be_convertkit_field_email'];
		if ( empty( $email_field_id ) || empty( $fields[ $email_field_id ]['value'] ) ) {
			return;
		}

		$args['email'] = $fields[ $email_field_id ]['value'];

		$first_name_field_id = $form_data['settings']['be_convertkit_field_first_name'];
		if ( $first_name_field_id !== '' && ! empty( $fields[ $first_name_field_id ]['value'] ) ) {
			$args['first_name'] = $fields[ $first_name_field_id ]['value'];
		}

		// Custom Fields and tags.
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
			return;
		}

		// Submit to ConvertKit.
		$request = wp_remote_post( add_query_arg( $args, 'https://api.convertkit.com/v3/forms/' . $ck_form_id . '/subscribe' ) );

		if ( function_exists( 'wpforms_log' ) ) {
			wpforms_log(
				'ConvertKit Response',
				$request,
				array(
					'type'    => array( 'provider' ),
					'parent'  => $entry_id,
					'form_id' => $form_data['id'],
				)
			);
		}

	}

}

new Integrate_ConvertKit_WPForms();
