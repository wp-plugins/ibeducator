<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'ibedu_student_courses' ) ) :
/**
 * SHORTCODE: output student's courses.
 */
function ibedu_student_courses() {
	ob_start();
	include( IBEDUCATOR_PLUGIN_DIR . 'templates/shortcode-student-courses.php' );
	return ob_get_clean();
}
endif;
add_shortcode( 'ibedu_student_courses', 'ibedu_student_courses' );

if ( ! function_exists( 'ibedu_payment_page' ) ) :
/**
 * SHORTCODE: output payment page.
 */
function ibedu_payment_page() {
	ob_start();
	include( IBEDUCATOR_PLUGIN_DIR . 'templates/shortcode-payment.php' );
	return ob_get_clean();
}
endif;
add_shortcode( 'ibedu_payment_page', 'ibedu_payment_page' );