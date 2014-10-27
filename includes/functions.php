<?php

/**
 * Get breadcrumbs HTML.
 *
 * @param string $sep
 * @return string
 */
function ib_edu_breadcrumbs( $sep = ' &raquo; ' ) {
	$breadcrumbs = array();
	$is_lesson = is_singular( 'ibedu_lesson' );
	$is_course = is_singular( 'ibedu_course' );

	if ( $is_course || $is_lesson ) {
		$settings = get_option( 'ibedu_pages', array() );

		if ( isset( $settings['courses_page'] ) && is_numeric( $settings['courses_page'] ) ) {
			$page = get_post( $settings['courses_page'] );

			if ( $page ) {
				$breadcrumbs[] = '<a href="' . get_permalink( $page->ID ) . '">' . esc_html( $page->post_title ) . '</a>';
			}
		}
	}

	if ( $is_lesson ) {
		$course_id = get_post_meta( get_the_ID(), '_ibedu_course', true );

		if ( $course_id ) {
			$course = get_post( $course_id );

			if ( $course ) {
				$breadcrumbs[] = '<a href="' . get_permalink( $course->ID ) . '">' . esc_html( $course->post_title ) . '</a>';
			}
		}
	}

	$breadcrumbs[] = '<span>' . get_the_title() . '</span>';

	echo implode( $sep, $breadcrumbs );
}

/**
 * Get educator API url (can be used to process payment notifications from payment gateways).
 *
 * @param string $request
 * @return string
 */
function ib_edu_request_url( $request ) {
	$scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
	return esc_url_raw( add_query_arg( array( 'edu-request' => $request ), home_url( '/', $scheme ) ) );
}

/**
 * Get current currency.
 *
 * @return string
 */
function ib_edu_get_currency() {
	return apply_filters( 'ib_educator_currency', 'USD' );
}

/**
 * Get price of a course.
 *
 * @param int $course_id
 * @return float
 */
function ib_edu_get_course_price( $course_id ) {
	return floatval( get_post_meta( $course_id, '_ibedu_price', true ) );
}

/**
 * Format price.
 *
 * @param int|float $price
 * @return string
 */
function ib_edu_format_course_price( $price ) {
	$currency = ib_edu_get_currency();
	$formatted = '';

	switch ( $currency ) {
		case 'USD':
			if ( intval( $price ) != $price ) {
				$price = number_format( floatval( $price ), 2 );
			}

			$formatted = '$ ' . $price;
			break;
	}

	return apply_filters( 'ib_educator_format_price', $formatted, $currency, $price );
}

/**
 * Format grade.
 *
 * @param int|float $grade
 * @return string
 */
function ib_edu_format_grade( $grade ) {
	if ( ! is_int( $grade ) ) {
		$grade = number_format( floatval( $grade ), 2 );
	}

	return apply_filters( 'ib_educator_format_grade', $grade . '%', $grade );
}

/**
 * Get permalink endpoint URL.
 *
 * @param string $endpoint
 * @param string $value
 * @param string $url
 * @return string
 */
function ib_edu_get_endpoint_url( $endpoint, $value, $url ) {
	if ( get_option( 'permalink_structure' ) ) {
		// Pretty permalinks.
		$url = trailingslashit( $url ) . $endpoint . '/' . $value;
	} else {
		// Basic permalinks.
		$url = add_query_arg( $endpoint, $value, $url );
	}

	return $url;
}

/**
 * Get educator page id.
 *
 * @param string $page_name
 * @return int
 */
function ib_edu_page_id( $page_name ) {
	$pages = get_option( 'ibedu_pages', array() );

	if ( isset( $pages[ $page_name ] ) && is_numeric( $pages[ $page_name ] ) ) {
		return $pages[ $page_name ];
	}

	return 0;
}

/**
 * Get course access status message for a student.
 *
 * @param string $access_status
 * @return string
 */
function ib_edu_get_access_status_message( $access_status ) {
	$message = '';

	switch ( $access_status ) {
		case 'pending_entry':
			$message = '<p>' . __( 'Your registration is pending.', 'ibeducator' ) . '</p>';
			break;

		case 'pending_payment':
			$message = '<p>' . __( 'The payment for this course is pending.', 'ibeducator' ) . '</p>';
			break;

		case 'inprogress':
			$message = '<p>' . __( 'You are registered for this course.', 'ibeducator' ) . '</p>';
			break;
	}

	return $message;
}

/**
 * Get the course ID for a lesson.
 *
 * @param int $lesson_id
 * @return int
 */
function ib_edu_get_course_id( $lesson_id = null ) {
	// Is this function called inside the loop?
	if ( ! $lesson_id ) $lesson_id = get_the_ID();

	$course_id = get_post_meta( $lesson_id, '_ibedu_course', true );
	
	return is_numeric( $course_id ) ? $course_id : 0;
}

/**
 * Check if the current user can view the lesson.
 *
 * @param int $lesson_id
 * @return boolean
 */
function ib_edu_student_can_study( $lesson_id ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$course_id = get_post_meta( $lesson_id, '_ibedu_course', true );

	if ( $course_id ) {
		$access_status = IB_Educator::get_instance()->get_access_status( $course_id, get_current_user_id() );

		if ( in_array( $access_status, array( 'inprogress', 'course_complete' ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Pass the message from the back-end to a template.
 *
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
function ib_edu_message( $key, $value = null ) {
	static $messages = array();

	if ( is_null( $value ) ) {
		return isset( $messages[ $key ] ) ? $messages[ $key ] : null;
	}

	$messages[ $key ] = $value;
}

/**
 * Get available course difficulty levels.
 *
 * @since 1.0.0
 * @return array
 */
function ib_edu_get_difficulty_levels() {
	return array(
		'beginner'     => __( 'Beginner', 'ibeducator' ),
		'intermediate' => __( 'Intermediate', 'ibeducator' ),
		'advanced'     => __( 'Advanced', 'ibeducator' ),
	);
}

/**
 * Get course difficulty.
 *
 * @since 1.0.0
 * @param int $course_id
 * @return null|array
 */
function ib_edu_get_difficulty( $course_id ) {
	$difficulty = get_post_meta( $course_id, '_ib_educator_difficulty', true );
	
	if ( $difficulty ) {
		$levels = ib_edu_get_difficulty_levels();

		return array(
			'key'   => $difficulty,
			'label' => ( isset( $levels[ $difficulty ] ) ) ? $levels[ $difficulty ] : '',
		);
	}

	return null;
}

/**
 * Get database table names.
 *
 * @since 1.0.0
 * @param string $key
 * @return string
 */
function ib_edu_table_names() {
	global $wpdb;
	$prefix = $wpdb->prefix . 'ibedu_';
	
	return array(
		'payments'  => $prefix . 'payments',
		'entries'   => $prefix . 'entries',
		'questions' => $prefix . 'questions',
		'choices'   => $prefix . 'choices',
		'answers'   => $prefix . 'answers',
		'grades'    => $prefix . 'grades',
	);
}

/**
 * Can the current user edit a given lesson?
 *
 * @param int $lesson_id
 * @return boolean
 */
function ib_edu_user_can_edit_lesson( $lesson_id ) {
	if ( current_user_can( 'manage_educator' ) ) return true;

	$course_id = ib_edu_get_course_id( $lesson_id );

	if ( $course_id ) {
		$api = IB_Educator::get_instance();
		return in_array( $course_id, $api->get_lecturer_courses( get_current_user_id() ) );
	}

	return false;
}

/**
 * Trigger deprecated function error.
 *
 * @param string $function
 * @param string $version
 * @param string $replacement
 */
function _ib_edu_deprecated_function( $function, $version, $replacement = null ) {
	if ( WP_DEBUG && current_user_can( 'manage_options' ) ) {
		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf( __('%1$s is <strong>deprecated</strong> since Educator WP version %2$s! Use %3$s instead.'), $function, $version, $replacement ) );
		} else {
			trigger_error( sprintf( __('%1$s is <strong>deprecated</strong> since Educator WP version %2$s with no alternative available.'), $function, $version ) );
		}
	}
}