<?php

class IBEdu_Request {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( 'IBEdu_Request', 'add_rewrite_endpoint' ) );
		add_action( 'parse_request', array( 'IBEdu_Request', 'process_request' ) );
	}

	/**
	 * Add rewrite endpoint to process requests.
	 */
	public static function add_rewrite_endpoint() {
		add_rewrite_endpoint( 'ibedu-request', EP_ROOT );
	}

	/**
	 * Process request.
	 *
	 * @param WP $wp
	 */
	public static function process_request( $wp ) {
		if ( ! isset( $wp->query_vars['ibedu-request'] ) ) {
			return;
		}

		$request = $wp->query_vars['ibedu-request'];

		do_action( 'ibedu_process_request', $request );

		exit;
	}
}