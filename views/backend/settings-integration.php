<?php
/**
 * Outputs settings fields at WPForms > Settings > Integrations > ConvertKit.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

printf(
	'<input type="text" name="api_key" placeholder="%s %s *" class="wpforms-required">',
	esc_attr( $this->name ),
	esc_attr__( 'API Key', 'integrate-convertkit-wpforms' )
);
?>
<span class="description">
	<?php
	printf(
		/* translators: %1$s: Link to ConvertKit Account */
		esc_html__( '%1$s Required for proper plugin function.', 'integrate-convertkit-wpforms' ),
		'<a href="' . esc_url( $this->api_key_url ) . '" target="_blank">' . esc_html__( 'Get your ConvertKit API Key.', 'integrate-convertkit-wpforms' ) . '</a>'
	);
	?>
</span>

<?php
printf(
	'<input type="text" name="api_secret" placeholder="%s %s *" class="wpforms-required">',
	esc_attr( $this->name ),
	esc_attr__( 'API Secret', 'integrate-convertkit-wpforms' )
);
?>
<span class="description">
	<?php
	printf(
		/* translators: %1$s: Link to ConvertKit Account */
		esc_html__( '%1$s Required for proper plugin function.', 'integrate-convertkit-wpforms' ),
		'<a href="' . esc_url( $this->api_key_url ) . '" target="_blank">' . esc_html__( 'Get your ConvertKit API Secret.', 'integrate-convertkit-wpforms' ) . '</a>'
	);
	?>
</span>
<?php


