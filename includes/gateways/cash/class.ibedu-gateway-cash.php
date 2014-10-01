<?php

class IBEdu_Gateway_Cash extends IBEdu_Payment_Gateway {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'cash';
		$this->title = __( 'Cash', 'ibeducator' );

		// Setup options.
		$this->init_options( array(
			'description' => array(
				'type'  => 'textarea',
				'label' => __( 'Instructions for a student', 'ibeducator' )
			)
		) );

		add_action( 'ibedu_thankyou_cash', array( $this, 'thankyou_page' ) );
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
		$redirect_args = array();

		// Add payment
		$payment = $api->add_payment( array(
			'course_id'       => $course_id,
			'user_id'         => $user_id,
			'payment_status'  => 'pending',
			'payment_gateway' => $this->get_id(),
			'amount'          => number_format( get_post_meta( $course_id, '_ibedu_price', true ), 2 ),
			'currency'        => ibedu_currency()
		) );

		if ( $payment->ID ) {
			$redirect_args['value'] = $payment->ID;
		}

		return array(
			'status'   => 'success',
			'redirect' => $this->get_redirect_url( $redirect_args )
		);
	}

	/**
	 * Output thank you information.
	 */
	public function thankyou_page() {
		$payment_id = get_query_var( 'edu-thankyou' );

		if ( ! $payment_id ) {
			echo '<p>' . __( 'You\'ve paid for this course already.' ) . '</p>';
		} else {
			$description = $this->get_option( 'description' );

			if ( ! empty( $description ) ) {
				echo '<h3>' . __( 'Payment Instructions', 'ibeducator' ) . '</h3>';
				echo '<div class="ibedu-payment-description">' . wpautop( $description ) . '</div>';
			}
		}

		// Show link to student courses page.
		$student_courses_page = get_post( ibedu_page_id( 'student_courses' ) );
		
		if ( $student_courses_page ) {
			echo '<p>' . sprintf( __( 'Go to %s page', 'ibeducator' ), '<a href="' . esc_url( get_permalink( $student_courses_page->ID ) ) . '">' . esc_html( $student_courses_page->post_title ) . '</a>' ) . '</p>';
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
				case 'description':
					$input[ $option_name ] = wp_kses_post( $value );
					break;
			}
		}

		return $input;
	}
}