<?php
	// Verify capability.
	if ( ! current_user_can( 'manage_educator' ) ) {
		return;
	}

	$payment_id = isset( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;
	$payment = IB_Educator_Payment::get_instance( $payment_id );
	$payment_statuses = IB_Educator_Payment::get_statuses();
	$api = IB_Educator::get_instance();
	$student = null;
	$course = null;

	if ( $payment->ID ) {
		$student = get_user_by( 'id', $payment->user_id );
		$course = get_post( $payment->course_id );
	}
?>
<div class="wrap">
	<h2><?php
		if ( $payment->ID ) {
			_e( 'Edit Payment', 'ibeducator' );
		} else {
			_e( 'Add Payment', 'ibeducator' );
		}
	?></h2>

	<?php
		$errors = ib_edu_message( 'edit_payment_errors' );

		if ( $errors ) {
			echo '<div class="error below-h2"><ul>';

			foreach ( $errors as $error ) {
				switch ( $error ) {
					case 'empty_student_id':
						echo '<li>' . __( 'Please select a student', 'ibeducator' ) . '</li>';
						break;

					case 'empty_course_id':
						echo '<li>' . __( 'Please select a course', 'ibeducator' ) . '</li>';
						break;
				}
			}

			echo '</ul></div>';
		}
	?>

	<?php if ( isset( $_GET['edu-message'] ) && 'saved' == $_GET['edu-message'] ) : ?>
	<div id="message" class="updated below-h2">
		<p><?php _e( 'Payment saved.', 'ibeducator' ); ?></p>
	</div>
	<?php endif; ?>

	<form id="edu_edit_payment_form" class="ib-edu-admin-form" action="<?php echo admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment_id ); ?>" method="post">
		<?php wp_nonce_field( 'ib_educator_edit_payment_' . $payment->ID ); ?>
		<input type="hidden" id="autocomplete-nonce" value="<?php echo wp_create_nonce( 'ib_educator_autocomplete' ); ?>">

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-entry-id"><?php _e( 'Entry ID', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<?php
					if ( $payment->ID ) $entry = $api->get_entry( array( 'payment_id' => $payment->ID ) );
					else $entry = false;

					$entry_value = ( $entry ) ? intval( $entry->ID ) : __( 'This payment is not connected to any entry.', 'ibeducator' );
				?>
				<input type="text" id="ib-edu-entry-id" class="regular-text" value="<?php echo $entry_value; ?>" disabled="disabled">
				<?php if ( ! $entry ) : ?>
				<p>
					<label><input type="checkbox" name="create_entry" value="1"> <?php _e( 'Create an entry for this student', 'ibeducator' ); ?></label>
				</p>
				<?php endif; ?>
			</div>
		</div>

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
					<input type="text" id="payment-student-id" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $username ); ?>"<?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
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
					<input type="text" id="payment-course-id" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $course_title ); ?>" <?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
				</div>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-amount"><?php _e( 'Payment Method', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="payment_gateway">
					<option value="">&mdash; <?php _e( 'Select', 'ibeducator' ); ?> &mdash;</option>
					<?php
						$gateways = IB_Educator_Main::get_gateways();

						foreach ( $gateways as $gateway ) {
							echo '<option value="' . esc_attr( $gateway->get_id() ) . '" '
								 . selected( $payment->payment_gateway, $gateway->get_id() ) . '>'
								 . esc_html( $gateway->get_title() ) . '</option>';
						}
					?>
				</select>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-amount"><?php _e( 'Amount', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<?php
					$amount = ( $payment->amount ) ? (float) number_format( $payment->amount, 2 ) : '';
				?>
				<input type="text" id="ib-edu-amount" class="regular-text" name="amount" value="<?php echo $amount; ?>">
				<div class="description"><?php _e( 'A number with a maximum of 2 figures after the decimal point (for example, 9.99).', 'ibeducator' ); ?></div>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="payment-status"><?php _e( 'Status', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="payment_status" id="payment-status">
					<?php foreach ( $payment_statuses as $key => $label ) : ?>
					<option value="<?php echo $key; ?>"<?php if ( $key == $payment->payment_status ) echo ' selected="selected"'; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	ibEducatorAutocomplete(document.getElementById('payment-student-id'), {
		nonce: jQuery('#autocomplete-nonce').val(),
		url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		entity: 'student'
	});

	ibEducatorAutocomplete(document.getElementById('payment-course-id'), {
		nonce: jQuery('#autocomplete-nonce').val(),
		url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		entity: 'course'
	});
});
</script>