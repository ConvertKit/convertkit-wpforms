<?php
/**
 * ConvertKit Custom Fields Resource class.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

/**
 * Reads ConvertKit Custom Fields from the options table, and refreshes
 * ConvertKit Custom Fields data stored locally from the API.
 *
 * @since   1.7.0
 */
class Integrate_ConvertKit_WPForms_Resource_Custom_Fields extends Integrate_ConvertKit_WPForms_Resource {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 *
	 * @since   1.7.0
	 *
	 * @var     string
	 */
	public $settings_name = 'integrate_convertkit_wpforms_custom_fields';

	/**
	 * The type of resource
	 *
	 * @since   1.7.0
	 *
	 * @var     string
	 */
	public $type = 'custom_fields';

	/**
	 * The key to use when alphabetically sorting resources.
	 *
	 * @since   1.7.0
	 *
	 * @var     string
	 */
	public $order_by = 'label';

}
