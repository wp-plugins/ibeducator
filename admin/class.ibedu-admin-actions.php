<?php

class IBEdu_Admin_Actions {
	/**
	 * Edit course entry.
	 */
	public static function edit_entry() {
		$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;

		if ( ! $entry_id ) return;

		$entry = IBEdu_Entry::get_instance( $entry_id );

		if ( ! $entry->ID ) return;

		if ( count( $_POST ) ) {
			// Verify nonce.
			check_admin_referer( 'ibedu_edit_entry_' . $entry_id );
			
			$api = IBEdu_API::get_instance();

			// Capability check.
			if ( ! current_user_can( 'manage_educator' ) && ! in_array( $entry->course_id, $api->get_lecturer_courses( get_current_user_id() ) ) ) {
				return;
			}

			if ( isset( $_POST['entry_status'] ) && array_key_exists( $_POST['entry_status'], IBEdu_Entry::get_statuses() ) ) {
				$entry->entry_status = $_POST['entry_status'];
			}

			if ( isset( $_POST['grade'] ) && is_numeric( $_POST['grade'] ) ) {
				$entry->grade = $_POST['grade'];
			}

			if ( $entry->save() ) {
				wp_redirect( admin_url( 'admin.php?page=ibedu_entries&edu-action=edit-entry&entry_id=' . $entry_id . '&edu-message=saved' ) );
				exit;
			}
		}
	}

	/**
	 * Edit payment action.
	 */
	public static function edit_payment() {
		$payment_id = isset( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;

		if ( ! $payment_id ) return;

		$payment = IBEdu_Payment::get_instance( $payment_id );

		if ( ! $payment->ID ) return;

		if ( count( $_POST ) ) {
			// Verify nonce.
			check_admin_referer( 'ibedu_edit_payment_' . $payment_id );

			// Capability check.
			if ( ! current_user_can( 'manage_educator' ) ) return;

			if ( isset( $_POST['amount'] ) && is_numeric( $_POST['amount'] ) ) {
				$payment->amount = $_POST['amount'];
			}

			if ( isset( $_POST['payment_status'] ) && array_key_exists( $_POST['payment_status'], IBEdu_Payment::get_statuses() ) ) {
				$payment->payment_status = $_POST['payment_status'];
			}

			if ( $payment->save() ) {
				$api = IBEdu_API::get_instance();
				$entry_saved = true;

				if ( 'complete' == $payment->payment_status && ! $api->get_entry( array( 'payment_id' => $payment->ID ) ) ) {
					$entry = IBEdu_Entry::get_instance();
					$entry->course_id = $payment->course_id;
					$entry->user_id = $payment->user_id;
					$entry->payment_id = $payment->ID;
					$entry->entry_status = 'inprogress';
					$entry->entry_date = date( 'Y-m-d H:i:s' );
					$entry_saved = $entry->save();
				}

				if ( $entry_saved ) {
					wp_redirect( admin_url( 'admin.php?page=ibedu_payments&edu-action=edit-payment&payment_id=' . $payment_id . '&edu-message=saved' ) );
					exit;
				}
			}
		}
	}

	/**
	 * Edit payment gateway action.
	 */
	public static function edit_payment_gateway() {
		if ( ! isset( $_POST['gateway_id'] ) ) return;
		
		$gateway_id = sanitize_title( $_POST['gateway_id'] );

		// Verify nonce.
		check_admin_referer( 'ibedu_payments_settings' );

		$gateways = IBEdu_Main::get_gateways();

		if ( ! isset( $gateways[ $gateway_id ] ) ) return;

		// Capability check.
		if ( ! current_user_can( 'manage_educator' ) ) return;

		$saved = $gateways[ $gateway_id ]->save_admin_options();
		$message = '';

		if ( true === $saved ) {
			$message = 'saved';
		} else {
			$message = 'not_saved';
		}

		wp_redirect( admin_url( 'admin.php?page=ibedu_admin&tab=payment&gateway_id=' . $gateway_id . '&edu-message=' . $message ) );
	}
}