<?php
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
	$entry = IB_Educator_Entry::get_instance( $entry_id );
	$who = '';

	if ( current_user_can( 'manage_educator' ) ) {
		$who = 'admin';
	} elseif ( $entry->course_id && current_user_can( 'edit_ib_educator_course', $entry->course_id ) ) {
		$who = 'lecturer';
	}

	// Check capabilities.
	if ( empty( $who ) ) {
		// Current user cannot create entries.
		echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
		return;
	}

	$statuses = IB_Educator_Entry::get_statuses();
	$origins = IB_Educator_Entry::get_origins();
	$student = null;
	$course = null;

	if ( $entry->ID ) {
		$student = get_user_by( 'id', $entry->user_id );
		$course = get_post( $entry->course_id );
	}
?>
<div class="wrap">
	<h2><?php
		if ( $entry->ID ) {
			_e( 'Edit Entry', 'ibeducator' );
		} else {
			_e( 'Add Entry', 'ibeducator' );
		}
	?></h2>

	<?php if ( isset( $_GET['edu-message'] ) && 'saved' == $_GET['edu-message'] ) : ?>
	<div id="message" class="updated below-h2">
		<p><?php _e( 'Entry saved.', 'ibeducator' ); ?></p>
	</div>
	<?php endif; ?>

	<form id="edu_edit_entry_form" class="ib-edu-admin-form" action="<?php echo admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . $entry_id ); ?>" method="post">
		<?php wp_nonce_field( 'ib_educator_edit_entry_' . $entry->ID ); ?>
		<input type="hidden" id="autocomplete-nonce" value="<?php echo wp_create_nonce( 'ib_educator_autocomplete' ); ?>">

		<?php if ( 'admin' == $who ) : ?>
		<div class="ib-edu-field" data-origin="payment"<?php if ( 'payment' != $entry->entry_origin ) echo ' style="display:none;"'; ?>>
			<div class="ib-edu-label"><label for="ib-edu-payment-id"><?php _e( 'Payment ID', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" id="ib-edu-payment-id" class="regular-text" maxlength="20" size="6" name="payment_id" value="<?php echo esc_attr( $entry->payment_id ); ?>">
				<div class="description">
					<?php
						printf( __( 'Please find payment ID on %s page.', 'ibeducator' ), '<a href="'
							. admin_url( 'admin.php?page=ib_educator_payments' ) . '" target="_blank">'
							. __( 'Payments', 'ibeducator' ) . '</a>' );
					?>
				</div>
			</div>
		</div>

		<?php
			$ms = IB_Educator_Memberships::get_instance();
			$memberships = $ms->get_memberships();
		?>
		<div class="ib-edu-field" data-origin="membership"<?php if ( 'membership' != $entry->entry_origin ) echo ' style="display:none;"'; ?>>
			<div class="ib-edu-label"><label for="ib-edu-membership-id"><?php _e( 'Membership', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="membership_id" id="ib-edu-membership-id">
					<option value=""><?php _e( 'Select Membership', 'ibeducator' ); ?></option>
					<?php
						if ( $memberships ) {
							foreach ( $memberships as $membership ) {
								$selected = ( $entry->object_id == $membership->ID ) ? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $membership->ID ) . '"' . $selected . '>' . esc_html( $membership->post_title ) . '</option>';
							}
						}
					?>
				</select>
			</div>
		</div>
		<?php endif; ?>
		
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label><?php _e( 'Student', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<?php
					$student_id = 0;
					$username = '';

					if ( $student ) {
						$student_id = $student->ID;
						$username = $student->display_name;
					}
				?>
				<div class="ib-edu-autocomplete">
					<input type="hidden" name="student_id" class="ib-edu-autocomplete-value" value="<?php echo intval( $student_id ); ?>">
					<input type="text" id="entry-student-id" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $username ); ?>"<?php if ( 'admin' != $who ) echo ' disabled="disabled"'; ?>>
				</div>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label><?php _e( 'Course', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<?php
					$course_id = ( $course ) ? $course->ID : 0;
					$course_title = ( $course ) ? $course->post_title : '';
				?>
				<div class="ib-edu-autocomplete">
					<input type="hidden" name="course_id" class="ib-edu-autocomplete-value" value="<?php echo intval( $course_id ); ?>">
					<input type="text" id="entry-course-id" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $course_title ); ?>" <?php if ( 'admin' != $who ) echo ' disabled="disabled"'; ?>>
				</div>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-grade"><?php _e( 'Grade', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" id="ib-edu-grade" class="regular-text" maxlength="6" size="6" name="grade" value="<?php echo esc_attr( $entry->grade ); ?>">
				<div class="description"><?php _e( 'A number between 0 and 100.', 'ibeducator' ); ?></div>
			</div>
		</div>

		<?php if ( 'admin' == $who ) : ?>
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="entry-origin"><?php _e( 'Origin', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="entry_origin" id="entry-origin">
					<?php foreach ( $origins as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $entry->entry_origin ) echo ' selected="selected"'; ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php endif; ?>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="entry-status"><?php _e( 'Status', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="entry_status" id="entry-status">
					<?php foreach ( $statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $entry->entry_status ) echo ' selected="selected"'; ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-entry-date"><?php _e( 'Date', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" id="ib-edu-entry-date" class="regular-text" maxlength="19" size="19" name="entry_date" value="<?php echo esc_attr( $entry->entry_date ); ?>">
				<div class="description"><?php _e( 'Date format: yyyy-mm-dd hh:mm:ss', 'ibeducator' ); ?></div>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	function fieldsByOrigin( origin ) {
		jQuery('#edu_edit_entry_form > .ib-edu-field').each(function() {
			var forOrigin = this.getAttribute('data-origin');

			if ( forOrigin && forOrigin !== origin ) {
				this.style.display = 'none';
			} else {
				this.style.display = 'block';
			}
		});
	}

	var entryOrigin = jQuery('#entry-origin');

	entryOrigin.on('change', function() {
		fieldsByOrigin(this.value);
	});

	ibEducatorAutocomplete(document.getElementById('entry-student-id'), {
		nonce: jQuery('#autocomplete-nonce').val(),
		url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		entity: 'user'
	});

	ibEducatorAutocomplete(document.getElementById('entry-course-id'), {
		nonce: jQuery('#autocomplete-nonce').val(),
		url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		entity: 'course'
	});
});
</script>