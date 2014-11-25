<?php

class IB_Educator_Admin_Actions {
	/**
	 * Edit course entry.
	 */
	public static function edit_entry() {
		$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
		$entry = IB_Educator_Entry::get_instance( $entry_id );

		if ( count( $_POST ) ) {
			// Verify nonce.
			check_admin_referer( 'ib_educator_edit_entry_' . $entry_id );
			
			$api = IB_Educator::get_instance();
			$current_user_id = get_current_user_id();
			$who = '';

			// Check capabilities.
			if ( current_user_can( 'manage_educator' ) ) {
				$who = 'admin';
			} elseif ( $entry->course_id && current_user_can( 'edit_ib_educator_course', $entry->course_id ) ) {
				$who = 'lecturer';
			}

			if ( empty( $who ) ) {
				return;
			}

			// Payment ID.
			if ( 'admin' == $who && isset( $_POST['payment_id'] ) ) {
				$payment = IB_Educator_Payment::get_instance( $_POST['payment_id'] );

				if ( $payment->ID ) {
					$entry->payment_id = $payment->ID;
				}
			}

			// Student ID.
			if ( 'admin' == $who && isset( $_POST['student_id'] ) ) {
				$entry->user_id = intval( $_POST['student_id'] );
			}

			// Course ID.
			if ( 'admin' == $who && isset( $_POST['course_id'] ) ) {
				$entry->course_id = intval( $_POST['course_id'] );
			}		

			// Entry status.
			if ( isset( $_POST['entry_status'] ) && array_key_exists( $_POST['entry_status'], IB_Educator_Entry::get_statuses() ) ) {
				$entry->entry_status = $_POST['entry_status'];
			}

			// Grade.
			if ( isset( $_POST['grade'] ) && is_numeric( $_POST['grade'] ) ) {
				$entry->grade = $_POST['grade'];
			}

			// Entry date.
			$entry->entry_date = date( 'Y-m-d H:i:s' );

			if ( $entry->save() ) {
				wp_redirect( admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . $entry->ID . '&edu-message=saved' ) );
				exit;
			}
		}
	}

	/**
	 * Edit payment action.
	 */
	public static function edit_payment() {
		$payment_id = isset( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;
		$payment = IB_Educator_Payment::get_instance( $payment_id );
		$errors = array();

		if ( count( $_POST ) ) {
			// Verify nonce.
			check_admin_referer( 'ib_educator_edit_payment_' . $payment_id );

			// Capability check.
			if ( ! current_user_can( 'manage_educator' ) ) return;

			// Student ID.
			if ( empty( $payment->user_id ) ) {
				if ( ! empty( $_POST['student_id'] ) && is_numeric( $_POST['student_id'] ) ) {
					$payment->user_id = $_POST['student_id'];
				} else {
					$errors[] = 'empty_student_id';
				}
			}

			// Course ID.
			if ( empty( $payment->course_id ) ) {
				if ( ! empty( $_POST['course_id'] ) && is_numeric( $_POST['course_id'] ) ) {
					$payment->course_id = $_POST['course_id'];
				} else {
					$errors[] = 'empty_course_id';
				}
			}

			// Amount.
			if ( isset( $_POST['amount'] ) && is_numeric( $_POST['amount'] ) ) {
				$payment->amount = $_POST['amount'];
			}

			// Payment status.
			if ( isset( $_POST['payment_status'] ) && array_key_exists( $_POST['payment_status'], IB_Educator_Payment::get_statuses() ) ) {
				$payment->payment_status = $_POST['payment_status'];
			}

			// Payment gateway.
			if ( isset( $_POST['payment_gateway'] ) ) {
				$payment->payment_gateway = sanitize_title( $_POST['payment_gateway'] );
			}

			if ( ! empty( $errors ) ) {
				ib_edu_message( 'edit_payment_errors', $errors );
				return;
			}

			if ( $payment->save() ) {
				$api = IB_Educator::get_instance();
				$entry_saved = true;

				if ( isset( $_POST['create_entry'] ) && ! $api->get_entry( array( 'payment_id' => $payment->ID ) ) ) {
					$entry = IB_Educator_Entry::get_instance();
					$entry->course_id = $payment->course_id;
					$entry->user_id = $payment->user_id;
					$entry->payment_id = $payment->ID;
					$entry->entry_status = 'inprogress';
					$entry->entry_date = date( 'Y-m-d H:i:s' );
					$entry_saved = $entry->save();

					if ( $entry_saved ) {
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
				}

				if ( $entry_saved ) {
					wp_redirect( admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment->ID . '&edu-message=saved' ) );
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
		check_admin_referer( 'ib_educator_payments_settings' );

		$gateways = IB_Educator_Main::get_gateways();

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

		wp_redirect( admin_url( 'admin.php?page=ib_educator_admin&tab=payment&gateway_id=' . $gateway_id . '&edu-message=' . $message ) );
	}
}