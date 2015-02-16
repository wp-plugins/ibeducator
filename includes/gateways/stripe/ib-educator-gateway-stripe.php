<?php

class IB_Educator_Gateway_Stripe extends IB_Educator_Payment_Gateway {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'stripe';
		$this->title = __( 'Stripe', 'ibeducator' );

		// Setup options.
		$this->init_options( array(
			'secret_key' => array(
				'type'      => 'text',
				'label'     => __( 'Secret key', 'ibeducator' ),
				'id'        => 'ib-edu-secret-key',
			),
			'publishable_key' => array(
				'type'      => 'text',
				'label'     => __( 'Publishable key', 'ibeducator' ),
				'id'        => 'ib-edu-publishable-key',
			),
			'thankyou_message' => array(
				'type'      => 'textarea',
				'label'     => __( 'Thank you message', 'ibeducator' ),
				'id'        => 'ib-edu-thankyou-message',
				'rich_text' => true,
			),
		) );

		add_action( 'ib_educator_pay_' . $this->get_id(), array( $this, 'pay_page' ) );
		add_action( 'ib_educator_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'ib_educator_request_stripe_token', array( $this, 'process_stripe_token' ) );
	}

	/**
	 * Process payment.
	 *
	 * @return array
	 */
	public function process_payment( $object_id, $user_id = 0, $payment_type = 'course' ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array( 'redirect' => home_url( '/' ) );
		}
		
		// Add payment.
		$params = array(
			'user_id'         => $user_id,
			'payment_type'    => $payment_type,
			'payment_status'  => 'pending',
			'payment_gateway' => $this->get_id(),
			'currency'        => ib_edu_get_currency(),
		);

		if ( 'course' == $payment_type ) {
			$params['course_id'] = $object_id;
			$params['amount'] = ib_edu_get_course_price( $object_id );
		} elseif ( 'membership' == $payment_type ) {
			$params['object_id'] = $object_id;
			$ms = IB_Educator_Memberships::get_instance();
			$update_membership = null;

			if ( 1 == ib_edu_get_option( 'change_memberships', 'memberships' ) ) {
				$update_membership = $ms->get_new_payment_data( $user_id, $object_id );
			}

			if ( ! empty( $update_membership ) ) {
				$params['amount'] = $update_membership['price'];
			} else {
				$params['amount'] = $ms->get_price( $object_id );
			}
		}

		$redirect = '';
		$api = IB_Educator::get_instance();
		$payment = $api->add_payment( $params );

		if ( $payment->ID ) {
			// Record membership switch.
			if ( 'membership' == $payment_type && ! empty( $update_membership ) ) {
				$ms->record_switch( $payment );
			}

			$redirect = ib_edu_get_endpoint_url( 'edu-pay', $payment->ID, get_permalink( ib_edu_page_id( 'payment' ) ) );
		} else {
			$redirect = ib_edu_get_endpoint_url( 'edu-pay', '', get_permalink( ib_edu_page_id( 'payment' ) ) );
		}

		return array(
			'status'   => 'pending',
			'redirect' => $redirect,
		);
	}

	/**
	 * Output the Stripe's payment dialog.
	 * Step 2 in the payment process.
	 */
	public function pay_page() {
		$payment_id = absint( get_query_var( 'edu-pay' ) );

		if ( ! $payment_id ) {
			return;
		}

		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			return;
		}

		$payment = IB_Educator_Payment::get_instance( $payment_id );

		if ( ! $payment->ID || $user->ID != $payment->user_id ) {
			// The payment must exist and it must be associated with the current user.
			return;
		}

		if ( 'course' == $payment->payment_type ) {
			$post = get_post( $payment->course_id );
		} elseif ( 'membership' == $payment->payment_type ) {
			$post = get_post( $payment->object_id );
		}

		if ( ! $post ) {
			return;
		}
		?>
		<p id="ib-edu-payment-processing-msg">
			<?php _e( 'The payment is getting processed...', 'ibeducator' ); ?>
		</p>
		<script src="https://checkout.stripe.com/checkout.js"></script>
		<script>
		(function($) {
			var handler = StripeCheckout.configure({
				key: <?php echo json_encode( $this->get_option( 'publishable_key' ) ); ?>,
				image: '',
				email: <?php echo json_encode( $user->user_email ); ?>,
				token: function(token) {
					$.ajax({
						type: 'POST',
						cache: false,
						url: <?php echo json_encode( ib_edu_request_url( 'stripe_token' ) ); ?>,
						data: {
							payment_id: <?php echo intval( $payment->ID ); ?>,
							token: token.id,
							_wpnonce: <?php echo json_encode( wp_create_nonce( 'ib_educator_stripe_token' ) ); ?>
						},
						success: function(response) {
							if (response === '1') {
								$('#ib-edu-payment-processing-msg').text(<?php echo json_encode( __( 'Redirecting to the payment summary page...', 'ibeducator' ) ); ?>);
								var redirectTo = <?php echo json_encode( ib_edu_get_endpoint_url( 'edu-thankyou', $payment->ID, get_permalink( ib_edu_page_id( 'payment' ) ) ) ); ?>;
								document.location = redirectTo;
							}
						}
					});
				}
			});

			handler.open({
				name: <?php echo json_encode( esc_html( $post->post_title ) ); ?>,
				description: <?php echo json_encode( ib_edu_format_price( $payment->amount, false, false ) ); ?>,
				currency: <?php echo json_encode( ib_edu_get_currency() ); ?>,
				amount: <?php echo absint( $payment->amount * 100 ); ?>
			});

			$(window).on('popstate', function() {
				handler.close();
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Output thank you information.
	 */
	public function thankyou_page() {
		// Thank you message.
		$thankyou_message = $this->get_option( 'thankyou_message' );

		if ( ! empty( $thankyou_message ) ) {
			echo '<div class="ib-edu-payment-description">' . wpautop( stripslashes( $thankyou_message ) ) . '</div>';
		}
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_options( $input ) {
		foreach ( $input as $option_name => $value ) {
			switch ( $option_name ) {
				case 'thankyou_message':
					$input[ $option_name ] = wp_kses_data( $value );
					break;

				case 'secret_key':
				case 'publishable_key':
					$input[ $option_name ] = sanitize_text_field( $value );
					break;
			}
		}

		return $input;
	}

	/**
	 * Charge the card using Stripe.
	 * It's an AJAX action.
	 */
	public function process_stripe_token() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ib_educator_stripe_token' ) ) {
			exit( '0' );
		}

		if ( ! isset( $_POST['token'] ) || ! isset( $_POST['payment_id'] ) ) {
			exit( '0' );
		}

		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			exit( '0' );
		}

		$payment = IB_Educator_Payment::get_instance( $_POST['payment_id'] );

		if ( ! $payment->ID || $user->ID != $payment->user_id ) {
			// The payment must exist and it must be associated with the current user.
			exit( '0' );
		}

		require_once IBEDUCATOR_PLUGIN_DIR . 'lib/Stripe/Stripe.php';

		$token = $_POST['token'];
		$amount = round( (float) $payment->amount, 2 );
		$description = sprintf( __( 'Payment #%d', 'ibeducator' ), $payment->ID );

		if ( 'course' == $payment->payment_type ) {
			$description .= ' , ' . get_the_title( $payment->course_id );
		} elseif ( 'membership' == $payment->payment_type ) {
			$description .= ' , ' . get_the_title( $payment->object_id );
		}

		try {
			Stripe::setApiKey( $this->get_option( 'secret_key' ) );
			Stripe_Charge::create( array(
				'amount'      => $amount * 100,
				'currency'    => $payment->currency,
				'card'        => $token,
				'description' => $description,
			) );

			// Update the payment status.
			$payment->payment_status = 'complete';
			$payment->save();

			if ( 'course' == $payment->payment_type ) {
				// Setup course entry.
				$api = IB_Educator::get_instance();
				$entry = $api->get_entry( array( 'payment_id' => $payment->ID ) );

				if ( ! $entry ) {
					$entry = IB_Educator_Entry::get_instance();
					$entry->course_id = $payment->course_id;
					$entry->user_id = $payment->user_id;
					$entry->payment_id = $payment->ID;
					$entry->entry_status = 'inprogress';
					$entry->entry_date = date( 'Y-m-d H:i:s' );
					$entry->save();

					// Send notification email to the student.
					$student = get_user_by( 'id', $payment->user_id );
					$course = get_post( $payment->course_id, OBJECT, 'display' );

					if ( $student && $course ) {
						ib_edu_send_notification(
							$student->user_email,
							'student_registered',
							array(
								'course_title' => $course->post_title,
							),
							array(
								'student_name'   => $student->display_name,
								'course_title'   => $course->post_title,
								'course_excerpt' => $course->post_excerpt,
							)
						);
					}
				}
			} elseif ( 'membership' == $payment->payment_type ) {
				// Setup membership.
				$ms = IB_Educator_Memberships::get_instance();
				$ms->setup_membership( $payment->user_id, $payment->object_id );

				$student = get_user_by( 'id', $payment->user_id );
				$membership = $ms->get_membership( $payment->object_id );

				if ( $student && $membership ) {
					$user_membership = $ms->get_user_membership( $student->ID );
					$membership_meta = $ms->get_membership_meta( $membership->ID );
					$expiration = ( $user_membership ) ? $user_membership['expiration'] : 0;

					ib_edu_send_notification(
						$student->user_email,
						'membership_register',
						array(),
						array(
							'student_name' => $student->display_name,
							'membership'   => $membership->post_title,
							'expiration'   => ( $expiration ) ? date_i18n( get_option( 'date_format' ), $expiration ) : __( 'None', 'ibeducator' ),
							'price'        => $ms->format_price( $membership_meta['price'], $membership_meta['duration'], $membership_meta['period'], false ),
						)
					);
				}
			}

			exit( '1' );
		} catch ( Exception $e ) {}

		exit( '0' );
	}
}