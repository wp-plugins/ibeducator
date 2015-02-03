<?php
wp_nonce_field( 'ib_edu_membership', 'ib_edu_membership_nonce' );

$ms = IB_Educator_Memberships::get_instance();

// Get membership meta.
$meta = $ms->get_membership_meta( $post->ID );

// Get currency information.
$currency = ib_edu_get_currency();
$currency_symbol = ib_edu_get_currency_symbol( $currency );

// Get membership periods.
$membership_periods = $ms->get_periods();
?>
<div class="ib-edu-field">
	<div class="ib-edu-label">
		<label for="ib-educator-price"><?php _e( 'Price', 'ibeducator' ); ?></label>
	</div>

	<div class="ib-edu-control">
		<?php echo esc_html( $currency_symbol ); ?> <input type="text" id="ib-educator-price" name="_ib_educator_price" value="<?php echo esc_attr( $meta['price'] ); ?>">
	</div>
</div>

<div class="ib-edu-field">
	<div class="ib-edu-label">
		<label for="ib-educator-duration"><?php _e( 'Duration', 'ibeducator' ); ?></label>
	</div>

	<div class="ib-edu-control">
		<input type="text" id="ib-educator-duration" name="_ib_educator_duration" class="small-text" value="<?php echo intval( $meta['duration'] ); ?>">
		<select name="_ib_educator_period">
			<?php
				foreach ( $membership_periods as $mp_value => $mp_name ) {
					$selected = ( $meta['period'] == $mp_value ) ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $mp_value ) . '"' . $selected . '>' . esc_html( $mp_name ) . '</option>';
				}
			?>
		</select>
	</div>
</div>

<div class="ib-edu-field">
	<div class="ib-edu-label">
		<label for="ib-educator-price"><?php _e( 'Categories', 'ibeducator' ); ?></label>
	</div>

	<div class="ib-edu-control">
		<select name="_ib_educator_categories[]" multiple="multiple" size="5">
			<option value=""><?php _e( 'Select Categories', 'ibeducator' ); ?></option>
			<?php
				$terms = get_terms( 'ib_educator_category' );

				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$selected = in_array( $term->term_id, $meta['categories'] ) ? ' selected="selected"' : '';
						echo '<option value="' . intval( $term->term_id ) . '"' . $selected . '>' . esc_html( $term->name ) . '</option>';
					}
				}
			?>
		</select>
	</div>
</div>