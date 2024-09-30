<?php
/**
 * ConvertKit Sequences Sequences class.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

/**
 * Reads ConvertKit Sequences from the options table, and refreshes
 * ConvertKit Sequences data stored locally from the API.
 *
 * @since   1.7.2
 */
class Integrate_ConvertKit_WPForms_Resource_Sequences extends Integrate_ConvertKit_WPForms_Resource {

	/**
	 * Holds the Settings Key that stores site wide ConvertKit settings
	 *
	 * @since   1.7.2
	 *
	 * @var     string
	 */
	public $settings_name = 'integrate_convertkit_wpforms_sequences';

	/**
	 * The type of resource
	 *
	 * @since   1.7.2
	 *
	 * @var     string
	 */
	public $type = 'sequences';

}
