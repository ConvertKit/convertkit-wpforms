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
		<?php
		foreach ( $forms as $form ) {
			?>
			<option value="<?php echo esc_attr( $form['id'] ); ?>"<?php selected( $form['id'], $form_id ); ?>><?php echo esc_attr( $form['name'] ); ?></option>
			<?php
		}
		?>
	</select>
</div>
