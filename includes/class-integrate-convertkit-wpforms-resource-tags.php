<?php
/**
 * ConvertKit Tags Resource class.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

/**
 * Reads ConvertKit Tags from the options table, and refreshes
 * ConvertKit Tags data stored locally from the API.
 *
 * @since   1.7.0
 */
class Integrate_ConvertKit_WPForms_Resource_Tags extends Integrate_ConvertKit_WPForms_Resource {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 *
	 * @since   1.7.0
	 *
	 * @var     string
	 */
	public $settings_name = 'integrate_convertkit_wpforms_tags';

	/**
	 * The type of resource
	 *
	 * @since   1.7.0
	 *
	 * @var     string
	 */
	public $type = 'tags';

}
