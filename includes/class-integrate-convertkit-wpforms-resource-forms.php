<?php
/**
 * ConvertKit Forms Resource class.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

/**
 * Reads ConvertKit Forms from the options table, and refreshes
 * ConvertKit Forms data stored locally from the API.
 *
 * @since   1.7.0
 */
class Integrate_ConvertKit_WPForms_Resource_Forms extends Integrate_ConvertKit_WPForms_Resource {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 *
	 * @since   1.7.0
	 *
	 * @var     string
	 */
	public $settings_name = 'integrate_convertkit_wpforms_forms';

	/**
	 * The type of resource
	 *
	 * @since   1.7.0
	 *
	 * @var     string
	 */
	public $type = 'forms';

	/**
	 * Determines if the given Form ID is a legacy Form or Landing Page.
	 *
	 * @since   1.7.0
	 *
	 * @param   int $id     Form or Landing Page ID.
	 */
	public function is_legacy( $id ) {

		// Get Form.
		$form = $this->get_by_id( (int) $id );

		// Return false if no Form exists.
		if ( ! $form ) {
			return false;
		}

		// If the `format` key exists, this is not a legacy Form.
		if ( array_key_exists( 'format', $form ) ) {
			return false;
		}

		return true;

	}

}
