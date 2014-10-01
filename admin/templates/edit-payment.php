<?php
	$payment_id = isset( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;

	if ( ! $payment_id ) return;

	// Verify capability.
	if ( ! current_user_can( 'manage_educator' ) ) {
		return;
	}

	$payment = IBEdu_Payment::get_instance( $payment_id );

	if ( ! $payment->ID ) return;

	$payment_statuses = IBEdu_Payment::get_statuses();

	$student = get_user_by( 'id', $payment->user_id );
	$course = get_post( $payment->course_id );
?>
<div class="wrap">
	<h2><?php _e( 'Edit Payment', 'ibeducator' ); ?></h2>

	<?php if ( isset( $_GET['edu-message'] ) && 'saved' == $_GET['edu-message'] ) : ?>
	<div id="message" class="updated below-h2">
		<p><?php _e( 'Payment updated.', 'ibeducator' ); ?></p>
	</div>
	<?php endif; ?>

	<form id="edu_edit_payment_form" class="ibedu-admin-form" action="<?php echo admin_url( 'admin.php?page=ibedu_payments&edu-action=edit-payment&payment_id=' . $payment_id ); ?>" method="post">
		<?php wp_nonce_field( 'ibedu_edit_payment_' . $payment->ID ); ?>

		<div class="ibedu-field">
			<div class="ibedu-label"><label><?php _e( 'Student', 'ibeducator' ); ?></label></div>
			<div class="ibedu-control">
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

		<div class="ibedu-field">
			<div class="ibedu-label"><label><?php _e( 'Course', 'ibeducator' ); ?></label></div>
			<div class="ibedu-control">
				<input type="text" class="regular-text" value="<?php echo esc_attr( $course->post_title ); ?>" disabled="disabled">
			</div>
		</div>

		<div class="ibedu-field">
			<div class="ibedu-label"><label for="ibedu-amount"><?php _e( 'Amount', 'ibeducator' ); ?></label></div>
			<div class="ibedu-control">
				<input type="text" id="ibedu-amount" class="regular-text" name="amount" value="<?php echo number_format( $payment->amount, 2 ); ?>">
				<div class="description"><?php _e( 'A number with a maximum of 2 figures after the decimal point (for example, 9.99).', 'ibeducator' ); ?></div>
			</div>
		</div>

		<div class="ibedu-field">
			<div class="ibedu-label"><label for="payment-status"><?php _e( 'Status', 'ibeducator' ); ?></label></div>
			<div class="ibedu-control">
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