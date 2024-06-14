<?php
/**
 * ConvertKit Resource class.
 *
 * @package CKWC
 * @author ConvertKit
 */

/**
 * Abstract class defining variables and functions for a ConvertKit API Resource
 * (forms, sequences, tags).
 *
 * @since   1.7.0
 */
class Integrate_ConvertKit_WPForms_Resource extends ConvertKit_Resource_V4 {

	/**
	 * Constructor.
	 *
	 * @since   1.7.0
	 */
	public function __construct( $api_instance ) {

		// Initialize the API using the supplied Integrate_ConvertKit_WPForms_API instance.
		$this->api = $api_instance;

		// Call parent initialization function.
		parent::init();

	}

}
