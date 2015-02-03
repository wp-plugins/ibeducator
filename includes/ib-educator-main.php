<?php

class IB_Educator_Main {
	/**
	 * @var array
	 */
	protected static $gateways = array();

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'init_gateways' ) );
		add_action( 'init', array( __CLASS__, 'add_rewrite_endpoints' ), 8 ); // Run before the plugin update.
		add_action( 'template_redirect', array( __CLASS__, 'process_actions' ) );
		add_filter( 'template_include', array( __CLASS__, 'override_templates' ) );
		add_action( 'template_redirect', array( __CLASS__, 'protect_private_pages' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ) );
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'add_menu_classes' ) );

		// Register form.
		add_action( 'ib_educator_register_form', array( __CLASS__, 'register_form' ) );
		add_filter( 'ib_educator_register_form_validate', array( __CLASS__, 'register_form_validate' ) );
		add_filter( 'ib_educator_register_user_data', array( __CLASS__, 'register_user_data' ) );

		// Add templating actions.
		add_action( 'ib_educator_before_main_loop', array( __CLASS__, 'action_before_main_loop' ) );
		add_action( 'ib_educator_after_main_loop', array( __CLASS__, 'action_after_main_loop' ) );
		add_action( 'ib_educator_sidebar', array( __CLASS__, 'action_sidebar' ) );
		add_action( 'ib_educator_before_course_content', array( __CLASS__, 'before_course_content' ) );
	}

	/**
	 * Get the payment gateways objects.
	 *
	 * @return array
	 */
	public static function get_gateways() {
		return self::$gateways;
	}

	/**
	 * Plugin activation hook.
	 */
	public static function plugin_activation() {
		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-install.php';
		$install = new IB_Educator_Install();
		$install->activate();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function plugin_deactivation() {
		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-install.php';
		$install = new IB_Educator_Install();
		$install->deactivate();
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
			'paypal'        => 'IB_Educator_Gateway_Paypal',
			'cash'          => 'IB_Educator_Gateway_Cash',
			'check'         => 'IB_Educator_Gateway_Check',
			'bank-transfer' => 'IB_Educator_Gateway_Bank_Transfer',
			'free'          => 'IB_Educator_Gateway_Free',
		) );

		// Get the list of enabled gateways.
		$enabled_gateways = null;

		if ( ! is_admin() || ! current_user_can( 'manage_educator' ) ) {
			$gateways_options = get_option( 'ibedu_payment_gateways', array() );
			$enabled_gateways = array(
				'free',
			);

			foreach ( $gateways_options as $gateway_id => $options ) {
				if ( isset( $options[ 'enabled' ] ) && 1 == $options[ 'enabled' ] ) {
					$enabled_gateways[] = $gateway_id;
				}
			}

			$enabled_gateways = apply_filters( 'ib_educator_enabled_gateways', $enabled_gateways );
		}

		$loaded_gateway = null;
		$gateway_file = null;

		foreach ( $gateways as $gateway_id => $gateway ) {
			if ( null !== $enabled_gateways && ! in_array( $gateway_id, $enabled_gateways ) ) {
				continue;
			}

			$gateway_file = IBEDUCATOR_PLUGIN_DIR . 'includes/gateways/'
						  . strtolower( str_replace( '_', '-', substr( $gateway, 20 ) ) ) . '/'
						  . strtolower( str_replace( '_', '-', $gateway ) ) . '.php';

			if ( is_readable( $gateway_file ) ) {
				require_once $gateway_file;

				$loaded_gateway = new $gateway();
				self::$gateways[ $loaded_gateway->get_id() ] = $loaded_gateway;
			}
		}
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
		add_rewrite_endpoint( 'edu-membership', EP_PAGES );
	}

	/**
	 * Process actions.
	 */
	public static function process_actions() {
		if ( ! isset( $GLOBALS['wp_query']->post )
			|| ! isset( $GLOBALS['wp_query']->post->ID )
			|| ! isset( $GLOBALS['wp_query']->query_vars['edu-action'] ) ) {
			return;
		}

		$post_id = $GLOBALS['wp_query']->post->ID;
		$action = $GLOBALS['wp_query']->query_vars['edu-action'];

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

			case 'join':
				IB_Educator_Actions::join();
				break;

			case 'resume-entry':
				IB_Educator_Actions::resume_entry();
				break;

			case 'pause-membership':
				IB_Educator_Actions::pause_membership();
				break;

			case 'resume-membership':
				IB_Educator_Actions::resume_membership();
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
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		if ( apply_filters( 'ib_educator_stylesheet', true ) ) {
			wp_enqueue_style( 'ib-educator-base', IBEDUCATOR_PLUGIN_URL . 'css/base.css' );

			switch ( get_template() ) {
				case 'twentyfourteen':
					wp_enqueue_style( 'ib-educator-twentyfourteen', IBEDUCATOR_PLUGIN_URL . 'css/twentyfourteen.css' );
					break;

				case 'twentyfifteen':
					wp_enqueue_style( 'ib-educator-twentyfifteen', IBEDUCATOR_PLUGIN_URL . 'css/twentyfifteen.css' );
					break;
			}
		}
	}

	/**
	 * Add classes to menu items.
	 *
	 * @param array $items
	 * @return array
	 */
	public static function add_menu_classes( $items ) {
		$courses_url = get_post_type_archive_link( 'ib_educator_course' );

		foreach ( $items as $key => $item ) {
			if ( $item->url == $courses_url ) {
				if ( is_singular( 'ib_educator_course' )
					|| is_post_type_archive( 'ib_educator_course' )
					|| is_tax( 'ib_educator_category' ) ) {
					$items[ $key ]->classes[] = 'current-menu-item';
				}

				break;
			}
		}

		return $items;
	}

	/**
	 * Output default user register form.
	 *
	 * @param array $error_codes
	 */
	public static function register_form( $error_codes ) {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			return;
		}

		foreach ( $error_codes as $error_code ) {
			switch ( $error_code ) {
				case 'account_info_empty':
					$errors['account_username'] = true;
					$errors['account_email'] = true;
					break;

				case 'invalid_username':
				case 'existing_user_login':
					$errors['account_username'] = true;
					break;

				case 'invalid_email':
				case 'existing_user_email':
					$errors['account_email'] = true;
					break;
			}
		}
		?>
		<fieldset>
			<legend><?php _e( 'Create an Account', 'ibeducator' ); ?></legend>

			<div class="ib-edu-form-field<?php if ( isset( $errors['account_username'] ) ) echo ' error'; ?>">
				<label for="ib-edu-username"><?php _e( 'Username', 'ibeducator' ); ?> <span class="required">*</span></label>
				<div class="ib-edu-form-control">
					<input type="text" id="ib-edu-username" name="account_username" value="<?php if ( ! empty( $_POST['account_username'] ) ) echo esc_attr( $_POST['account_username'] ); ?>">
				</div>
			</div>

			<div class="ib-edu-form-field<?php if ( isset( $errors['account_email'] ) ) echo ' error'; ?>">
				<label for="ib-edu-email"><?php _e( 'Email', 'ibeducator' ); ?> <span class="required">*</span></label>
				<div class="ib-edu-form-control">
					<input type="text" id="ib-edu-email" name="account_email" value="<?php if ( ! empty( $_POST['account_email'] ) ) echo esc_attr( $_POST['account_email'] ); ?>">
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Validate the default user registration form.
	 *
	 * @param WP_Error $errors
	 * @return WP_Error
	 */
	public static function register_form_validate( $errors ) {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			return $errors;
		}

		if ( ! empty( $_POST['account_username'] ) ) {
			if ( ! validate_username( $_POST['account_username'] ) ) {
				$errors->add( 'invalid_username', __( 'Please check if you entered your username correctly.', 'ibeducator' ) );
			}
		} else {
			$errors->add( 'account_info_empty', __( 'Please enter your username and email.', 'ibeducator' ) );
		}

		// Get account username.
		if ( ! empty( $_POST['account_email'] ) ) {
			if ( ! is_email( $_POST['account_email'] ) ) {
				$errors->add( 'invalid_email', __( 'Please check if you entered your email correctly.', 'ibeducator' ) );
			}
		} elseif ( ! $errors->get_error_message( 'account_info_empty' ) ) {
			$errors->add( 'account_info_empty', __( 'Please enter your username and email.', 'ibeducator' ) );
		}

		return $errors;
	}

	/**
	 * Filter the default user registration data.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function register_user_data( $data ) {
		if ( ! empty( $_POST['account_username'] ) ) {
			$data['user_login'] = $_POST['account_username'];
		}

		if ( ! empty( $_POST['account_email'] ) ) {
			$data['user_email'] = $_POST['account_email'];
		}

		$data['user_pass'] = wp_generate_password( 12, false );

		return $data;
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

			case 'twentyfifteen':
				echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';

				if ( 'archive' != $where ) echo '<div class="ib-edu-twentyfifteen">';

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

			case 'twentyfifteen':
				echo '</main></div>';

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