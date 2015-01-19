<?php
/**
 * SHORTCODE: output student's courses.
 */
function ib_edu_student_courses( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-student-courses.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include( $template );
	return ob_get_clean();
}

/**
 * SHORTCODE: output payment page.
 */
function ib_edu_payment_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-payment.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include( $template );
	return ob_get_clean();
}

/**
 * SHORTCODE: output membership page.
 */
function ib_edu_memberships_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-memberships.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include( $template );
	return ob_get_clean();
}

/**
 * SHORTCODE: output membership page.
 */
function ib_edu_user_membership_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-user-membership.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include( $template );
	return ob_get_clean();
}

/**
 * SHORTCODE: output the user's payments page.
 */
function ib_edu_user_payments_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-user-payments.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include( $template );
	return ob_get_clean();
}

function ib_edu_register_shortcodes() {
	$shortcodes = array(
		'ibedu_student_courses' => 'ib_edu_student_courses',
		'ibedu_payment_page'    => 'ib_edu_payment_page',
		'memberships_page'      => 'ib_edu_memberships_page',
		'user_membership_page'  => 'ib_edu_user_membership_page',
		'user_payments_page'    => 'ib_edu_user_payments_page',
	);

	foreach ( $shortcodes as $key => $function ) {
		add_shortcode( apply_filters( 'ib_educator_shortcode_tag', $key ), $function );
	}
}
add_action( 'init', 'ib_edu_register_shortcodes' );