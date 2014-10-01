<?php

class IBEdu_Gateway_Paypal extends IBEdu_Payment_Gateway {
	protected $business_email;
	protected $live_url;
	protected $test_url;
	protected $notify_url;
	protected $test;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id = 'paypal';
		$this->title = __( 'PayPal', 'ibeducator' );
		$this->live_url = 'https://www.paypal.com/cgi-bin/webscr';
		$this->test_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

		$this->init_options( array(
			'business_email' => array(
				'type'  => 'text',
				'label' => __( 'Business Email', 'ibeducator' )
			),

			'test' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Test', 'ibeducator' ),
				'description' => __( 'If checked, Educator will use PayPal sandbox URL and PayPal payments will be in testing mode.', 'ibeducator' )
			),

			'thankyou_message' => array(
				'type'  => 'textarea',
				'label' => __( 'Thank you message', 'ibeducator' )
			),
		) );

		add_action( 'ibedu_pay_' . $this->get_id(), array( $this, 'pay_page' ) );
		add_action( 'ibedu_thankyou_' . $this->get_id(), array( $this, 'thankyou_page' ) );
		add_action( 'ibedu_process_request', array( $this, 'process_ipn' ) );
	}

	/**
	 * Process payment.
	 *
	 * @return array
	 */
	public function process_payment( $course_id, $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$api = IBEdu_API::get_instance();
		
		// Add payment
		$payment = $api->add_payment( array(
			'course_id'       => $course_id,
			'user_id'         => $user_id,
			'payment_status'  => 'pending',
			'payment_gateway' => $this->get_id(),
			'amount'          => number_format( get_post_meta( $course_id, '_ibedu_price', true ), 2 ),
			'currency'        => ibedu_currency()
		) );

		return array(
			'status'   => 'success',
			'redirect' => ibedu_endpoint_url( 'edu-pay', ( $payment->ID ? $payment->ID : '' ), get_permalink( ibedu_page_id( 'payment' ) ) )
		);
	}

	/**
	 * Output the form to the step 2 (pay page) of the payment page.
	 */
	public function pay_page() {
		$action_url = ( $this->get_option( 'test' ) ) ? $this->test_url : $this->live_url;
		$payment_id = absint( get_query_var( 'edu-pay' ) );

		if ( ! $payment_id ) {
			return;
		}

		$payment = IBEdu_Payment::get_instance( $payment_id );

		if ( ! $payment->ID ) {
			return;
		}

		$course = get_post( $payment->course_id );

		if ( ! $course ) {
			return;
		}

		$amount = number_format( $payment->amount, 2, '.', '' );
		$return_url = '';
		$payment_page_id = ibedu_page_id( 'payment' );

		if ( $payment_page_id ) {
			$return_url = ibedu_endpoint_url( 'edu-thankyou', ( $payment->ID ? $payment->ID : '' ), get_permalink( $payment_page_id ) );
		}

		echo '<form id="ibedu-paypal-form" action="' . esc_url( $action_url ) . '" method="post">';
		echo '<input type="hidden" name="cmd" value="_xclick">';
		echo '<input type="hidden" name="charset" value="utf-8">';
		echo '<input type="hidden" name="business" value="' . esc_attr( $this->get_option( 'business_email' ) ) . '">';
		echo '<input type="hidden" name="return" value="' . esc_url( $return_url ) . '">';
		echo '<input type="hidden" name="notify_url" value="' . esc_url( ibedu_api_url( 'paypalipn' ) ) . '">';
		echo '<input type="hidden" name="currency_code" value="' . ibedu_currency() . '">';
		echo '<input type="hidden" name="item_name" value="' . esc_attr( $course->post_title ) . '">';
		echo '<input type="hidden" name="item_number" value="' . absint( $payment->ID ) . '">';
		echo '<input type="hidden" name="amount" value="' . $amount . '">';
		echo '<div id="paypal-form-buttons"><button type="submit">' . __( 'Continue', 'ibeducator' ) . '</button></div>';
		echo '</form>';
		echo '<div id="paypal-redirect-notice" style="display: none;">' . __( 'Redirecting to PayPal...', 'ibeducator' ) . '</div>';
		echo '<script>(function() {
			document.getElementById("paypal-form-buttons").style.display = "none";
			document.getElementById("paypal-redirect-notice").style.display = "block";
			setTimeout(function() {
				document.getElementById("ibedu-paypal-form").submit();
			}, 1000);
		})();</script>';
	}

	public function thankyou_page() {
		// Thank you message.
		$thankyou_message = $this->get_option( 'thankyou_message' );

		if ( ! empty( $thankyou_message ) ) {
			echo '<div class="ibedu-payment-description">' . wpautop( $thankyou_message ) . '</div>';
		}

		// Show link to student courses page.
		$student_courses_page = get_post( ibedu_page_id( 'student_courses' ) );
		
		if ( $student_courses_page ) {
			echo '<p>' . sprintf( __( 'Go to %s page', 'ibeducator' ), '<a href="' . esc_url( get_permalink( $student_courses_page->ID ) ) . '">' . esc_html( $student_courses_page->post_title ) . '</a>' ) . '</p>';
		}
	}

	public function process_ipn( $request ) {
		$debug = 0;
		$log_file = IBEDUCATOR_PLUGIN_DIR . 'ipn.log';

		// Read POST data
		// reading posted data directly from $_POST causes serialization
		// issues with array data in POST. Reading raw POST data from input stream instead.
		$raw_post_data = file_get_contents( 'php://input' );
		$raw_post_array = explode( '&', $raw_post_data );
		$myPost = array();
		
		foreach ( $raw_post_array as $keyval ) {
			$keyval = explode ('=', $keyval);

			if ( 2 == count( $keyval ) ) {
				$myPost[ $keyval[0] ] = urldecode( $keyval[1] );
			}
		}

		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
		
		if ( function_exists( 'get_magic_quotes_gpc' ) ) {
			$get_magic_quotes_exists = true;
		} else {
			$get_magic_quotes_exists = false;
		}
		
		foreach ( $myPost as $key => $value ) {
			if( true == $get_magic_quotes_exists && 1 == get_magic_quotes_gpc() ) {
				$value = urlencode( stripslashes( $value ) );
			} else {
				$value = urlencode( $value );
			}

			$req .= "&$key=$value";
		}

		// Post IPN data back to PayPal to validate the IPN data is genuine.
		// Without this step anyone can fake IPN data.
		if ( $this->get_option( 'test' ) ) {
			$paypal_url = $this->test_url;
		} else {
			$paypal_url = $this->live_url;
		}

		$ch = curl_init( $paypal_url );

		if ( ! $ch ) {
			return;
		}

		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $req );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );

		if ( $debug ) {
			curl_setopt( $ch, CURLOPT_HEADER, 1 );
			curl_setopt( $ch, CURLINFO_HEADER_OUT, 1 );
		}

		// Set TCP timeout to 30 seconds.
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Connection: Close' ) );

		$res = curl_exec( $ch );

		if ( 0 != curl_errno( $ch ) ) {
			if ( true == $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ) . 'Can\'t connect to PayPal to validate IPN message: ' . curl_error( $ch ) . PHP_EOL, 3, $log_file );
			}

			curl_close( $ch );
			exit;
		} else {
			// Log the entire HTTP response if debug is switched on.
			if ( $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ). 'HTTP request of validation request:' . curl_getinfo( $ch, CURLINFO_HEADER_OUT ) . ' for IPN payload: ' . $req . PHP_EOL, 3, $log_file );
				error_log( date( '[Y-m-d H:i e] ' ). 'HTTP response of validation request: ' . $res . PHP_EOL, 3, $log_file );

				// Split response headers and payload.
				list( $headers, $res ) = explode( "\r\n\r\n", $res, 2 );
			}

			curl_close( $ch );
		}

		// Inspect IPN validation result and act accordingly.
		if ( 0 == strcmp( $res, 'VERIFIED' ) ) {
			if ( isset( $_POST['payment_status'] ) ) {
				$payment_id = ! isset( $_POST['item_number'] ) ? 0 : absint( $_POST['item_number'] );
				$currency = ! isset( $_POST['mc_currency'] ) ? '' : $_POST['mc_currency'];
				$receiver_email = ! isset( $_POST['receiver_email'] ) ? '' : $_POST['receiver_email'];
				$payment_amount = ! isset( $_POST['mc_gross'] ) ? '' : $_POST['mc_gross'];

				if ( $receiver_email != $this->get_option( 'business_email' ) ) {
					return;
				}

				if ( 0 == $payment_id ) {
					return;
				}

				$payment = IBEdu_Payment::get_instance( $payment_id );

				if ( ! $payment->ID ) {
					return;
				}

				if ( $payment_amount != $payment->amount ) {
					return;
				}

				if ( $currency != $payment->currency ) {
					return;
				}

				switch ( $_POST['payment_status'] ) {
					case 'Completed':
						// Update payment status.
						$payment->payment_status = 'complete';
						$payment->save();

						// Add entry.
						$entry = IBEdu_Entry::get_instance();
						$entry->course_id = $payment->course_id;
						$entry->user_id = $payment->user_id;
						$entry->payment_id = $payment->ID;
						$entry->entry_status = 'inprogress';
						$entry->entry_date = date( 'Y-m-d H:i:s' );
						$entry->save();
						break;

					case 'Failed':
					case 'Expired':
					case 'Denied':
					case 'Voided':
						// Update payment status.
						$payment->payment_status = 'failed';
						$payment->save();
						break;
				}
			}
			
			if( $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ) . 'Verified IPN: ' . $req . PHP_EOL, 3, $log_file );
			}
		} else if ( 0 == strcmp( $res, 'INVALID' ) ) {
			if ( $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ) . 'Invalid IPN: ' . $req . PHP_EOL, 3, $log_file );
			}
		}
	}

	public function sanitize_admin_options( $input ) {
		foreach ( $input as $option_name => $value ) {
			switch ( $option_name ) {
				case 'business_email':
					$input[ $option_name ] = sanitize_email( $value );
					break;

				case 'thankyou_message':
					$input[ $option_name ] = wp_kses_post( $value );
					break;
			}
		}

		return $input;
	}
}