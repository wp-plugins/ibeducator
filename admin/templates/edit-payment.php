<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
	return;
}

$payment_id = isset( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;
$payment = IB_Educator_Payment::get_instance( $payment_id );
$payment_statuses = IB_Educator_Payment::get_statuses();
$types = IB_Educator_Payment::get_types();
$api = IB_Educator::get_instance();
$student = null;
$post = null;

if ( $payment->ID ) {
	$student = get_user_by( 'id', $payment->user_id );

	if ( 'course' == $payment->payment_type ) {
		$post = get_post( $payment->course_id );
	} elseif ( 'membership' == $payment->payment_type ) {
		$post = get_post( $payment->object_id );
	}
} else {
	if ( isset( $_POST['payment_type'] ) && array_key_exists( $_POST['payment_type'], $types ) ) {
		$payment->payment_type = $_POST['payment_type'];
	} else {
		$payment->payment_type = 'course';
	}
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

	<form id="edu_edit_payment_form" class="ib-edu-admin-form" action="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment_id ) ); ?>" method="post">
		<?php wp_nonce_field( 'ib_educator_edit_payment_' . $payment->ID ); ?>
		<input type="hidden" id="autocomplete-nonce" value="<?php echo wp_create_nonce( 'ib_educator_autocomplete' ); ?>">

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="payment-type"><?php _e( 'Payment Type', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="payment_type" id="payment-type">
					<?php foreach ( $types as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $payment->payment_type ) echo ' selected="selected"'; ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ib-edu-field" data-type="course"<?php if ( 'course' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
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

		<?php
			$student_id = 0;
			$username = '';

			if ( $student ) {
				$student_id = $student->ID;
				$username = $student->display_name;
			}
		?>
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label><?php _e( 'Student', 'ibeducator' ); ?><span class="required">*</span></label></div>
			<div class="ib-edu-control">
				<div class="ib-edu-autocomplete">
					<input type="hidden" name="student_id" class="ib-edu-autocomplete-value" value="<?php echo intval( $student_id ); ?>">
					<input type="text" id="payment-student-id" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $username ); ?>"<?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
				</div>
			</div>
		</div>

		<div class="ib-edu-field" data-type="course"<?php if ( 'course' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
			<div class="ib-edu-label"><label><?php _e( 'Course', 'ibeducator' ); ?><span class="required">*</span></label></div>
			<div class="ib-edu-control">
				<?php
					$course_id = ( 'course' == $payment->payment_type && $post ) ? $post->ID : 0;
					$course_title = ( 'course' == $payment->payment_type && $post ) ? $post->post_title : '';
				?>
				<div class="ib-edu-autocomplete">
					<input type="hidden" name="course_id" class="ib-edu-autocomplete-value" value="<?php echo intval( $course_id ); ?>">
					<input type="text" id="payment-course-id" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $course_title ); ?>" <?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
				</div>
			</div>
		</div>

		<?php
			$ms = IB_Educator_Memberships::get_instance();
			$memberships = $ms->get_memberships();
			$user_membership = $ms->get_user_membership( $payment->user_id );
		?>
		<div class="ib-edu-field" data-type="membership"<?php if ( 'membership' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
			<div class="ib-edu-label"><label><?php _e( 'Membership', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<div>
					<select name="object_id">
						<option value="0"><?php _e( 'Select Membership', 'ibeducator' ); ?></option>
						<?php
							if ( $memberships ) {
								foreach ( $memberships as $membership ) {
									$selected = ( $membership->ID == $payment->object_id ) ? ' selected="selected"' : '';

									echo '<option value="' . intval( $membership->ID ) . '"' . $selected . '>'
										 . esc_html( $membership->post_title ) . '</option>';
								}
							}
						?>
					</select>

					<p>
						<label><input type="checkbox" name="setup_membership" value="1"> <?php
							if ( $user_membership ) {
								_e( 'Update membership for this student', 'ibeducator' );
							} else {
								_e( 'Setup membership for this student', 'ibeducator' );
							}
						?></label>
					</p>

					<p>
						<?php
							if ( $payment->parent_id ) {
								$parent_payment_url = admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment->parent_id );
								_e( 'Switched from:', 'ibeducator' );
								echo ' <a href="' . esc_url( $parent_payment_url ) . '">payment #' . intval( $payment->parent_id ) . '</a>';
							}
						?>
					</p>
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
					$amount = ( $payment->amount ) ? (float) $payment->amount : '';
				?>
				<input type="text" id="ib-edu-amount" class="regular-text" name="amount" value="<?php echo $amount; ?>">
				<div class="description"><?php _e( 'A number with a maximum of 2 figures after the decimal point (for example, 9.99).', 'ibeducator' ); ?></div>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-txn_id"><?php _e( 'Transaction ID', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" id="ib-edu-txn_id" class="regular-text" name="txn_id" value="<?php echo esc_attr( $payment->txn_id ); ?>">
			</div>
		</div>

		<?php
			$currencies = ib_edu_get_currencies();
		?>
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-edu-currency"><?php _e( 'Currency', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select id="ib-edu-currency" name="currency">
					<option value=""><?php _e( 'Select Currency', 'ibeducator' ); ?></option>
					<?php
						$current_currency = empty( $payment->currency ) ? ib_edu_get_currency() : $payment->currency;

						foreach ( $currencies as $key => $value ) {
							$selected = ( $key == $current_currency ) ? ' selected="selected"' : '';

							echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
						}
					?>
				</select>
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="payment-status"><?php _e( 'Status', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select name="payment_status" id="payment-status">
					<?php foreach ( $payment_statuses as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $payment->payment_status ) echo ' selected="selected"'; ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php submit_button(); ?>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	function fieldsByType( type ) {
		jQuery('#edu_edit_payment_form > .ib-edu-field').each(function() {
			var forType = this.getAttribute('data-type');

			if ( forType && forType !== type ) {
				this.style.display = 'none';
			} else {
				this.style.display = 'block';
			}
		});
	}

	var paymentType = jQuery('#payment-type');

	paymentType.on('change', function() {
		fieldsByType(this.value);
	});

	ibEducatorAutocomplete(document.getElementById('payment-student-id'), {
		nonce: jQuery('#autocomplete-nonce').val(),
		url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		entity: 'user'
	});

	ibEducatorAutocomplete(document.getElementById('payment-course-id'), {
		nonce: jQuery('#autocomplete-nonce').val(),
		url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
		entity: 'course'
	});
});
</script>