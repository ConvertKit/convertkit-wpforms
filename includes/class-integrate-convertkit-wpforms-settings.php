<?php
/**
 * ConvertKit WPForms Settings class.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

/**
 * Registers settings when editing a form at Settings > ConvertKit.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */
class Integrate_ConvertKit_WPForms_Settings {

	/**
	 * The slug / key that holds settings and this integration's provider settings,
	 * matching Integrate_ConvertKit_WPForms::$slug.
	 * 
	 * @since 	1.5.8
	 * 
	 * @var 	string
	 */
	private $settings_key = 'convertkit';

	/**
	 * Holds the key to store the Creator Network Recommendations JS URL in.
	 *
	 * @since   1.5.8
	 *
	 * @var     string
	 */
	private $creator_network_recommendations_script_key = 'creator_network_recommendations_script';

	/**
	 * Holds the URL to the WPForms Integrations screen.
	 * 
	 * @since 	1.5.8
	 * 
	 * @var 	string
	 */
	private $integrations_link = 'admin.php?page=wpforms-settings&view=integrations';

	/**
	 * Constructor
	 *
	 * @since   1.5.8
	 */
	public function __construct() {

		add_filter( 'wpforms_builder_settings_sections', array( $this, 'settings_section' ), 20, 1 );
        add_filter( 'wpforms_form_settings_panel_content', array( $this, 'settings_section_content' ), 20 );

	}

	/**
	 * Registers a ConvertKit settings section when editing a WPForms Form at Settings > Convertkit.
	 * 
	 * @since 	1.5.8
	 * 
	 * @param 	array 	$sections 	Settings sections.
	 * @return 	array 				Settings sections
	 */
	public function settings_section( $sections ) {

		$sections[ $this->settings_key ] = __( 'ConvertKit', 'integrate-convertkit-wpforms' );
        return $sections;

	}

	/**
	 * Outputs fields for the ConvertKit settings section when editing a WPForms Form at Settings > Convertkit.
	 * 
	 * @since 	1.5.8
	 * 
	 * @param 	WPForms_Builder_Panel_Settings 	$instance 	WPForms Form Builder Settings Instance.
	 */
    public function settings_section_content( $instance ) {

        echo '<div class="wpforms-panel-content-section wpforms-panel-content-section-convertkit">';
        echo '<div class="wpforms-panel-content-section-title">' . __( 'ConvertKit', 'integrate-convertkit-wpforms' ) . '</div>';

    	// If no provider is specified for ConvertKit at WPForms > Settings > Integrations > ConvertKit,
    	// don't show an option.
    	if ( ! $this->has_provider() ) {
    		echo sprintf(
				'%s <a href="%s">%s</a>',
				esc_html__( 'Please connect your ConvertKit account on the', 'integrate-convertkit-wpforms' ),
				esc_url( admin_url( $this->integrations_link ) ),
				esc_html__( 'integrations screen', 'integrate-convertkit-wpforms' )
			);
			echo '</div>';
			return;
    	}

    	// Output provider <select> field, where the user can select which ConvertKit credentials to use.
    	wpforms_panel_field(
            'select',
            'settings',
			$this->settings_key . '_connection_id',
			$instance->form_data,
            __( 'Connection', 'integrate-convertkit-wpforms' ),
            array( 
            	'options' => $this->get_providers(),
            	'placeholder' => __( '-- Select Connection --', 'integrate-convertkit-wpforms' ),
            )
        );

        // If no connection specified, don't show the Creator Network Recommendations option.
        if ( ! array_key_exists( $this->settings_key . '_connection_id', $instance->form_data['settings'] ) ) {
        	echo '</div>';
			return;
        }
        if ( empty( $instance->form_data['settings'][ $this->settings_key . '_connection_id'] ) ) {
        	echo '</div>';
			return;
        }

    	// Query API to fetch Creator Network Recommendations script.
		$result = $this->get_creator_network_recommendations_script( $instance->form_data['settings'][ $this->settings_key . '_connection_id'], true );

		// If an error occured, don't show an option.
		if ( is_wp_error( $result ) ) {
			printf(
				'%s. <a href="%s">%s</a>',
				$result->get_error_message(),
				esc_url( admin_url( $this->integrations_link ) ),
				esc_html__( 'Fix settings', 'integrate-convertkit-wpforms' )
			);
			echo '</div>';
			return;
		}

		// If the result is false, the Creator Network is disabled - don't show an option.
		if ( ! $result ) {
			printf(
				'%s <a href="%s">%s</a>',
				esc_html__( 'Creator Network Recommendations requires a', 'integrate-convertkit-wpforms' ),
				esc_url( ckgf_get_settings_billing_url() ), // @TODO.
				esc_html__( 'paid ConvertKit Plan', 'integrate-convertkit-wpforms' )
			);
			echo '</div>';
			return;
		}

   		// Creator Network enabled.
   		// Show option.
    	wpforms_panel_field(
			'toggle',
			'settings',
			$this->settings_key . '_' . $this->creator_network_recommendations_script_key,
			$instance->form_data,
			esc_html__( 'Enable Creator Network Recommendations', 'integrate-convertkit-wpforms' ),
			array(
				'tooltip' => esc_html__( 'If enabled, displays the Creator Network Recommendations modal when this form is submitted.', 'integrate-convertkit-wpforms' )
			)
		);

    	echo '</div>';

    }

	/**
	 * Enqueues the Creator Network Recommendations script, if the WPForms form
	 * has the 'Enable Creator Network Recommendations' setting enabled.
	 *
	 * @since   1.5.8
	 *
	 * @param   array $form       WPForms Form.
	 * @param   bool  $is_ajax    If AJAX is enabled for form submission.
	 */
    public function maybe_enqueue_creator_network_recommendations_script() {

		// Bail if AJAX submission is disabled; we can't show the Creator Network Recommendations
		// if the page reloads on form submission.
		if ( ! $is_ajax ) {
			return;
		}

		// Bail if Creator Network Recommendations are disabled.
		if ( ! array_key_exists( 'ckgf_enable_creator_network_recommendations', $form ) ) {
			return;
		}
		if ( ! $form['ckgf_enable_creator_network_recommendations'] ) {
			return;
		}

		// Fetch Creator Network Recommendations script URL.
		$script_url = $this->get_creator_network_recommendations_script();

		// Bail if an error occured fetching the script, or no script exists,
		// because Creator Network Recommendations are not enabled on the
		// ConvertKit account.
		if ( is_wp_error( $script_url ) ) {
			return;
		}
		if ( ! $script_url ) {
			return;
		}

		// Enqueue script.
		wp_enqueue_script( 'convertkit-wpforms-creator-network-recommendations', $script_url, array(), CKGF_PLUGIN_VERSION, true );

    }

    /**
     * Checks if this integration has a provider registered at
     * WPForms > Settings > Integrations > ConvertKit.
     * 
     * @since 	1.5.8
     */
    private function has_provider() {

    	// Get providers registered at WPForms > Settings > Integrations.
    	$providers = wpforms_get_providers_options();

    	// Bail if no providers exist.
    	if ( empty( $providers ) ) {
    		return false;
    	}

    	if ( ! array_key_exists( $this->settings_key, $providers ) ) {
    		return false;
    	}

    	return true;
    	
    }

    /**
     * Checks if this integration has a provider registered at
     * WPForms > Settings > Integrations > ConvertKit.
     * 
     * @since 	1.5.8
     */
    private function get_providers() {

    	// Bail if no providers exist for this integration.
    	if ( ! $this->has_provider() ) {
    		return false;
    	}

    	// Get providers registered at WPForms > Settings > Integrations.
    	$providers = wpforms_get_providers_options();

    	// Build array compatible with wpforms_panel_field().
    	$connections = array();
    	foreach ( $providers[ $this->settings_key ] as $account_id => $details ) {
    		$connections[ $account_id ] = sprintf(
    			'%s: %s: %s',
    			$details['label'],
    			esc_html__( 'Connected on', 'integrate-convertkit-wpforms' ),
    			date( 'dS F, Y', $details['date'] )
    		);
    	}

    	return $connections;

    }

    /**
     * Returns the given connection registered at
     * WPForms > Settings > Integrations > ConvertKit.
     * 
     * @since 	1.5.8
     */
    private function get_provider( $account_id ) {

    	// Get all registered providers in WPForms.
		$providers = wpforms_get_providers_options();

		// Bail if no ConvertKit providers were registered.
		if ( ! array_key_exists( $this->settings_key, $providers ) ) {
			return false;
		}

		// Bail if the requested connection does not exist.
		if ( ! array_key_exists( $account_id, $providers[ $this->settings_key ] ) ) {
			return false;
		}

		return $providers[ $this->settings_key ][ $account_id ];

    }

	/**
	 * Fetches the Creator Network Recommendations script from the database, falling
	 * back to an API query if the database doesn't have a copy of it stored.
	 *
	 * @since   1.5.8
	 *
	 * @param   bool $force  If enabled, queries the API instead of checking the cached data.
	 *
	 * @return  WP_Error|bool|string
	 */
	private function get_creator_network_recommendations_script( $account_id, $force = true ) {

		// Get Creator Network Recommendations script URL.
		if ( ! $force ) {
			$script_url = get_option( $this->creator_network_recommendations_script_key );
			if ( $script_url ) {
				return $script_url;
			}
		}

		// Get connection.
		$provider = $this->get_provider( $account_id );
		if ( ! $provider ) {
			return new WP_Error(
				'integrate_convertkit_wpforms_settings_connection_missing',
				__( 'The connection specified in the Form\'s Settings > ConvertKit does not exist', 'integrate-convertkit-wpforms' )
			);
		}

		// No cached script, or we're forcing an API query; fetch from the API.
		$api = new Integrate_ConvertKit_WPForms_API(
			$provider['api_key'],
			$provider['api_secret']
		);

		// Sanity check that we're using the ConvertKit WordPress Libraries 1.3.7 or higher.
		// If another ConvertKit Plugin is active and out of date, its libraries might
		// be loaded that don't have this method.
		if ( ! method_exists( $api, 'recommendations_script' ) ) {
			delete_option( $this->creator_network_recommendations_script_key );
			return false;
		}

		// Get script from API.
		$result = $api->recommendations_script();

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			delete_option( $this->creator_network_recommendations_script_key );
			return $result;
		}

		// Bail if not enabled.
		if ( ! $result['enabled'] ) {
			delete_option( $this->creator_network_recommendations_script_key );
			return false;
		}

		// Store script URL, as Creator Network Recommendations are available on this account.
		update_option( $this->creator_network_recommendations_script_key, $result['embed_js'] );

		// Return.
		return $result['embed_js'];

	}

}

new Integrate_ConvertKit_WPForms_Settings();
