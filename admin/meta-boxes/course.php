<?php
wp_nonce_field( 'ib_educator_course_meta_box', 'ib_educator_course_meta_box_nonce' );

$price = ib_edu_get_course_price( $post->ID );
?>
<div class="ib-edu-field">
	<div class="ib-edu-label"><label for="ib-educator-price"><?php _e( 'Price', 'ibeducator' ); ?></label></div>
	<div class="ib-edu-control">
		<input type="text" id="ib-educator-price" name="_ibedu_price" value="<?php echo esc_attr( $price ); ?>">
	</div>
</div>

<div class="ib-edu-field">
	<div class="ib-edu-label"><label for="ib-educator-difficulty"><?php _e( 'Difficulty', 'ibeducator' ); ?></label></div>
	<div class="ib-edu-control">
		<?php
			$difficulty = get_post_meta( $post->ID, '_ib_educator_difficulty', true );
			$difficulty_levels = ib_edu_get_difficulty_levels();
		?>
		<select id="ib-educator-difficulty" name="_ib_educator_difficulty">
			<option value=""><?php _e( 'None', 'ibeducator' ); ?></option>
			<?php
				foreach ( $difficulty_levels as $key => $label ) {
					echo '<option value="' . esc_attr( $key ) . '"' . ( $key == $difficulty ? ' selected="selected"' : '' ) . '>' . esc_html( $label ) . '</option>';
				}
			?>
		</select>
	</div>
</div>