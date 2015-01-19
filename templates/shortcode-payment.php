<?php
$user_id = get_current_user_id();

if ( ( $thankyou = get_query_var( 'edu-thankyou' ) ) ) {
	// Thank you page, payment summary.
	if ( ! $user_id ) {
		return;
	}

	if ( is_numeric( $thankyou ) ) {
		$payment = IB_Educator_Payment::get_instance( $thankyou );

		if ( ! $payment->ID || $payment->user_id != $user_id ) {
			return;
		}

		$post = null;
		
		if ( 'course' == $payment->payment_type ) {
			$post = get_post( $payment->course_id );
			$price = ib_edu_get_course_price( $post->ID );
		} elseif ( 'membership' == $payment->payment_type ) {
			$post = get_post( $payment->object_id );
			$ms = IB_Educator_Memberships::get_instance();
			$price = $ms->get_price( $post->ID );
		}
		
		if ( ! $post ) return;
		?>
		<h3><?php _e( 'Payment Summary', 'ibeducator' ); ?></h3>

		<table id="payment-details">
			<tbody>
				<tr class="payment-id">
					<td><?php _e( 'Payment', 'ibeducator' ); ?></td>
					<td><?php echo intval( $payment->ID ); ?></td>
				</tr>

				<tr class="payment-date">
					<td><?php _e( 'Date', 'ibeducator' ); ?></td>
					<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->payment_date ) ) ); ?></td>
				</tr>

				<tr class="payment-status">
					<td><?php _e( 'Payment Status', 'ibeducator' ); ?></td>
					<td>
						<?php
							$statuses = IB_Educator_Payment::get_statuses();

							if ( array_key_exists( $payment->payment_status, $statuses ) ) {
								echo esc_html( $statuses[ $payment->payment_status ] );
							}
						?>
					</td>
				</tr>

				<?php if ( 'course' == $payment->payment_type ) : ?>
				<tr>
					<td>
						<?php _e( 'Course', 'ibeducator' ); ?>
					</td>
					<td>
						<?php
							printf(
								__( '%s with %s', 'ibeducator' ),
								'<a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a>',
								esc_html( get_the_author_meta( 'display_name', $post->post_author ) )
							);
						?>
					</td>
				</tr>
				<?php elseif ( 'membership' == $payment->payment_type ) : ?>
				<tr>
					<td>
						<?php _e( 'Membership', 'ibeducator' ); ?>
					</td>
					<td>
						<?php
							echo esc_html( $post->post_title );
						?>
					</td>
				</tr>
				<?php endif; ?>

				<tr>
					<td><?php _e( 'Price', 'ibeducator' ); ?></td>
					<td>
						<?php echo ib_edu_format_price( $price, false ); ?>
					</td>
				</tr>

				<tr class="amount-total">
					<td><?php _e( 'Total', 'ibeducator' ); ?></td>
					<td><?php echo ib_edu_format_price( $payment->amount, false ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php

		if ( $payment->ID && $payment->user_id == $user_id ) {
			do_action( 'ib_educator_thankyou_' . $payment->payment_gateway );
		}

		// Show link to the payments page.
		$payments_page = get_post( ib_edu_page_id( 'user_payments' ) );
		
		if ( $payments_page ) {
			echo '<p>' . sprintf( __( 'Go to %s page', 'ibeducator' ), '<a href="' . esc_url( get_permalink( $payments_page->ID ) ) . '">' . esc_html( $payments_page->post_title ) . '</a>' ) . '</p>';
		}
	}
} else if ( ( $pay = get_query_var( 'edu-pay' ) ) ) {
	// Can be used for step 2 of the payment process.
	// PayPal gateway uses it.
	if ( is_numeric( $pay ) ) {
		$payment = IB_Educator_Payment::get_instance( $pay );

		// The payment must exist and it must belong to the current user.
		if ( $payment->ID && $payment->user_id == $user_id ) {
			do_action( 'ib_educator_pay_' . $payment->payment_gateway );
		}
	}
} else {
	// Step 1 of the payment process.
	$payment_type = 'course';
	$course_id = get_query_var( 'edu-course' );
	$membership_id = 0;
	$post = null;

	if ( ! is_numeric( $course_id ) && isset( $_POST['course_id'] ) ) {
		$course_id = intval( $_POST['course_id'] );
	}

	if ( $course_id ) {
		$post = get_post( $course_id );
	} else {
		// No course id? Try to get membership id.
		$membership_id = get_query_var( 'edu-membership' );

		if ( ! is_numeric( $membership_id ) && isset( $_POST['membership_id'] ) ) {
			$membership_id = intval( $_POST['membership_id'] );
		}

		if ( $membership_id ) {
			$post = get_post( $membership_id );
			$payment_type = 'membership';
		}
	}
	?>
	<?php if ( $post ) : ?>
		<?php
			$api = IB_Educator::get_instance();

			// Get price.
			if ( 'course' == $payment_type ) {
				$access_status = $api->get_access_status( $course_id, $user_id );
				$can_pay = in_array( $access_status, array( 'course_complete', 'forbidden' ) );
			} elseif ( 'membership' == $payment_type ) {
				$ms = IB_Educator_Memberships::get_instance();
				$can_pay = true;
			}
		?>
		<?php if ( $can_pay ) : ?>
			<?php
				$gateways = IB_Educator_Main::get_gateways();
				$logged_in = is_user_logged_in();

				if ( ! $logged_in ) {
					$login_url = apply_filters( 'ib_educator_login_url', '' );

					if ( empty( $login_url ) ) {
						if ( 'course' == $payment_type ) {
							$login_url = wp_login_url( ib_edu_get_endpoint_url( 'edu-course', $course_id, get_permalink() ) );
						} else {
							$login_url = wp_login_url( ib_edu_get_endpoint_url( 'edu-membership', $membership_id, get_permalink() ) );
						}
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

							case 'invalid_username':
								echo '<div class="ib-edu-message error">' . __( 'Please check if you entered your username correctly.', 'ibeducator' ) . '</div>';
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

			<form id="ib-edu-payment-form" class="ib-edu-form" action="<?php echo esc_url( ib_edu_get_endpoint_url( 'edu-action', 'payment', get_permalink() ) ); ?>" method="post">
				<?php wp_nonce_field( 'ibedu_submit_payment' ); ?>

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

					<?php if ( 'course' == $payment_type ) : ?>
					<?php // Course information. ?>
					<div class="ib-edu-form-field">
						<label><?php _e( 'Course', 'ibeducator' ); ?></label>

						<div class="ib-edu-form-control">
							<?php
								printf(
									__( '%s with %s', 'ibeducator' ),
									'<a href="' . esc_url( get_permalink( $post->ID ) ) . '" target="_blank">' . esc_html( $post->post_title ) . '</a>',
									esc_html( get_the_author_meta( 'display_name', $post->post_author ) )
								);
							?>
							<input type="hidden" name="course_id" value="<?php echo intval( $post->ID ); ?>">
						</div>
					</div>
					<?php elseif ( 'membership' == $payment_type ) : ?>
					<?php // Membership information. ?>
					<div class="ib-edu-form-field">
						<label><?php _e( 'Membership', 'ibeducator' ); ?></label>

						<div class="ib-edu-form-control">
							<?php echo esc_html( $post->post_title ); ?>
							<input type="hidden" name="membership_id" value="<?php echo intval( $post->ID ); ?>">
						</div>
					</div>
					<?php endif; ?>

					<?php
						$price = 0.0;
					?>
					<div class="ib-edu-form-field">
						<label><?php _e( 'Price', 'ibeducator' ); ?></label>
						<div class="ib-edu-form-control">
							<?php
								if ( 'course' == $payment_type ) {
									// Course.
									$price = ib_edu_get_course_price( $post->ID );

									if ( $price ) echo ib_edu_format_course_price( $price );
									else echo 0;
								} elseif ( 'membership' == $payment_type ) {
									// Membership.
									$membership_meta = $ms->get_membership_meta( $membership_id );
									$update_membership = null;

									if ( 1 == ib_edu_get_option( 'change_memberships', 'memberships' ) ) {
										$update_membership = $ms->get_new_payment_data( $user_id, $membership_id );
									}

									if ( ! empty( $update_membership ) ) {
										// Membership can be changed.
										$price = $update_membership['price'];

										printf(
											_x( '%s now and %s from %s', 'membership price description', 'ibeducator' ),
											ib_edu_format_price( $price ),
											$ms->format_price( $membership_meta['price'], $membership_meta['duration'], $membership_meta['period'] ),
											esc_html( date_i18n( get_option( 'date_format' ), $update_membership['expiration'] ) )
										);
									} else {
										// Pay for new membership.
										$price = $ms->get_price( $membership_id );

										echo $ms->format_price( $price, $membership_meta['duration'], $membership_meta['period'] );
									}
								}
							?>
						</div>
					</div>

					<?php if ( $price > 0 && ! empty( $gateways ) ) : ?>
					<div class="ib-edu-form-field<?php if ( in_array( 'payment_method', $error_fields ) ) echo ' error'; ?>">
						<label><?php _e( 'Payment Method', 'ibeducator' ); ?> <span class="required">*</span></label>
						<div class="ib-edu-form-control">
							<ul class="ib-edu-payment-method">
								<?php
									$current_gateway_id = isset( $_POST['payment_method'] ) ? $_POST['payment_method'] : '';

									foreach ( $gateways as $gateway_id => $gateway ) {
										if ( 'free' == $gateway_id ) continue;

										$checked = '';

										if ( ! empty( $current_gateway_id ) && $current_gateway_id === $gateway_id ) {
											$checked = ' checked';
										} elseif ( empty( $current_gateway_id ) && $gateway->is_default() ) {
											$checked = ' checked';
										}

										echo '<li><label><input type="radio" name="payment_method" value="' . esc_attr( $gateway_id ) . '"' . $checked . '> ' . esc_html( $gateway->get_title() ) . '</label></li>';
									}
								?>
							</ul>
						</div>
					</div>
					<?php elseif ( 0.0 == $price ) : ?>
					<input type="hidden" name="payment_method" value="free">
					<?php endif; ?>
				</fieldset>

				<div class="ib-edu-form-actions">
					<button type="submit" class="ib-edu-button"><?php _e( 'Continue', 'ibeducator' ) ?></button>
				</div>
			</form>
		<?php else : ?>
			<?php
				if ( 'course' == $payment_type ) {
					echo '<p>' . ib_edu_get_access_status_message( $access_status ) . '</p>';
				}
			?>
		<?php endif; ?>
	<?php else : // if $course ?>
		<p><?php _e( 'Please select a course to continue.', 'ibeducator' ); ?></p>
	<?php endif; ?>
	<?php
}
?>