<?php

function ibedu_breadcrumbs( $sep = ' &raquo; ' ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_breadcrumbs' );
	return ib_edu_breadcrumbs( $sep );
}

function ibedu_api_url( $request ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_request_url' );
	return ib_edu_request_url( $request );
}

function ibedu_currency() {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_get_currency' );
	return ib_edu_get_currency();
}

function ibedu_get_price( $course_id ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_get_course_price' );
	return ib_edu_get_course_price( $course_id );
}

function ibedu_format_price( $price ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_format_course_price' );
	return ib_edu_format_course_price( $price );
}

function ibedu_format_grade( $grade ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_format_grade' );
	return ib_edu_format_grade( $grade );
}

function ibedu_endpoint_url( $endpoint, $value, $url ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_get_endpoint_url' );
	return ib_edu_get_endpoint_url( $endpoint, $value, $url );
}

function ibedu_page_id( $page_name ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_page_id' );
	return ib_edu_page_id( $page_name );
}

function ibedu_get_access_status_message( $access_status ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_get_access_status_message' );
	return ib_edu_get_access_status_message( $access_status );
}

function ibedu_lesson_course_id( $lesson_id = null ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_get_course_id' );
	return ib_edu_get_course_id( $lesson_id );
}

function ibedu_can_study( $lesson_id ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_student_can_study' );
	return ib_edu_student_can_study( $lesson_id );
}

function ibedu_message( $key, $value = null ) {
	_ib_edu_deprecated_function( __FUNCTION__, '0.9.0', 'ib_edu_message' );
	if ( is_null( $value ) ) return ib_edu_message( $key );
	ib_edu_message( $key, $value );
}