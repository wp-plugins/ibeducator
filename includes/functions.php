<?php

/**
 * Get breadcrumbs HTML.
 *
 * @param string $sep
 * @return string
 */
function ibedu_breadcrumbs( $sep = ' &raquo; ' ) {
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
function ibedu_api_url( $request ) {
	$scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
	return esc_url_raw( add_query_arg( array( 'ibedu-request' => $request ), home_url( '/', $scheme ) ) );
}

/**
 * Get current currency.
 *
 * @return string
 */
function ibedu_currency() {
	return apply_filters( 'ibedu_currency', 'USD' );
}

/**
 * Format price.
 *
 * @param int|float $price
 * @return string
 */
function ibedu_format_price( $price ) {
	$currency = ibedu_currency();
	$formatted = '';

	switch ( $currency ) {
		case 'USD':
			$formatted = '$ ' . number_format( floatval( $price ), 2 );
			break;
	}

	return apply_filters( 'ibedu_format_price', $formatted, $currency );
}

/**
 * Format grade.
 *
 * @param int|float $grade
 * @return string
 */
function ibedu_format_grade( $grade ) {
	if ( ! is_int( $grade ) ) {
		$grade = number_format( floatval( $grade ), 2 );
	}

	return apply_filters( 'ibedu_format_grade', $grade . '%', $grade );
}

/**
 * Get permalink endpoint URL.
 *
 * @param string $endpoint
 * @param string $value
 * @param string $url
 * @return string
 */
function ibedu_endpoint_url( $endpoint, $value, $url ) {
	if ( get_option( 'permalink_structure' ) ) {
		// Pretty permalinks
		$url = trailingslashit( $url ) . $endpoint . '/' . $value;
	} else {
		// Basic permalinks
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
function ibedu_page_id( $page_name ) {
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
function ibedu_get_access_status_message( $access_status ) {
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
function ibedu_lesson_course_id( $lesson_id = null ) {
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
function ibedu_can_study( $lesson_id ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	$course_id = get_post_meta( $lesson_id, '_ibedu_course', true );

	if ( $course_id ) {
		$access_status = IBEdu_API::get_instance()->get_access_status( $course_id, get_current_user_id() );

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
function ibedu_message( $key, $value = null ) {
	static $messages = array();

	if ( null === $value ) {
		return isset( $messages[ $key ] ) ? $messages[ $key ] : null;
	}

	$messages[ $key ] = $value;
}