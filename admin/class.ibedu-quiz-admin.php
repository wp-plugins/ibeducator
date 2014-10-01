<?php

class IBEdu_Quiz_Admin {
	/**
	 * Initialize the quiz admin.
	 */
	public static function init() {
		// Enqueue scripts and stylesheets.
		add_action( 'admin_enqueue_scripts', array( 'IBEdu_Quiz_Admin', 'enqueue_scripts_styles' ), 9 );

		// Add meta box.
		add_action( 'add_meta_boxes', array( 'IBEdu_Quiz_Admin', 'add_meta_boxes' ) );

		// Indicate that the post has a quiz.
		add_action( 'save_post', array( 'IBEdu_Quiz_Admin', 'set_quiz' ), 10, 3 );

		// AJAX actions.
		add_action( 'wp_ajax_ibedu_quiz_question', array( 'IBEdu_Quiz_Admin', 'quiz_question' ) );
		add_action( 'wp_ajax_ibedu_sort_questions', array( 'IBEdu_Quiz_Admin', 'sort_questions' ) );
		add_action( 'wp_ajax_ibedu_quiz_grade', array( 'IBEdu_Quiz_Admin', 'quiz_grade' ) );
	}

	/**
	 * Enqueue scripts and stylesheets.
	 */
	public static function enqueue_scripts_styles() {
		$screen = get_current_screen();

		if ( 'post' == $screen->base && 'ibedu_lesson' == $screen->post_type ) {
			wp_enqueue_style( 'ibedu-quiz', IBEDUCATOR_PLUGIN_URL . 'admin/css/quiz.css', array(), '1.0' );
			wp_enqueue_script( 'ibedu-quiz', IBEDUCATOR_PLUGIN_URL . 'admin/js/quiz.js', array( 'jquery', 'underscore', 'backbone' ), '1.0' );
			wp_localize_script( 'ibedu-quiz', 'educatorQuizText', array(
				'confirm_delete' => __( 'Are you sure you want to delete this item?', 'ibeducator' )
			) );
		}
	}

	/**
	 * Add meta box.
	 */
	public static function add_meta_boxes() {
		// Quiz meta box
		add_meta_box(
			'ibedu_quiz',
			__( 'Quiz', 'ibeducator' ),
			array( 'IBEdu_Quiz_Admin', 'quiz_meta_box' ),
			'ibedu_lesson'
		);
	}

	/**
	 * Output quiz meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function quiz_meta_box( $post ) {
		include( IBEDUCATOR_PLUGIN_DIR . 'admin/meta-boxes/quiz.php' );
	}

	/**
	 * Save multiple choices to the database.
	 *
	 * @param int $question_id
	 * @param array $choices
	 * @return array Saved choices.
	 */
	protected static function save_question_choices( $question_id, $choices ) {
		$api = IBEdu_API::get_instance();
		$choice_ids = array();

		foreach ( $choices as $choice ) {
			if ( isset( $choice->choice_id ) && is_numeric( $choice->choice_id ) ) {
				$choice_ids[] = $choice->choice_id;
			}
		}

		// Delete choices that are not sent by the user.
		if ( ! empty( $choice_ids ) ) {
			global $wpdb;
			$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}ibedu_choices WHERE question_id=%d AND ID NOT IN (" . implode( ',', $choice_ids ) . ")", $question_id ));
		}

		// Add choices to the question.
		$current_choices = $api->get_question_choices( $question_id );
		$saved_choices = array();

		foreach ( $choices as $choice ) {
			$choice_data = array(
				'ID'          => ( ! isset( $choice->choice_id ) ) ? 0 : absint( $choice->choice_id ),
				'choice_text' => ( ! isset( $choice->choice_text ) ) ? '' : esc_html( $choice->choice_text ),
				'correct'     => ( ! isset( $choice->correct ) ) ? 0 : absint( $choice->correct ),
				'menu_order'  => ( ! isset( $choice->menu_order ) ) ? 0 : absint( $choice->menu_order ),
			);

			if ( $current_choices && isset( $current_choices[ $choice_data['ID'] ] ) ) {
				$api->update_choice( $choice_data['ID'], $choice_data );
			} else {
				$choice_data['question_id'] = $question_id;
				$choice_data['ID'] = $api->add_choice( $choice_data );
			}

			$choice_data['choice_id'] = $choice_data['ID'];

			$saved_choices[] = $choice_data;
		}

		return $saved_choices;
	}

	/**
	 * AJAX: process quiz question admin requests.
	 */
	public static function quiz_question() {
		$api = IBEdu_API::get_instance();

		switch ( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				$response = array(
					'status' => '',
					'errors' => array(),
					'id'     => 0
				);
				$input = json_decode( file_get_contents( 'php://input' ) );

				// Input given?
				if ( ! $input || ! isset( $input->lesson_id ) || ! is_numeric( $input->lesson_id ) ) {
					status_header( 400 );
					exit;
				}

				// Verify nonce.
				if ( ! isset( $input->_wpnonce ) || ! wp_verify_nonce( $input->_wpnonce, 'ibedu_quiz_' . $input->lesson_id ) ) {
					status_header( 403 );
					exit;
				}

				// Verify capabilities.
				if ( ! current_user_can( 'edit_ibedu_lesson', $input->lesson_id ) ) {
					exit;
				}

				// Verify nonce.
				if ( $input ) {
					$question = IBEdu_Question::get_instance();

					if ( isset( $input->lesson_id ) && is_numeric( $input->lesson_id ) ) {
						$question->lesson_id = $input->lesson_id;
					} else {
						$response['errors'][] = 'lesson_id';
					}

					if ( isset( $input->question ) && ! empty( $input->question ) ) {
						$question->question = $input->question;
					} else {
						$response['errors'][] = 'question';
					}

					if ( isset( $input->question_type ) && ! empty( $input->question_type ) ) {
						$question->question_type = $input->question_type;
					} else {
						$response['errors'][] = 'question_type';
					}

					if ( isset( $input->menu_order ) && is_numeric( $input->menu_order ) ) {
						$question->menu_order = $input->menu_order;
					} else {
						$response['errors'][] = 'order';
					}

					if ( ! count( $response['errors'] ) ) {
						$question->save();

						if ( $question->ID && isset( $input->choices ) && is_array( $input->choices ) ) {
							$response['choices'] = self::save_question_choices( $question->ID, $input->choices );
						}

						$response['status'] = 'success';
						$response['id'] = $question->ID;
					}
				}

				if ( count( $response['errors'] ) ) {
					$response['status'] = 'error';
					status_header( 400 );
				}

				echo json_encode( $response );
				break;

			case 'PUT':
				$response = array(
					'status' => '',
					'errors' => array()
				);
				$question_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
				$input = json_decode( file_get_contents( 'php://input' ) );

				// Input given?
				if ( ! $question_id || ! $input ) {
					status_header( 400 );
					exit;
				}

				$question = IBEdu_Question::get_instance( $question_id );

				// Question found?
				if ( ! $question->ID ) {
					status_header( 400 );
					exit;
				}

				// Verify nonce.
				if ( ! isset( $input->_wpnonce ) || ! wp_verify_nonce( $input->_wpnonce, 'ibedu_quiz_' . $question->lesson_id ) ) {
					status_header( 403 );
					exit;
				}

				// Verify capabilities.
				if ( ! current_user_can( 'edit_ibedu_lesson', $question->lesson_id ) ) {
					exit;
				}

				if ( isset( $input->question ) && ! empty( $input->question ) ) {
					$question->question = $input->question;
				} else {
					$response['errors'][] = 'question';
				}

				if ( ! count( $response['errors'] ) ) {
					$question->save();

					if ( isset( $input->choices ) && is_array( $input->choices ) ) {
						$response['choices'] = self::save_question_choices( $question->ID, $input->choices );
					}

					$response['status'] = 'success';
				}

				// Incorrect input?
				if ( count( $response['errors'] ) ) {
					$response['status'] = 'error';
					status_header( 400 );
				}

				echo json_encode( $response );
				break;

			case 'DELETE':
				$response = array(
					'status' => ''
				);
				$question_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

				// Input given?
				if ( ! $question_id ) {
					status_header( 400 );
					exit;
				}

				$question = IBEdu_Question::get_instance( $question_id );

				// Question found?
				if ( ! $question->ID ) {
					status_header( 400 );
					exit;
				}

				// Verify nonce.
				$input = json_decode( file_get_contents( 'php://input' ) );
				if ( ! isset( $input->_wpnonce ) || ! wp_verify_nonce( $input->_wpnonce, 'ibedu_quiz_' . $question->lesson_id ) ) {
					status_header( 403 );
					exit;
				}

				// Verify capabilities.
				if ( ! current_user_can( 'edit_ibedu_lesson', $question->lesson_id ) ) {
					exit;
				}

				if ( $question->ID ) {
					// First, delete question choices.
					$choices_deleted = true;
					if ( 'multiplechoice' == $question->question_type ) {
						$choices_deleted = $api->delete_choices( $question->ID );
					}

					if ( false !== $choices_deleted ) {
						$response['status'] = $question->delete() ? 'success' : 'error';
					}
				}

				echo json_encode( $response );
				break;
		}

		exit;
	}

	/**
	 * Indicate that the post has a quiz.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function set_quiz( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if ( 'ibedu_lesson' != $post->post_type || ! current_user_can( 'edit_ibedu_lesson', $post_id ) ) {
			return;
		}

		$has_quiz = 0;
		$questions = IBEdu_API::get_instance()->get_questions( array( 'lesson_id' => $post_id ) );

		if ( ! empty( $questions ) ) {
			$has_quiz = 1;
		}

		update_post_meta( $post_id, '_ibedu_quiz', $has_quiz );
	}

	/**
	 * AJAX: sort quiz questions.
	 */
	public static function sort_questions() {
		global $wpdb;

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;

		if ( ! $lesson_id || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_quiz_' . $lesson_id ) ) {
			exit;
		}

		// Verify capabilities.
		if ( ! ibedu_can_edit_lesson( get_current_user_id(), $lesson_id ) ) {
			exit;
		}
		
		$ids = isset( $_POST['question_id'] ) && is_array( $_POST['question_id'] ) ? $_POST['question_id'] : null;
		$order = isset( $_POST['order'] ) && is_array( $_POST['order'] ) ? $_POST['order'] : null;

		if ( $ids && $order ) {
			foreach ( $ids as $key => $question_id ) {
				if ( is_numeric( $question_id ) && isset( $order[ $key ] ) && is_numeric( $order[ $key ] ) ) {
					$wpdb->update(
						$wpdb->prefix . 'ibedu_questions',
						array( 'menu_order' => $order[ $key ] ),
						array( 'ID' => $question_id, 'lesson_id' => $lesson_id ),
						array( '%d' ),
						array( '%d', '%d' )
					);
				}
			}
		}

		exit;
	}

	/**
	 * AJAX: add grade for a quiz.
	 */
	public static function quiz_grade() {
		global $wpdb;
		$api = IBEdu_API::get_instance();
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_edit_progress_' . $entry_id ) ) {
			exit;
		}

		// Verify capabilities.
		if ( ! current_user_can( 'edit_ibedu_lesson', $lesson_id ) ) {
			exit;
		}

		$quiz_grade = $api->get_quiz_grade( $lesson_id, $entry_id );

		if ( ! $quiz_grade ) exit;

		$grade = isset( $_POST['grade'] ) ? floatval( $_POST['grade'] ) : 0;

		$api->update_quiz_grade( $quiz_grade->ID, array(
			'grade'           => $grade,
			'status'          => 'approved',
		) );

		echo json_encode( array( 'status' => 'success' ) );

		exit;
	}
}