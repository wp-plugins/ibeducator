<?php

class IBEdu_Main {
	protected static $gateways = array();

	private function __construct() {}

	/**
	 * Initialize plugin.
	 */
	public static function init() {
		load_plugin_textdomain( 'ibeducator', false, IBEDUCATOR_PLUGIN_DIR . 'languages/' );

		self::init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private static function init_hooks() {
		add_action( 'init', array( 'IBEdu_Main', 'add_rewrite_endpoints' ) );
		add_action( 'init', array( 'IBEdu_Main', 'register_post_types' ) );
		add_action( 'init', array( 'IBEdu_Main', 'init_gateways' ) );

		// Process actions (e.g. enroll, payment).
		add_action( 'template_redirect', array( 'IBEdu_Main', 'process_actions' ) );

		// Override templates.
		add_filter( 'template_include', array( 'IBEdu_Main', 'override_templates' ) );

		// Verify permissions for various pages.
		add_action( 'template_redirect', array( 'IBEdu_Main', 'protect_private_pages' ) );

		// Add templating actions.
		add_action( 'ibedu_before_main_loop', array( 'IBEdu_Main', 'action_before_main_loop' ) );
		add_action( 'ibedu_after_main_loop', array( 'IBEdu_Main', 'action_after_main_loop' ) );
		add_action( 'ibedu_sidebar', array( 'IBEdu_Main', 'action_sidebar' ) );

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( 'IBEdu_Main', 'enqueue_scripts_styles' ) );
	}

	/**
	 * Initialize payment gateways.
	 */
	public static function init_gateways() {
		// Include abstract payment gateway class.
		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/gateways/abstract.ibedu-payment-gateway.php';

		$gateways = apply_filters( 'ibedu_payment_gateways', array(
			'paypal' => 'IBEdu_Gateway_Paypal',
			'cash'   => 'IBEdu_Gateway_Cash',
			'check'  => 'IBEdu_Gateway_Check',
		) );

		// Get the list of enabled gateways.
		$enabled_gateways = null;

		if ( ! is_admin() || ! current_user_can( 'manage_educator' ) ) {
			$gateways_options = get_option( 'ibedu_payment_gateways', array() );
			$enabled_gateways = array();

			foreach ( $gateways_options as $gateway_id => $options ) {
				if ( isset( $options[ 'enabled' ] ) && 1 == $options[ 'enabled' ] ) {
					$enabled_gateways[] = $gateway_id;
				}
			}
		}

		$loaded_gateway = null;
		$gateway_file = null;

		foreach ( $gateways as $gateway_id => $gateway ) {
			if ( $enabled_gateways && ! in_array( $gateway_id, $enabled_gateways ) ) {
				continue;
			}

			$gateway_file = IBEDUCATOR_PLUGIN_DIR . 'includes/gateways/' . strtolower( substr( $gateway, 14 ) ) . '/class.' . strtolower( str_replace( '_', '-', $gateway ) ) . '.php';

			if ( is_readable( $gateway_file ) ) {
				require( $gateway_file );

				$loaded_gateway = new $gateway();
				self::$gateways[ $loaded_gateway->get_id() ] = $loaded_gateway;
			}
		}
	}

	/**
	 * Get payment gateways objects.
	 *
	 * @return array
	 */
	public static function get_gateways() {
		return self::$gateways;
	}

	/**
	 * Add rewrite endpoints.
	 */
	public static function add_rewrite_endpoints() {
		add_rewrite_endpoint( 'edu-pay', EP_PAGES );
		add_rewrite_endpoint( 'edu-course', EP_PAGES );
		add_rewrite_endpoint( 'edu-thankyou', EP_PAGES );
		add_rewrite_endpoint( 'edu-action', EP_PAGES | EP_PERMALINK );
		add_rewrite_endpoint( 'edu-message', EP_PAGES | EP_PERMALINK );
	}

	/**
	 * Register post types.
	 */
	public static function register_post_types() {
		// Register post type for courses.
		register_post_type(
			'ibedu_course',
			array(
				'labels'              => array(
					'name'          => __( 'Courses', 'ibeducator' ),
					'singular_name' => __( 'Course', 'ibeducator' )
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => false,
				'capability_type'     => 'ibedu_course',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'courses' ),
				'query_var'           => 'course',
				'can_export'          => true
			)
		);

		// Register post type for lessons.
		register_post_type(
			'ibedu_lesson',
			array(
				'labels'              => array(
					'name'          => __( 'Lessons', 'ibeducator' ),
					'singular_name' => __( 'Lesson', 'ibeducator' )
				),
				'public'              => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => false,
				'capability_type'     => 'ibedu_lesson',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'lessons' ),
				'query_var'           => 'lesson',
				'can_export'          => true
			)
		);
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		if ( apply_filters( 'ibedu_default_styles', true ) ) {
			wp_enqueue_style( 'ibedu-base', IBEDUCATOR_PLUGIN_URL . 'css/ibedu-base.css' );
		}
	}

	/**
	 * Process actions.
	 */
	public static function process_actions() {
		$post_id = isset( $GLOBALS['wp_query']->post->ID ) ? $GLOBALS['wp_query']->post->ID : null;

		if ( ! $post_id ) {
			return;
		}

		$action = isset( $GLOBALS['wp_query']->query_vars['edu-action'] ) ? $GLOBALS['wp_query']->query_vars['edu-action'] : '';

		if ( empty( $action ) ) {
			return;
		}

		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/class.ibedu-actions.php';

		switch ( $action ) {
			case 'cancel-payment':
				IBEdu_Actions::cancel_payment();
				break;

			case 'submit-quiz':
				IBEdu_Actions::submit_quiz();
				break;

			case 'payment':
				IBEdu_Actions::payment();
				break;
		}
	}

	/**
	 * Override templates.
	 *
	 * @param string $template
	 * @return string
	 */
	public static function override_templates( $template ) {
		if ( is_post_type_archive( 'ibedu_course' ) ) {
			if ( false === strpos( $template, 'archive-ibedu_course.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/archive-ibedu_course.php';
			}
		} elseif ( is_singular( 'ibedu_course' ) ) {
			if ( false === strpos( $template, 'single-ibedu_course.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/single-ibedu_course.php';
			}
		} elseif ( is_singular( 'ibedu_lesson' ) ) {
			if ( false === strpos( $template, 'single-ibedu_lesson.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/single-ibedu_lesson.php';
			}
		} elseif ( is_post_type_archive( 'ibedu_lesson' ) ) {
			if ( false === strpos( $template, 'archive-ibedu_lesson.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/archive-ibedu_lesson.php';
			}
		}

		return $template;
	}

	/**
	 * Protect private pages.
	 */
	public static function protect_private_pages() {
		// User must be logged in to view a private pages (e.g. payment, my courses).
		$private_pages = array();

		// Student courses page.
		$student_courses_page = ibedu_page_id( 'student_courses_page' );

		if ( $student_courses_page > 0 ) {
			$private_pages[] = $student_courses_page;
		}

		if ( ! empty( $private_pages ) && is_page( $private_pages ) && ! is_user_logged_in() ) {
			wp_redirect( wp_login_url( get_permalink( $GLOBALS['wp_query']->post->ID ) ) );
			exit;
		}
	}

	/**
	 * Plugin activation hook.
	 */
	public static function plugin_activation() {
		require_once( IBEDUCATOR_PLUGIN_DIR . 'includes/class.ibedu-install.php' );
		$ibedu_install = new IBEdu_Install();
		$ibedu_install->install();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function plugin_deactivation() {
		require_once( IBEDUCATOR_PLUGIN_DIR . 'includes/class.ibedu-install.php' );
		$ibedu_install = new IBEdu_Install();
		$ibedu_install->deactivate();
	}

	/**
	 * Modify main loop template for default themes.
	 */
	public static function action_before_main_loop( $where = '' ) {
		$template = get_template();

		switch ( $template ) {
			case 'twentyfourteen':
				echo '<div id="main-content" class="main-content"><div id="primary" class="content-area"><div id="content" class="site-content" role="main">';

				if ( 'archive' != $where ) echo '<div class="ibedu-twentyfourteen">';

				break;
		}
	}

	/**
	 * Modify main loop template for default themes.
	 */
	public static function action_after_main_loop( $where = '' ) {
		$template = get_template();

		switch ( $template ) {
			case 'twentyfourteen':
				echo '</div></div></div>';

				if ( 'archive' != $where ) echo '</div>';

				break;
		}
	}

	/**
	 * Modify main loop sidebar for default themes.
	 */
	public static function action_sidebar() {
		get_sidebar();
	}
}