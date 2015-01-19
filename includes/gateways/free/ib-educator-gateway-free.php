<?php

class IB_Educator_Gateway_Free extends IB_Educator_Payment_Gateway {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'free';
		$this->title = __( 'Free', 'ibeducator' );
		$this->editable = false;
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
			'payment_status'  => 'complete',
			'payment_gateway' => $this->get_id(),
			'amount'          => 0.0,
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

		if ( 0.0 == $params['amount'] ) {
			$api = IB_Educator::get_instance();
			$payment = $api->add_payment( $params );

			if ( $payment->ID ) {
				if ( 'course' == $payment_type ) {
					// Setup course entry.
					$entry = IB_Educator_Entry::get_instance();
					$entry->course_id = $object_id;
					$entry->user_id = $user_id;
					$entry->payment_id = $payment->ID;
					$entry->entry_status = 'inprogress';
					$entry->entry_date = date( 'Y-m-d H:i:s' );
					$entry->save();
				} elseif ( 'membership' == $payment_type ) {
					// Record membership switch.
					if ( ! empty( $update_membership ) ) {
						$ms->record_switch( $payment );
					}

					// Setup membership automatically as it's free.
					$ms->setup_membership( $user_id, $object_id );
				}
			}
		}

		return array(
			'status'   => 'complete',
			'redirect' => get_permalink( $object_id ),
		);
	}
}