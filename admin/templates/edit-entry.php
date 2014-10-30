<?php
	$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;

	if ( ! $entry_id ) return;

	$entry = IB_Educator_Entry::get_instance( $entry_id );

	if ( ! $entry->ID ) return;

	$api = IB_Educator::get_instance();
	$statuses = IB_Educator_Entry::get_statuses();

	if ( ! current_user_can( 'edit_ib_educator_course', $entry->course_id ) ) {
		echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
		return;
	}

	$student = get_user_by( 'id', $entry->user_id );
	$course = get_post( $entry->course_id );
?>
<div class="wrap">
	<h2><?php _e( 'Educator Entries', 'ibeducator' ); ?></h2>

	<?php if ( isset( $_GET['edu-message'] ) && 'saved' == $_GET['edu-message'] ) : ?>
	<div id="message" class="updated below-h2">
		<p><?php _e( 'Entry updated.', 'ibeducator' ); ?></p>
	</div>
	<?php endif; ?>

	<form id="edu_edit_entry_form" class="ib-edu-admin-form" action="<?php echo admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . $entry_id ); ?>" method="post">
		<?php wp_nonce_field( 'ib_educator_edit_entry_' . $entry->ID ); ?>
		
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label><?php _e( 'Student', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<?php
					$username = '';

					if ( $student->first_name && $student->last_name ) {
						$username = $student->first_name . ' ' . $student->last_name;
					} else {
						$username = $student->display_name;
					}
				?>
				<input type="text" class="regular-text" value="<?php echo esc_attr( $username ) . ' (#' . absint( $student->ID ) . ')'; ?>" disabled="disabled">
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label><?php _e( 'Course', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" class="regular-text" value="<?php echo esc_attr( $course->post_title ); ?>" disabled="disabled">
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-grade"><?php _e( 'Grade', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" id="ib-edu-grade" class="regular-text" maxlength="6" size="6" name="grade" value="<?php echo esc_attr( $entry->grade ); ?>">
				<div class="description"><?php _e( 'A number between 0 and 100.', 'ibeducator' ); ?></div>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="entry-status"><?php _e( 'Status', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="entry_status" id="entry-status">
					<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo $key; ?>"<?php if ( $key == $entry->entry_status ) echo ' selected="selected"'; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>