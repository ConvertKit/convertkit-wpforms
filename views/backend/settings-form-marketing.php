<?php
/**
 * Outputs settings fields when adding/editing a WPForms Form at Marketing > Kit > Add New Connection.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

?>
<div class="wpforms-provider-account-add <?php echo sanitize_html_class( $class ); ?> wpforms-connection-block">
	<?php
	printf(
		'<input type="text" data-name="api_key" placeholder="%s %s *" class="wpforms-required">',
		esc_attr( $this->name ),
		esc_attr__( 'API Key', 'integrate-convertkit-wpforms' )
	);
	?>
	<span class="description">
		<?php
		printf(
			/* translators: %1$s: Link to Kit Account */
			esc_html__( '%1$s Required for proper plugin function.', 'integrate-convertkit-wpforms' ),
			'<a href="' . esc_url( $this->api_key_url ) . '" target="_blank">' . esc_html__( 'Get your Kit API Key.', 'integrate-convertkit-wpforms' ) . '</a>'
		);
		?>
	</span>

	<?php
	printf(
		'<input type="text" data-name="api_secret" placeholder="%s %s *" class="wpforms-required">',
		esc_attr( $this->name ),
		esc_attr__( 'API Secret', 'integrate-convertkit-wpforms' )
	);
	?>
	<span class="description">
		<?php
		printf(
			/* translators: %1$s: Link to Kit Account */
			esc_html__( '%1$s Required for proper plugin function.', 'integrate-convertkit-wpforms' ),
			'<a href="' . esc_url( $this->api_key_url ) . '" target="_blank">' . esc_html__( 'Get your Kit API Secret.', 'integrate-convertkit-wpforms' ) . '</a>'
		);
		?>
	</span>

	<?php
	printf(
		'<button data-provider="%s">%s</button>',
		esc_attr( $this->slug ),
		esc_html__( 'Connect to Kit', 'integrate-convertkit-wpforms' )
	);
	?>
</div>
