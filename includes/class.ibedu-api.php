<?php

class IBEdu_API {
	private static $instance = null;
	private function __construct() {}

	/**
	 * Get instance.
	 *
	 * @return IBEdu_API
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get course access status.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return string
	 */
	public function get_access_status( $course_id, $user_id ) {
		global $wpdb;
		$status = '';
		$sql = 'SELECT ep.course_id, ep.user_id, ep.payment_status, ee.entry_status FROM ' . $wpdb->prefix . 'ibedu_payments ep
			LEFT JOIN ' . $wpdb->prefix . 'ibedu_entries ee ON ee.payment_id=ep.ID
			WHERE ep.course_id=%d AND ep.user_id=%d';
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $course_id, $user_id ) );
		$has_complete = false;
		$has_cancelled = false;

		if ( $results ) {
			foreach ( $results as $result ) {
				if ( 'complete' == $result->entry_status ) {
					$has_complete = true;
				} else if ( 'cancelled' == $result->entry_status ) {
					$has_cancelled = true;
				} else {
					// Found payment/entry record that is not complete nor cancelled.
					if ( 'pending' == $result->entry_status ) {
						$status = 'pending_entry';
					} if ( 'inprogress' == $result->entry_status ) {
						$status = 'inprogress';
					} else if ( 'pending' == $result->payment_status ) {
						$status = 'pending_payment';
					}
				}
			}
		}

		if ( empty( $status ) ) {
			$status = ( $has_complete ) ? 'course_complete' : 'forbidden';
		}

		return $status;
	}

	/**
	 * Determine if a user can pay for a given course.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return boolean
	 */
	public function user_can_pay( $course_id, $user_id ) {
		return in_array( $this->get_access_status( $course_id, $user_id ), array( 'forbidden', 'course_complete' ) );
	}

	/**
	 * Save payment to database.
	 *
	 * @param array $data
	 * @return IBEdu_Payment
	 */
	public function add_payment( $data ) {
		// Record payment.
		$payment = IBEdu_Payment::get_instance();
		$payment->course_id = $data['course_id'];
		$payment->user_id = $data['user_id'];
		$payment->payment_gateway = $data['payment_gateway'];
		$payment->payment_status = $data['payment_status'];
		$payment->amount = $data['amount'];
		$payment->currency = $data['currency'];
		$payment->save();
		return $payment;
	}

	/**
	 * Get entry from database.
	 *
	 * @param array $args
	 * @return false|IBEdu_Entry
	 */
	public function get_entry( $args ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'ibedu_entries WHERE 1';

		// Filter by payment_id.
		if ( isset( $args['payment_id'] ) ) {
			$sql .= ' AND payment_id=' . absint( $args['payment_id'] );
		}

		// Filter by course_id.
		if ( isset( $args['course_id'] ) ) {
			$sql .= ' AND course_id=' . absint( $args['course_id'] );
		}

		// Filter by user_id.
		if ( isset( $args['user_id'] ) ) {
			$sql .= ' AND user_id=' . absint( $args['user_id'] );
		}

		// Filter by entry_status.
		if ( isset( $args['entry_status'] ) ) {
			$sql .= " AND entry_status='" . esc_sql( $args['entry_status'] ) . "'";
		}

		$row = $wpdb->get_row( $sql );

		if ( $row ) {
			return IBEdu_Entry::get_instance( $row );
		}

		return false;
	}

	/**
	 * Get entries.
	 *
	 * @param array $args
	 * @return false|array of IBEdu_Entry objects
	 */
	public function get_entries( $args ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'ibedu_entries WHERE 1';

		// Filter by course_id.
		if ( isset( $args['course_id'] ) ) {
			$course_id = array();

			if ( is_array( $args['course_id'] ) ) {
				foreach ( $args['course_id'] as $id ) {
					$course_id[] = absint( $id );
				}
			} else {
				$course_id[] = absint( $args['course_id'] );
			}

			if ( ! empty( $course_id ) ) {
				$sql .= ' AND course_id IN (' . implode( ',', $course_id ) . ')';
			}
		}

		// Filter by user_id.
		if ( isset( $args['user_id'] ) ) {
			$sql .= ' AND user_id=' . absint( $args['user_id'] );
		}

		// Filter by entry status.
		if ( isset( $args['entry_status'] ) ) {
			$sql .= " AND entry_status='" . esc_sql( $args['entry_status'] ) . "'";
		}

		// With or without pagination?
		$has_pagination = isset( $args['page'] ) && isset( $args['per_page'] ) && is_numeric( $args['page'] ) && is_numeric( $args['per_page'] );
		$pagination_sql = '';

		if ( $has_pagination ) {
			$num_rows = $wpdb->get_var( str_replace( 'SELECT *', 'SELECT count(1)', $sql ) );
			$pagination_sql .= ' LIMIT ' . ( ( $args['page'] - 1 ) * $args['per_page'] ) . ', ' . $args['per_page'];
		}

		$entries = $wpdb->get_results( $sql . ' ORDER BY entry_date DESC' . $pagination_sql );

		if ( $entries ) {
			$entries = array_map( array( 'IBEdu_Entry', 'get_instance' ), $entries );
		}

		if ( $has_pagination ) {
			return array(
				'num_pages' => ceil( $num_rows / $args['per_page'] ),
				'rows'  => $entries
			);
		}

		return $entries;
	}

	/**
	 * Get entries count grouped by entry status.
	 *
	 * @param array $args
	 * @return false|array
	 */
	public function get_entries_count( $args = array() ) {
		global $wpdb;

		$sql = 'SELECT entry_status, count(1) as num_rows FROM ' . $wpdb->prefix . 'ibedu_entries WHERE 1';

		// Filter by course_id.
		if ( isset( $args['course_id'] ) ) {
			$course_id = array();

			if ( is_array( $args['course_id'] ) ) {
				foreach ( $args['course_id'] as $id ) {
					$course_id[] = absint( $id );
				}
			} else {
				$course_id[] = absint( $args['course_id'] );
			}

			if ( ! empty( $course_id ) ) {
				$sql .= ' AND course_id IN (' . implode( ',', $course_id ) . ')';
			}
		}

		$sql .= ' GROUP BY entry_status';

		return $wpdb->get_results( $sql, OBJECT_K );
	}

	/**
	 * Get student's courses.
	 *
	 * @param int $user_id
	 * @return false|array of WP_Post objects grouped by entry status
	 */
	public function get_student_courses( $user_id ) {
		global $wpdb;
		
		if ( absint( $user_id ) != $user_id ) return false;
		
		$ids = array();

		$entries = $this->get_entries( array( 'user_id'  => $user_id ) );
		
		if ( $entries ) {
			$statuses = array();

			foreach ( $entries as $row ) {
				$ids[] = $row->course_id;

				if ( isset( $statuses[ $row->entry_status ] ) ) {
					++$statuses[ $row->entry_status ];
				} else {
					$statuses[ $row->entry_status ] = 0;
				}
			}

			$query = new WP_Query( array(
				'post_type' => 'ibedu_course',
				'post__in'  => $ids,
				'orderby'   => 'post__in',
				'order'     => 'ASC'
			) );

			if ( $query->have_posts() ) {
				$posts = array();

				foreach ( $query->posts as $post ) {
					$posts[ $post->ID ] = $post;
				}

				return array(
					'entries' => $entries,
					'courses'     => $posts,
					'statuses'    => $statuses
				);
			}
		}

		return false;
	}

	/**
	 * Get courses that are pending payment.
	 *
	 * @param int $user_id
	 * @return false|array of WP_Post objects
	 */
	public function get_pending_courses( $user_id ) {
		global $wpdb;
		$ids = array();
		$payments = $this->get_payments( array( 'user_id' => $user_id, 'payment_status' => array( 'pending' ) ) );

		if ( $payments ) {
			$payment_ids = array();

			foreach ( $payments as $payment ) {
				$ids[] = $payment->course_id;
				$payment_ids[ $payment->course_id ] = $payment->ID;
			}

			$query = new WP_Query( array(
				'post_type' => 'ibedu_course',
				'post__in'  => $ids,
				'orderby'   => 'post__in',
				'order'     => 'ASC'
			) );

			if ( $query->have_posts() ) {
				$posts = array();

				foreach ( $query->posts as $post ) {
					$post->edu_payment_id = $payment_ids[ $post->ID ];
					$posts[ $post->ID ] = $post;
				}

				return $posts;
			}
		}

		return false;
	}

	/**
	 * Get lessons for a course.
	 *
	 * @param int $course_id
	 * @return false|WP_Query
	 */
	public function get_lessons( $course_id ) {
		if ( ! is_numeric( $course_id ) ) return false;

		return new WP_Query( array(
			'post_type'  => 'ibedu_lesson',
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
			'meta_query' => array(
				array( 'key' => '_ibedu_course', 'value' => $course_id, 'compare' => '=' )
			)
		) );
	}

	/**
	 * Get the number of lessons in a course.
	 *
	 * @param int $course_id
	 * @return int
	 */
	public function get_num_lessons( $course_id ) {
		global $wpdb;
		$num_lessons = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(1) FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
				WHERE p.post_type='ibedu_lesson' AND pm.meta_key='_ibedu_course' AND pm.meta_value=%d",
				$course_id
			)
		);

		return $num_lessons;
	}

	/**
	 * Get payments.
	 *
	 * @param array $args
	 * @return false|array of IBEdu_Payment objects
	 */
	public function get_payments( $args ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'ibedu_payments WHERE 1';

		// Filter by user_id.
		if ( isset( $args['user_id'] ) ) {
			$sql .= ' AND user_id=' . absint( $args['user_id'] );
		}

		// Filter by course_id.
		if ( isset( $args['course_id'] ) ) {
			$sql .= ' AND course_id=' . absint( $args['course_id'] );
		}

		// Filter by payment status.
		if ( isset( $args['payment_status'] ) && is_array( $args['payment_status'] ) ) {
			// Escape SQL.
			foreach ( $args['payment_status'] as $key => $payment_status ) {
				$args['payment_status'][ $key ] = esc_sql( $payment_status );
			}

			$sql .= " AND payment_status IN ('" . implode( "', '", $args['payment_status'] ) . "')";
		}

		// With or without pagination
		$has_pagination = isset( $args['page'] ) && isset( $args['per_page'] ) && is_numeric( $args['page'] ) && is_numeric( $args['per_page'] );
		$pagination_sql = '';

		if ( $has_pagination ) {
			$num_rows = $wpdb->get_var( str_replace( 'SELECT *', 'SELECT count(1)', $sql ) );
			$pagination_sql .= ' LIMIT ' . ( ( $args['page'] - 1 ) * $args['per_page'] ) . ', ' . $args['per_page'];
		}

		$payments = $wpdb->get_results( $sql . ' ORDER BY payment_date DESC' . $pagination_sql );

		if ( $payments ) {
			$payments = array_map( array( 'IBEdu_Payment', 'get_instance' ), $payments );
		}

		if ( $has_pagination ) {
			return array(
				'num_pages' => ceil( $num_rows / $args['per_page'] ),
				'rows'      => $payments
			);
		}

		return $payments;
	}

	/**
	 * Get payments count groupped by payment status.
	 */
	public function get_payments_count() {
		global $wpdb;
		return $wpdb->get_results( "SELECT payment_status, count(1) as num_rows FROM {$wpdb->prefix}ibedu_payments GROUP BY payment_status", OBJECT_K );
	}

	/**
	 * Get courses of a lecturer.
	 *
	 * @param int $user_id
	 * @return false|array of course_ids
	 */
	public function get_lecturer_courses( $user_id ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_author=%d AND post_type='ibedu_course'", $user_id ) );
	}

	/**
	 * Get quiz questions.
	 *
	 * @param array $args
	 * @return false|array of IBEdu_Question objects
	 */
	public function get_questions( $args ) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'ibedu_questions WHERE 1';

		if ( isset( $args['lesson_id'] ) ) {
			$sql .= ' AND lesson_id=' . absint( $args['lesson_id'] );
		}

		$sql .= ' ORDER BY menu_order ASC';
		$questions = $wpdb->get_results( $sql );

		if ( $questions ) {
			$questions = array_map( array( 'IBEdu_Question', 'get_instance' ), $questions );
		}

		return $questions;
	}

	/**
	 * Get all answers choices for a lesson.
	 *
	 * @param int $lesson_id
	 * @return false|array
	 */
	public function get_choices( $lesson_id, $sorted = false ) {
		global $wpdb;
		$choices = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ibedu_choices WHERE question_id IN (SELECT question_id FROM {$wpdb->prefix}ibedu_questions WHERE lesson_id = %d) ORDER BY menu_order ASC",
			$lesson_id
		) );

		if ( ! $sorted ) {
			return $choices;
		}

		if ( $choices ) {
			$sorted = array();

			foreach ( $choices as $row ) {
				if ( ! isset( $sorted[ $row->question_id ] ) ) {
					$sorted[ $row->question_id ] = array();
				}

				$sorted[ $row->question_id ][ $row->ID ] = $row;
			}

			return $sorted;
		}

		return false;
	}

	/**
	 * Get the available choices for a multiple answer question.
	 *
	 * @param int $question_id
	 * @return false|array
	 */
	public function get_question_choices( $question_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, choice_text, correct, menu_order "
			. "FROM {$wpdb->prefix}ibedu_choices "
			. "WHERE question_id = %d "
			. "ORDER BY menu_order ASC",
			$question_id
		), OBJECT_K );
	}

	/**
	 * Add question answer choice to the database.
	 *
	 * @param array $data
	 * @return false|int
	 */
	public function add_choice( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'ibedu_choices',
			array(
				'question_id' => $data['question_id'],
				'choice_text' => $data['choice_text'],
				'correct'     => $data['correct'],
				'menu_order'  => $data['menu_order']
			),
			array( '%d', '%s', '%d', '%d' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Update question answer choice in the database.
	 *
	 * @param array $data
	 * @return false|int
	 */
	public function update_choice( $choice_id, $data ) {
		global $wpdb;

		return $wpdb->update(
			$wpdb->prefix . 'ibedu_choices',
			array(
				'choice_text' => $data['choice_text'],
				'correct'     => $data['correct'],
				'menu_order'  => $data['menu_order']
			),
			array( 'ID' => $choice_id ),
			array( '%s', '%d', '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Delete question answer choice from the database.
	 *
	 * @param int $choice_id
	 * @return false|int false on error, number of rows updated on success.
	 */
	public function delete_choice( $choice_id ) {
		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . 'ibedu_choices',
			array( 'ID' => $choice_id ),
			array( '%d' )
		);
	}

	/**
	 * Delete question answer choices from the database.
	 *
	 * @param int $question_id
	 * @return false|int false on error, number of rows updated on success.
	 */
	public function delete_choices( $question_id ) {
		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . 'ibedu_choices',
			array( 'question_id' => $question_id ),
			array( '%d' )
		);
	}

	/**
	 * Add answer to a question in a quiz.
	 *
	 * @param array $data
	 * @return false|int
	 */
	public function add_student_answer( $data ) {
		global $wpdb;
		$insert_data = array(
			'question_id' => ! isset( $data['question_id'] ) ? 0 : $data['question_id'],
			'entry_id'    => ! isset( $data['entry_id'] ) ? 0 : $data['entry_id'],
			'correct'     => ! isset( $data['correct'] ) ? 0 : $data['correct'],
			'choice_id'   => ! isset( $data['choice_id'] ) ? 0 : $data['choice_id']
		);
		$data_format = array( '%d', '%d', '%d', '%d' );
		
		if ( isset( $data['answer_text'] ) ) {
			$insert_data['answer_text'] = $data['answer_text'];
			$data_format[] = '%s';
		}

		return $wpdb->insert( $wpdb->prefix . 'ibedu_answers', $insert_data, $data_format );
	}

	/**
	 * Get student's answers for a given lesson.
	 *
	 * @param int $lesson_id
	 * @param int $entry_id
	 * @return false|array
	 */
	public function get_student_answers( $lesson_id, $entry_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, ID, entry_id, question_id, choice_id, correct, answer_text "
				. "FROM {$wpdb->prefix}ibedu_answers "
				. "WHERE entry_id = %d AND question_id IN (SELECT question_id FROM {$wpdb->prefix}ibedu_questions WHERE lesson_id = %d)",
				$entry_id,
				$lesson_id
			),
			OBJECT_K
		);
	}

	/**
	 * Add grade for a quiz.
	 *
	 * @param array $data
	 * @return false|int
	 */
	public function add_quiz_grade( $data ) {
		global $wpdb;

		return $wpdb->insert(
			$wpdb->prefix . 'ibedu_grades',
			array(
				'lesson_id' => $data['lesson_id'],
				'entry_id'  => $data['entry_id'],
				'grade'     => $data['grade'],
				'status'    => $data['status'],
			),
			array( '%d', '%d', '%f', '%s' )
		);
	}

	/**
	 * Update quiz grade.
	 *
	 * @param array $data
	 * @return int
	 */
	public function update_quiz_grade( $grade_id, $data ) {
		global $wpdb;
		$insert_data = array();
		$insert_format = array();

		foreach ( $data as $key => $value ) {
			switch ( $key ) {
				case 'grade':
					$insert_data[ $key ] = $value;
					$insert_format[] = '%f';
					break;

				case 'status':
					$insert_data[ $key ] = $value;
					$insert_format[] = '%s';
					break;
			}
		}

		return $wpdb->update(
			"{$wpdb->prefix}ibedu_grades",
			$insert_data,
			array( 'ID' => $grade_id ),
			$insert_format,
			array( '%d' )
		);
	}

	/**
	 * Check if the quiz was submitted for a given lesson.
	 *
	 * @param int $lesson_id
	 * @param int $entry_id
	 * @return boolean
	 */
	public function is_quiz_submitted( $lesson_id, $entry_id ) {
		global $wpdb;

		$submitted = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(1) FROM {$wpdb->prefix}ibedu_grades WHERE lesson_id=%d AND entry_id=%d LIMIT 1",
			$lesson_id,
			$entry_id
		) );

		return ( 1 == $submitted );
	}

	/**
	 * Get student's grade for the given quiz.
	 *
	 * @param int $lesson_id
	 * @param int $entry_id
	 * @return array
	 */
	public function get_quiz_grade( $lesson_id, $entry_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}ibedu_grades WHERE lesson_id=%d AND entry_id=%d",
			$lesson_id,
			$entry_id
		) );
	}
}