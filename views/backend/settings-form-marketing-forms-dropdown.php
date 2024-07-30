<?php
/**
 * Outputs the ConvertKit Form dropdown field when adding/editing a WPForms Form at Marketing > ConvertKit.
 *
 * @package ConvertKit_WPForms
 * @author ConvertKit
 */

?>
<div class="wpforms-provider-lists wpforms-connection-block">
	<h4><?php esc_html_e( 'ConvertKit Form', 'integrate-convertkit-wpforms' ); ?></h4>

	<select name="providers[<?php echo esc_attr( $this->slug ); ?>][<?php echo esc_attr( $connection_id ); ?>][list_id]" size="1">
		<option <?php selected( 'subscribe', $value ); ?> value="subscribe" data-preserve-on-refresh="1">
			<?php esc_html_e( 'Subscribe', 'integrate-convertkit-wpforms' ); ?>
		</option>

		<optgroup label="<?php esc_attr_e( 'Forms', 'integrate-convertkit-wpforms' ); ?>" id="convertkit-wpforms-forms" data-option-value-prefix="form:">
			<?php
			if ( $forms->exist() ) {
				foreach ( $forms->get() as $form ) {
					printf(
						'<option value="%s"%s>%s [%s]</option>',
						esc_attr( 'form:' . $form['id'] ),
						selected( 'form:' . $form['id'], $value, false ),
						esc_attr( $form['name'] ),
						( ! empty( $form['format'] ) ? esc_attr( $form['format'] ) : 'inline' )
					);
				}
			}
			?>
		</optgroup>
	</select>

	<p class="note">
		<code><?php esc_html_e( 'Subscribe', 'integrate-convertkit-wpforms' ); ?></code>: <?php esc_html_e( 'Subscribes the email address to ConvertKit', 'integrate-convertkit-wpforms' ); ?>
		<br />
		<code><?php esc_html_e( 'Form', 'integrate-convertkit-wpforms' ); ?></code>: <?php esc_html_e( 'Susbcribes the email address to ConvertKit, and adds the subscriber to the ConvertKit Form', 'integrate-convertkit-wpforms' ); ?>
	</p>
</div>
