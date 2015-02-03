<?php

class IB_Educator_Gateway_Cash extends IB_Educator_Payment_Gateway {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'cash';
		$this->title = __( 'Cash', 'ibeducator' );

		// Setup options.
		$this->init_options( array(
			'description' => array(
				'type'      => 'textarea',
				'label'     => __( 'Instructions for a student', 'ibeducator' ),
				'id'        => 'ib-edu-description',
				'rich_text' => true,
			),
		) );

		add_action( 'ib_educator_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
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

		$redirect_args = array();
		$api = IB_Educator::get_instance();
		$payment = $api->add_payment( $params );

		if ( $payment->ID ) {
			// Record membership switch.
			if ( 'membership' == $payment_type && ! empty( $update_membership ) ) {
				$ms->record_switch( $payment );
			}

			$redirect_args['value'] = $payment->ID;
		}

		return array(
			'status'   => 'pending',
			'redirect' => $this->get_redirect_url( $redirect_args ),
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
				echo '<div class="ib-edu-payment-description">' . wpautop( stripslashes( $description ) ) . '</div>';
			}
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