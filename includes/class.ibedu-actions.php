<?php

class IBEdu_Actions {
	/**
	 * Cancel student's payment for a course.
	 */
	public static function cancel_payment() {
		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_cancel_payment' ) ) {
			return;
		}

		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;

		if ( ! $payment_id ) {
			return;
		}

		$payment = IBEdu_Payment::get_instance( $payment_id );

		// User may cancel his/her pending payments only.
		if ( 'pending' == $payment->payment_status && $payment->user_id == get_current_user_id() ) {
			if ( $payment->update_status( 'cancelled' ) ) {
				wp_redirect( ibedu_endpoint_url( 'edu-message', 'payment-cancelled', get_permalink() ) );
				exit;
			}
		}
	}

	/**
	 * Submit quiz.
	 */
	public static function submit_quiz() {
		if ( empty( $_POST ) ) return;

		$lesson_id = get_the_ID();
		$user_id = get_current_user_id();
		$api = IBEdu_API::get_instance();

		// Verify nonce.
		check_admin_referer( 'ibedu_submit_quiz_' . $lesson_id );

		$questions = $api->get_questions( array( 'lesson_id' => $lesson_id ) );
		$num_questions = count( $questions );
		$num_answers = 0;

		// The student has to submit the answers to all questions.
		if ( ! isset( $_POST['answers'] ) ) {
			ibedu_message( 'quiz', 'empty-answers' );
			return;
		} else if ( is_array( $_POST['answers'] ) ) {
			foreach ( $_POST['answers'] as $answer ) {
				if ( ! empty( $answer ) ) ++$num_answers;
			}

			if ( $num_answers != $num_questions ) {
				ibedu_message( 'quiz', 'empty-answers' );
				return;
			}
		}

		if ( ! $questions ) return;

		$user_answer = '';
		$answer = null;
		$question_meta = null;
		$entry = $api->get_entry( array(
			'user_id'           => $user_id,
			'course_id'         => get_post_meta( $lesson_id, '_ibedu_course', true ),
			'entry_status' => 'inprogress'
		) );
		$automatic_grade = true;

		if ( ! $entry ) return;

		// Process answers only if the quiz wasn't yet submitted.
		if ( $api->is_quiz_submitted( $lesson_id, $entry->ID ) ) return;

		$answered = 0;
		$correct = 0;
		$choices = $api->get_choices( $lesson_id, true );

		// Check answers to the quiz questions.
		foreach ( $questions as $question ) {
			if ( ! isset( $_POST['answers'][ $question->ID ] ) ) {
				// Student has to submit an answer.
				continue;
			}

			// Every question type needs a specific way to check for the valid answer.
			switch ( $question->question_type ) {
				// Multiple Choice Question.
				case 'multiplechoice':
					$user_answer = absint( $_POST['answers'][ $question->ID ] );

					if ( isset( $choices[ $question->ID ] ) && isset( $choices[ $question->ID ][ $user_answer ] ) ) {
						$choice = $choices[ $question->ID ][ $user_answer ];

						// Add answer to database.
						$added = $api->add_student_answer( array(
							'question_id' => $question->ID,
							'entry_id'    => $entry->ID,
							'correct'     => $choice->correct,
							'choice_id'   => $choice->ID
						) );

						if ( 1 == $added ) ++$answered;
						if ( 1 == $choice->correct ) ++$correct;
					}

					break;

				// Written Answer Question.
				case 'writtenanswer':
					// We cannot check written answers automatically.
					if ( $automatic_grade ) $automatic_grade = false;

					$user_answer = stripslashes( $_POST['answers'][ $question->ID ] );

					if ( empty( $user_answer ) ) {
						continue;
					}

					// Add answer to database.
					$added = $api->add_student_answer( array(
						'question_id' => $question->ID,
						'entry_id'    => $entry->ID,
						'correct'     => -1,
						'answer_text' => $user_answer
					) );

					if ( 1 == $added ) ++$answered;
					
					break;
			}
		}

		if ( $answered == $num_questions ) {
			$grade_data = array(
				'lesson_id' => $lesson_id,
				'entry_id'  => $entry->ID,
			);

			if ( $automatic_grade ) {
				$grade_data['grade'] = round( $correct / $answered * 100 );
				$grade_data['status'] = 'approved';
			} else {
				$grade_data['grade'] = 0;
				$grade_data['status'] = 'pending';
			}

			$api->add_quiz_grade( $grade_data );

			wp_redirect( ibedu_endpoint_url( 'edu-message', 'quiz-submitted', get_permalink() ) );
			exit;
		}
	}

	/**
	 * Pay for a course.
	 */
	public static function payment() {
		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_submit_payment' ) ) {
			return;
		}

		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		if ( ! $course_id ) return;

		// Get the course price.
		$course_price = get_post_meta( $course_id, '_ibedu_price', true );

		// Create an account
		$errors = array();
		$username = '';
		$email = '';
		$payment_method = '';
		$user_id = get_current_user_id();

		// Get account username.
		if ( ! isset( $_POST['account_username'] ) || empty( $_POST['account_username'] ) ) {
			if ( ! $user_id ) {
				$errors[] = 'account_info_empty';
			}
		} else {
			$username = $_POST['account_username'];
		}

		// Get account email.
		if ( ! isset( $_POST['account_email'] ) || empty( $_POST['account_email'] ) ) {
			if ( ! $user_id && ! in_array( 'account_info_empty', $errors ) ) {
				$errors[] = 'account_info_empty';
			}
		} else {
			$email = $_POST['account_email'];
		}

		// Get payment method.
		$gateways = IBEdu_Main::get_gateways();

		if ( ! isset( $_POST['payment_method'] ) || ! array_key_exists( $_POST['payment_method'], $gateways ) ) {
			if ( $course_price ) {
				// Course is not free, payment method is required.
				$errors[] = 'empty_payment_method';
			}
		} else {
			$payment_method = $_POST['payment_method'];
		}

		// Attempt to register the user.
		if ( ! $user_id && $username && $email ) {
			$user_id = register_new_user( $username, $email );

			if ( is_wp_error( $user_id ) ) {
				$errors[] = $user_id->get_error_code();
			} else {
				// Log the user in.
				wp_set_auth_cookie( $user_id );

				// Set the user's role to student.
				$current_user = get_user_by( 'id', $user_id );
				$current_user->remove_role( 'subscriber' );
				$current_user->add_role( 'student' );
			}
		}

		// Any errors?
		if ( ! empty( $errors ) ) {
			ibedu_message( 'payment_errors', $errors );
			return;
		}

		$api = IBEdu_API::get_instance();
		$access_status = $api->get_access_status( $course_id, $user_id );

		// Student can pay for a course only if he/she completed this course or didn't register for it yet.
		if ( in_array( $access_status, array( 'course_complete', 'forbidden' ) ) ) {
			if ( ! $course_price ) {
				// The course is free.
				$payment = IBEdu_Payment::get_instance();
				$payment->course_id = $course_id;
				$payment->user_id = $user_id;
				$payment->amount = 0.00;
				$payment->payment_gateway = 'free';
				$payment->payment_status = 'complete';
				$payment->payment_date = date( 'Y-m-d H:i:s' );

				if ( $payment->save() ) {
					$entry = IBEdu_Entry::get_instance();
					$entry->course_id = $course_id;
					$entry->user_id = $user_id;
					$entry->payment_id = $payment->ID;
					$entry->entry_status = 'inprogress';
					$entry->entry_date = date( 'Y-m-d H:i:s' );
					$entry->save();
				}

				wp_safe_redirect( get_permalink( $course_id ) );
			} else {
				// The course is not free.
				$gateway = $gateways[ $payment_method ];
				$result = $gateway->process_payment( $course_id, $user_id );
				wp_safe_redirect( $result['redirect'] );
			}

			exit;
		}
	}
}