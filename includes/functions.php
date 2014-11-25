<?php

/**
 * Get Educator's settings.
 *
 * @return array
 */
function ib_edu_get_settings() {
	return get_option( 'ib_educator_settings', array() );
}

/**
 * Get breadcrumbs HTML.
 *
 * @param string $sep
 * @return string
 */
function ib_edu_breadcrumbs( $sep = ' &raquo; ' ) {
	$breadcrumbs = array();
	$is_lesson = is_singular( 'ib_educator_lesson' );
	$is_course = is_singular( 'ib_educator_course' );

	if ( $is_course || $is_lesson ) {
		$student_courses_page_id = ib_edu_page_id( 'student_courses' );

		if ( $student_courses_page_id ) {
			$page = get_post( $student_courses_page_id );

			if ( $page ) {
				$breadcrumbs[] = '<a href="' . get_permalink( $page->ID ) . '">' . esc_html( $page->post_title ) . '</a>';
			}
		}
	}

	if ( $is_lesson ) {
		$course_id = ib_edu_get_course_id( get_the_ID() );

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
 * Get price of a course.
 *
 * @param int $course_id
 * @return float
 */
function ib_edu_get_course_price( $course_id ) {
	return floatval( get_post_meta( $course_id, '_ibedu_price', true ) );
}

/**
 * Get the list of available currencies.
 *
 * @return array
 */
function ib_edu_get_currencies() {
	return apply_filters( 'ib_educator_currencies', array(
		'AUD' => __( 'Australian Dollars', 'ibeducator' ),
		'BRL' => __( 'Brazilian Real', 'ibeducator' ),
		'CAD' => __( 'Canadian Dollars', 'ibeducator' ),
		'CNY' => __( 'Chinese Yuan', 'ibeducator' ),
		'CZK' => __( 'Czech Koruna', 'ibeducator' ),
		'DKK' => __( 'Danish Krone', 'ibeducator' ),
		'EUR' => __( 'Euros', 'ibeducator' ),
		'HKD' => __( 'Hong Kong Dollar', 'ibeducator' ),
		'HUF' => __( 'Hungarian Forint', 'ibeducator' ),
		'INR' => __( 'Indian Rupee', 'ibeducator' ),
		'IRR' => __( 'Iranian Rial', 'ibeducator' ),
		'ILS' => __( 'Israeli Shekel', 'ibeducator' ),
		'JPY' => __( 'Japanese Yen', 'ibeducator' ),
		'MYR' => __( 'Malaysian Ringgits', 'ibeducator' ),
		'MXN' => __( 'Mexican Peso', 'ibeducator' ),
		'NZD' => __( 'New Zealand Dollar', 'ibeducator' ),
		'NOK' => __( 'Norwegian Krone', 'ibeducator' ),
		'PHP' => __( 'Philippine Pesos', 'ibeducator' ),
		'PLN' => __( 'Polish Zloty', 'ibeducator' ),
		'GBP' => __( 'Pounds Sterling', 'ibeducator' ),
		'RUB' => __( 'Russian Rubles', 'ibeducator' ),
		'SGD' => __( 'Singapore Dollar', 'ibeducator' ),
		'SEK' => __( 'Swedish Krona', 'ibeducator' ),
		'CHF' => __( 'Swiss Franc', 'ibeducator' ),
		'TWD' => __( 'Taiwan New Dollars', 'ibeducator' ),
		'THB' => __( 'Thai Baht', 'ibeducator' ),
		'TRY' => __( 'Turkish Lira', 'ibeducator' ),
		'USD' => __( 'US Dollars', 'ibeducator' ),
		'UAH' => __( 'Ukrainian Hryvnia', 'ibeducator' ),
	) );
}
/*$currencies = ib_edu_get_currencies();
asort( $currencies );
foreach ( $currencies as $currency => $name ) {
	echo "case '$currency':\n";
}*/

/**
 * Get current currency.
 *
 * @return string
 */
function ib_edu_get_currency() {
	$settings = ib_edu_get_settings();

	if ( isset( $settings['currency'] ) ) {
		$currency = $settings['currency'];
	} else {
		$currency = 'USD';
	}

	return apply_filters( 'ib_educator_currency', $currency );
}

/**
 * Get currency symbol.
 *
 * @param string $currency
 * @return string
 */
function ib_edu_get_currency_symbol( $currency ) {
	switch ( $currency ) {
		case 'USD':
		case 'AUD':
		case 'CAD':
		case 'HKD':
		case 'MXN':
		case 'NZD':
		case 'SGD':
			$cs = "&#36;";
			break;
		case 'BRL': $cs = "&#82;&#36;"; break;
		case 'CNY': $cs = "&#165;"; break;
		case 'CZK': $cs = "&#75;&#269;"; break;
		case 'DKK': $cs = "&#107;&#114;"; break;
		case 'EUR': $cs = "&euro;"; break;
		case 'HUF': $cs = "&#70;&#116;"; break;
		case 'INR': $cs = "&#8377;"; break;
		case 'IRR': $cs = "&#65020;"; break;
		case 'ILS': $cs = "&#8362;"; break;
		case 'JPY': $cs = "&yen;"; break;
		case 'MYR': $cs = "&#82;&#77;"; break;
		case 'NOK': $cs = "&#107;&#114;"; break;
		case 'PHP': $cs = "&#8369;"; break;
		case 'PLN': $cs = "&#122;&#322;"; break;
		case 'GBP': $cs = "&pound;"; break;
		case 'RUB': $cs = "&#1088;&#1091;&#1073;."; break;
		case 'SEK': $cs = "&#107;&#114;"; break;
		case 'CHF': $cs = "&#67;&#72;&#70;"; break;
		case 'TWD': $cs = "&#78;&#84;&#36;"; break;
		case 'THB': $cs = "&#3647;"; break;
		case 'TRY': $cs = "&#84;&#76;"; break;
		case 'UAH': $cs = "&#8372;"; break;
		default: $cs = $currency;
	}

	return apply_filters( 'ib_educator_currency_symbol', $cs, $currency );
}

/**
 * Format price.
 *
 * @param int|float $price
 * @return string
 */
function ib_edu_format_course_price( $price ) {
	$settings = ib_edu_get_settings();
	$currency = ib_edu_get_currency();
	$currency_symbol = ib_edu_get_currency_symbol( $currency );
	$decimal_point = ! empty( $settings['decimal_point'] ) ? esc_html( $settings['decimal_point'] ) : '.';
	$thousands_sep = ! empty( $settings['thousands_sep'] ) ? esc_html( $settings['thousands_sep'] ) : ',';
	$formatted = number_format( $price, 2, $decimal_point, $thousands_sep );
	$formatted = ib_edu_strip_zeroes( $formatted, $decimal_point );

	if ( isset( $settings['currency_position'] ) && 'after' == $settings['currency_position'] ) {
		$formatted = "$formatted $currency_symbol";
	} else {
		$formatted = "$currency_symbol $formatted";
	}

	return apply_filters( 'ib_educator_format_price', $formatted, $currency, $price );
}

/**
 * Remove trailing zeroes from a number.
 *
 * @param mixed $number
 * @param string $decimal_point
 * @return string
 */
function ib_edu_strip_zeroes( $number, $decimal_point ) {
	return preg_replace( '/' . preg_quote( $decimal_point, '/' ) . '0+$/', '', $number );
}

/**
 * Format grade.
 *
 * @param int|float $grade
 * @return string
 */
function ib_edu_format_grade( $grade ) {
	$formatted = (float) round( $grade, 2 );

	return apply_filters( 'ib_educator_format_grade', $formatted . '%', $grade );
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
	$settings = get_option( 'ib_educator_settings', array() );
	$page_name .= '_page';

	if ( isset( $settings[ $page_name ] ) && is_numeric( $settings[ $page_name ] ) ) {
		return $settings[ $page_name ];
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

	$course_id = ib_edu_get_course_id( $lesson_id );

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
	$prefix = $wpdb->prefix . 'ibeducator_';
	
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
 * Send email notification.
 *
 * @param string $to
 * @param string $template
 * @param array $subject_vars
 * @param array $template_vars
 */
function ib_edu_send_notification( $to, $template, $subject_vars, $template_vars ) {
	require_once IBEDUCATOR_PLUGIN_DIR . '/includes/ib-educator-email.php';

	$email = new IB_Educator_Email();
	$email->set_template( $template );
	$email->parse_subject( $subject_vars );
	$email->parse_template( $template_vars );
	$email->add_recipient( $to );
	$email->send();
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