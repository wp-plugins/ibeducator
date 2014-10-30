<?php
	$user_id = get_current_user_id();

	if ( ( $thankyou = get_query_var( 'edu-thankyou' ) ) ) {
		if ( is_numeric( $thankyou ) ) {
			$payment = IB_Educator_Payment::get_instance( $thankyou );
			if ( ! $payment->ID ) return;
			$course = get_post( $payment->course_id );
			if ( ! $course ) return;
			?>
			<h3><?php _e( 'Payment Summary', 'ibeducator' ); ?></h3>
			<table id="payment-details">
				<thead>
					<tr>
						<th><?php _e( 'Item', 'ibeducator' ); ?></th>
						<th><?php _e( 'Amount', 'ibeducator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<?php
								printf(
									__( '%s with %s', 'ibeducator' ),
									'<a href="' . esc_url( get_permalink( $course->ID ) ) . '" target="_new">' . esc_html( $course->post_title ) . '</a>',
									esc_html( get_the_author_meta( 'display_name', $course->post_author ) )
								);
							?>
						</td>
						<td>
							<?php echo ib_edu_format_course_price( ib_edu_get_course_price( $course->ID ) ); ?>
						</td>
					</tr>

					<tr class="amount-total">
						<td><?php _e( 'Total', 'ibeducator' ); ?></td>
						<td><?php echo ib_edu_format_course_price( $payment->amount ); ?></td>
					</tr>
				</tbody>
			</table>
			<?php

			if ( $payment->ID && $payment->user_id == $user_id ) {
				do_action( 'ib_educator_thankyou_' . $payment->payment_gateway );
			}
		}
	} else if ( ( $pay = get_query_var( 'edu-pay' ) ) ) {
		if ( is_numeric( $pay ) ) {
			$payment = IB_Educator_Payment::get_instance( $pay );

			if ( $payment->ID && $payment->user_id == $user_id ) {
				do_action( 'ib_educator_pay_' . $payment->payment_gateway );
			}
		}
	} else {
		$course_id = get_query_var( 'edu-course' );
		if ( ! $course_id && isset( $_POST['course_id'] ) ) {
			$course_id = $_POST['course_id'];
		}
		$course = null;

		if ( is_numeric( $course_id ) ) {
			$course = get_post( $course_id );
		} else {
			$course_id = 0;
		}
		?>
		<?php if ( $course ) : ?>
			<?php
				$course_price = ib_edu_get_course_price( $course->ID );
				// Check, whether to allow the student to pay for this course.
				$api = IB_Educator::get_instance();
				$access_status = $api->get_access_status( $course_id, $user_id );
			?>
			<?php if ( in_array( $access_status, array( 'course_complete', 'forbidden' ) ) ) : ?>
				<?php
					$gateways = IB_Educator_Main::get_gateways();
					$logged_in = is_user_logged_in();

					if ( ! $logged_in ) {
						$login_url = apply_filters( 'ib_educator_login_url', '' );

						if ( empty( $login_url ) ) {
							$login_url = wp_login_url( ib_edu_get_endpoint_url( 'edu-course', $course_id, get_permalink() ) );
						}

						echo '<p>' . __( 'Already have an account?', 'ibeducator' ) . ' <a href="' . esc_url( $login_url ) . '">' . __( 'Log in', 'ibeducator' ) . '</a></p>';
					}
					
					$account_username = isset( $_POST['account_username'] ) ? $_POST['account_username'] : '';
					$account_email = isset( $_POST['account_email'] ) ? $_POST['account_email'] : '';
					$errors = ib_edu_message( 'payment_errors' );
					$error_fields = array();

					if ( is_array( $errors ) ) {
						foreach ( $errors as $error ) {
							switch ( $error ) {
								case 'account_info_empty':
									echo '<div class="ib-edu-message error">' . __( 'Please enter your username and email.', 'ibeducator' ) . '</div>';
									$error_fields[] = 'account_username';
									break;

								case 'username_exists':
									echo '<div class="ib-edu-message error">' . sprintf( __( 'Username &quot;%s&quot; is not available.', 'ibeducator' ), esc_html( $account_username ) ) . '</div>';
									$error_fields[] = 'account_username';
									break;

								case 'email_exists':
									echo '<div class="ib-edu-message error">' . __( 'A user with your email already exists.', 'ibeducator' ) . '</div>';
									$error_fields[] = 'account_email';
									break;

								case 'invalid_email':
									echo '<div class="ib-edu-message error">' . __( 'Please check if you entered your email correctly.', 'ibeducator' ) . '</div>';
									$error_fields[] = 'account_email';
									break;

								case 'empty_payment_method':
									echo '<div class="ib-edu-message error">' . __( 'Please select a payment method.', 'ibeducator' ) . '</div>';
									$error_fields[] = 'payment_method';
									break;
							}
						}
					}
				?>

				<form id="ib-edu-payment-form" class="ib-edu-form" action="<?php echo ib_edu_get_endpoint_url( 'edu-action', 'payment', get_permalink() ); ?>" method="post">
					<?php wp_nonce_field( 'ibedu_submit_payment' ); ?>
					<input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

					<?php if ( ! is_user_logged_in() ) : ?>
					<fieldset>
						<legend><?php _e( 'Create an Account', 'ibeducator' ); ?></legend>

						<div class="ib-edu-form-field<?php if ( in_array( 'account_username', $error_fields ) ) echo ' error'; ?>">
							<label for="ib-edu-username"><?php _e( 'Username', 'ibeducator' ); ?> <span class="required">*</span></label>
							<div class="ib-edu-form-control">
								<input type="text" id="ib-edu-username" name="account_username" value="<?php echo esc_attr( $account_username ); ?>">
							</div>
						</div>

						<div class="ib-edu-form-field<?php if ( in_array( 'account_email', $error_fields ) ) echo ' error'; ?>">
							<label for="ib-edu-email"><?php _e( 'Email', 'ibeducator' ); ?> <span class="required">*</span></label>
							<div class="ib-edu-form-control">
								<input type="text" id="ib-edu-email" name="account_email" value="<?php echo esc_attr( $account_email ); ?>">
							</div>
						</div>
					</fieldset>
					<?php endif; ?>

					<fieldset>
						<legend><?php _e( 'Payment Information', 'ibeducator' ); ?></legend>

						<div class="ib-edu-form-field">
							<label><?php _e( 'Course', 'ibeducator' ); ?></label>
							<div class="ib-edu-form-control">
								<?php
									printf(
										__( '%s with %s', 'ibeducator' ),
										'<a href="' . esc_url( get_permalink( $course->ID ) ) . '" target="_new">' . esc_html( $course->post_title ) . '</a>',
										esc_html( get_the_author_meta( 'display_name', $course->post_author ) )
									);
								?>
							</div>
						</div>

						<div class="ib-edu-form-field">
							<label><?php _e( 'Price', 'ibeducator' ); ?></label>
							<div class="ib-edu-form-control">
								<?php
									if ( $course_price ) {
										echo ib_edu_format_course_price( $course_price );
									} else {
										_e( 'Free', 'ibeducator' );
									}
								?>
							</div>
						</div>

						<?php if ( $course_price && ! empty( $gateways ) ) : ?>
						<div class="ib-edu-form-field<?php if ( in_array( 'payment_method', $error_fields ) ) echo ' error'; ?>">
							<label><?php _e( 'Payment Method', 'ibeducator' ); ?> <span class="required">*</span></label>
							<div class="ib-edu-form-control">
								<ul class="ib-edu-payment-method">
									<?php
										$current_gateway_id = isset( $_POST['payment_method'] ) ? $_POST['payment_method'] : '';

										foreach ( $gateways as $gateway_id => $gateway ) {
											$checked = '';

											if ( ! empty( $current_gateway_id ) && $current_gateway_id === $gateway_id ) {
												$checked = ' checked';
											} elseif ( empty( $current_gateway_id ) && $gateway->is_default() ) {
												$checked = ' checked';
											}

											echo '<li><label><input type="radio" name="payment_method" value="' . $gateway_id . '"' . $checked . '> ' . $gateway->get_title() . '</label></li>';
										}
									?>
								</ul>
							</div>
						</div>
						<?php endif; ?>
					</fieldset>

					<div class="ib-edu-form-actions">
						<button type="submit" class="ib-edu-button"><?php _e( 'Continue', 'ibeducator' ) ?></button>
					</div>
				</form>
			<?php else : ?>
				<p><?php echo ib_edu_get_access_status_message( $access_status ); ?></p>
			<?php endif; ?>
		<?php else : // if $course ?>
			<p><?php _e( 'Please select a course to continue.', 'ibeducator' ); ?></p>
		<?php endif; ?>
		<?php
	}
?>