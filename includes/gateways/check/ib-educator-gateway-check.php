<?php

class IB_Educator_Gateway_Check extends IB_Educator_Payment_Gateway {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'check';
		$this->title = __( 'Check', 'ibeducator' );

		// Setup options.
		$this->init_options( array(
			'description' => array(
				'type'      => 'textarea',
				'label'     => __( 'Instructions for a student', 'ibeducator' ),
				'id'        => 'ib-edu-description',
				'rich_text' => true,
			)
		) );

		add_action( 'ib_educator_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	}

	/**
	 * Process payment.
	 *
	 * @return array
	 */
	public function process_payment( $course_id, $user_id ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$api = IB_Educator::get_instance();
		$redirect_args = array();

		// Add payment.
		$payment = $api->add_payment( array(
			'course_id'       => $course_id,
			'user_id'         => $user_id,
			'payment_status'  => 'pending',
			'payment_gateway' => $this->get_id(),
			'amount'          => number_format( ib_edu_get_course_price( $course_id ), 2 ),
			'currency'        => ib_edu_get_currency()
		) );

		if ( $payment->ID ) {
			$redirect_args['value'] = $payment->ID;
		}

		return array(
			'status'   => 'pending',
			'redirect' => $this->get_redirect_url( $redirect_args )
		);
	}

	/**
	 * Output thank you information.
	 */
	public function thankyou_page() {
		$description = $this->get_option( 'description' );

		if ( ! empty( $description ) ) {
			echo '<h3>' . __( 'Payment Instructions', 'ibeducator' ) . '</h3>';
			echo '<div class="ib-edu-payment-description">' . wpautop( stripslashes( $description ) ) . '</div>';
		}

		// Show link to student courses page.
		$student_courses_page = get_post( ib_edu_page_id( 'student_courses' ) );
		
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
					$input[ $option_name ] = wp_kses_data( $value );
					break;
			}
		}

		return $input;
	}
}