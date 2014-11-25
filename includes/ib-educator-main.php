<?php

class IB_Educator_Main {
	protected static $gateways = array();

	private function __construct() {}

	/**
	 * Initialize plugin.
	 */
	public static function init() {
		self::init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private static function init_hooks() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_endpoints' ) );
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'init_gateways' ) );

		// Plugin textdomain.
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );

		// Process actions (e.g. enroll, payment).
		add_action( 'template_redirect', array( __CLASS__, 'process_actions' ) );

		// Override templates.
		add_filter( 'template_include', array( __CLASS__, 'override_templates' ) );

		// Verify permissions for various pages.
		add_action( 'template_redirect', array( __CLASS__, 'protect_private_pages' ) );

		// Add templating actions.
		add_action( 'ib_educator_before_main_loop', array( __CLASS__, 'action_before_main_loop' ) );
		add_action( 'ib_educator_after_main_loop', array( __CLASS__, 'action_after_main_loop' ) );
		add_action( 'ib_educator_sidebar', array( __CLASS__, 'action_sidebar' ) );
		add_action( 'ib_educator_before_course_content', array( __CLASS__, 'before_course_content' ) );

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ) );
	}

	/**
	 * Load plugin textdomain.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'ibeducator', false, IBEDUCATOR_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Initialize payment gateways.
	 */
	public static function init_gateways() {
		// Include abstract payment gateway class.
		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/gateways/ib-educator-payment-gateway.php';

		$gateways = apply_filters( 'ib_educator_payment_gateways', array(
			'paypal' => 'IB_Educator_Gateway_Paypal',
			'cash'   => 'IB_Educator_Gateway_Cash',
			'check'  => 'IB_Educator_Gateway_Check',
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

			$gateway_file = IBEDUCATOR_PLUGIN_DIR . 'includes/gateways/' . strtolower( substr( $gateway, 20 ) ) . '/' . strtolower( str_replace( '_', '-', $gateway ) ) . '.php';

			if ( is_readable( $gateway_file ) ) {
				require_once $gateway_file;

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
		add_rewrite_endpoint( 'edu-request', EP_ROOT );
	}

	/**
	 * Register post types.
	 */
	public static function register_post_types() {
		// Register post type for courses.
		register_post_type(
			'ib_educator_course',
			array(
				'labels'              => array(
					'name'          => __( 'Courses', 'ibeducator' ),
					'singular_name' => __( 'Course', 'ibeducator' ),
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'capability_type'     => 'ib_educator_course',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'courses' ),
				'query_var'           => 'course',
				'can_export'          => true,
			)
		);

		// Register post type for lessons.
		register_post_type(
			'ib_educator_lesson',
			array(
				'labels'              => array(
					'name'          => __( 'Lessons', 'ibeducator' ),
					'singular_name' => __( 'Lesson', 'ibeducator' ),
				),
				'public'              => true,
				'exclude_from_search' => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'capability_type'     => 'ib_educator_lesson',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => true,
				'rewrite'             => array( 'slug' => 'lessons' ),
				'query_var'           => 'lesson',
				'can_export'          => true,
			)
		);

		// Register course taxonomy.
		register_taxonomy( 'ib_educator_category', 'ib_educator_course', array(
			'label'             => __( 'Course Categories', 'ibeducator' ),
			'public'            => true,
			'show_ui'           => true,
			'show_in_nav_menus' => true,
			'hierarchical'      => true,
			'rewrite'           => array( 'slug' => 'course-category' ),
			'capabilities'      => array(
				'assign_terms' => 'edit_ib_educator_courses',
			),
		) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		$default_stylesheet = apply_filters( 'ib_educator_stylesheet', true );

		if ( $default_stylesheet ) {
			wp_enqueue_style( 'ib-educator-base', IBEDUCATOR_PLUGIN_URL . 'css/base.css' );
		}
	}

	/**
	 * Process actions.
	 */
	public static function process_actions() {
		if ( ! isset( $GLOBALS['wp_query']->post ) ) {
			return;
		}

		$post_id = isset( $GLOBALS['wp_query']->post->ID ) ? $GLOBALS['wp_query']->post->ID : null;

		if ( ! $post_id ) {
			return;
		}

		$action = isset( $GLOBALS['wp_query']->query_vars['edu-action'] ) ? $GLOBALS['wp_query']->query_vars['edu-action'] : '';

		if ( empty( $action ) ) {
			return;
		}

		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-actions.php';

		switch ( $action ) {
			case 'cancel-payment':
				IB_Educator_Actions::cancel_payment();
				break;

			case 'submit-quiz':
				IB_Educator_Actions::submit_quiz();
				break;

			case 'payment':
				IB_Educator_Actions::payment();
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
		if ( is_post_type_archive( 'ib_educator_course' ) ) {
			if ( false === strpos( $template, 'archive-ib_educator_course.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/archive-ib_educator_course.php';
			}
		} elseif ( is_singular( 'ib_educator_course' ) ) {
			if ( false === strpos( $template, 'single-ib_educator_course.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/single-ib_educator_course.php';
			}
		} elseif ( is_singular( 'ib_educator_lesson' ) ) {
			if ( false === strpos( $template, 'single-ib_educator_lesson.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/single-ib_educator_lesson.php';
			}
		} elseif ( is_post_type_archive( 'ib_educator_lesson' ) ) {
			if ( false === strpos( $template, 'archive-ib_educator_lesson.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/archive-ib_educator_lesson.php';
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
		$student_courses_page = ib_edu_page_id( 'student_courses_page' );

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
		require_once( IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-install.php' );
		$install = new IB_Educator_Install();
		$install->activate();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function plugin_deactivation() {
		require_once( IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-install.php' );
		$install = new IB_Educator_Install();
		$install->deactivate();
	}

	/**
	 * Action hook: before main loop.
	 */
	public static function action_before_main_loop( $where = '' ) {
		$template = get_template();

		switch ( $template ) {
			case 'twentyfourteen':
				echo '<div id="main-content" class="main-content"><div id="primary" class="content-area"><div id="content" class="site-content" role="main">';

				if ( 'archive' != $where ) echo '<div class="ib-edu-twentyfourteen">';

				break;
		}
	}

	/**
	 * Action hook: after main loop.
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
	 * Action hook: main loop sidebar.
	 */
	public static function action_sidebar() {
		get_sidebar();
	}

	/**
	 * Action hook: before course content.
	 */
	public static function before_course_content() {
		// Output course difficulty.
		$difficulty = ib_edu_get_difficulty( get_the_ID() );

		if ( $difficulty ) {
			echo '<div class="ib-edu-course-difficulty"><span class="label">' . __( 'Difficulty:', 'ibeducator' ) . '</span>' . esc_html( $difficulty['label'] ) . '</div>';
		}

		// Output course categories.
		$categories = get_the_term_list( get_the_ID(), 'ib_educator_category', '', __( ', ', 'ibeducator' ) );

		if ( $categories ) {
			echo '<div class="ib-edu-course-categories"><span class="label">' . __( 'Categories:', 'ibeducator' ) . '</span>' . $categories . '</div>';
		}
	}
}