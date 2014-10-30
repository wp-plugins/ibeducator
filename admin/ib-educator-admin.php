<?php

class IB_Educator_Admin {
	/**
	 * Initialize admin.
	 */
	public static function init() {
		// Enqueue scripts and stylesheets.
		add_action( 'admin_enqueue_scripts', array( 'IB_Educator_Admin', 'enqueue_scripts_styles' ), 9 );

		// Order lessons by menu_order in lessons list admin screen.
		add_action( 'pre_get_posts', array( 'IB_Educator_Admin', 'lessons_menu_order' ) );

		// Add the course column name to lessons list admin screen.		
		add_filter( 'manage_ib_educator_lesson_posts_columns', array( 'IB_Educator_Admin', 'lessons_columns' ) );

		// Add the course column content to lessons list admin screen.
		add_filter( 'manage_ib_educator_lesson_posts_custom_column', array( 'IB_Educator_Admin', 'lessons_column_output' ), 10, 2 );

		// Add filters controls to the lessons list admin screen.
		add_filter( 'restrict_manage_posts', array( 'IB_Educator_Admin', 'lessons_add_filters' ) );

		// Filter lessons list admin screen (by course).
		add_filter( 'pre_get_posts', array( 'IB_Educator_Admin', 'lessons_parse_filters' ) );

		// Admin menu.
		add_action( 'admin_menu', array( 'IB_Educator_Admin', 'admin_menu' ), 9 );

		// Settings.
		add_action( 'admin_init', array( 'IB_Educator_Admin', 'setup_settings' ) );
		add_action( 'admin_init', array( 'IB_Educator_Admin', 'admin_actions' ) );

		// AJAX actions.
		add_action( 'wp_ajax_ib_educator_delete_payment', array( 'IB_Educator_Admin', 'admin_payments_delete' ) );
		add_action( 'wp_ajax_ib_educator_delete_entry', array( 'IB_Educator_Admin', 'admin_entries_delete' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		//$screen = get_current_screen();
		wp_enqueue_style( 'ib-educator-admin', IBEDUCATOR_PLUGIN_URL . 'admin/css/admin.css', array(), '1.0' );
	}

	/**
	 * Modify lessons order in the admin panel.
	 *
	 * @param WP_Query $query
	 */
	public static function lessons_menu_order( $query ) {
		if ( $query->is_main_query() ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}

	/**
	 * Add course column to the lessons list in admin.
	 *
	 * @param array $collumns
	 *
	 * @return array 
	 */
	public static function lessons_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' == $key ) {
				$new_columns['course'] = __( 'Course', 'ibeducator' );
			}
		}

		return $new_columns;
	}

	/**
	 * Output column content to the lessons list in admin.
	 *
	 * @param string $column_name
	 * @param int $post_id
	 */
	public static function lessons_column_output( $column_name, $post_id ) {
		if ( 'course' == $column_name ) {
			$course_id = ib_edu_get_course_id( $post_id );

			if ( $course_id && ( $course = get_post( $course_id ) ) ) {
				echo '<a href="' . esc_url( get_permalink( $course->ID ) ) . '" target="_blank">' . esc_html( $course->post_title ) . '</a>';
			}
		}
	}

	/**
	 * Add courses filter select box to the lessons list in admin.
	 */
	public static function lessons_add_filters() {
		$screen = get_current_screen();

		if ( 'ib_educator_lesson' == $screen->post_type ) {
			$args = array(
				'post_type'      => 'ib_educator_course',
				'posts_per_page' => -1,
			);

			if ( ! current_user_can( 'edit_others_ib_educator_lessons' ) ) {
				$args['author'] = get_current_user_id();
			}

			$courses = get_posts( $args );

			if ( $courses ) {
				$selected_course = isset( $_GET['ibedu_course'] ) ? absint( $_GET['ibedu_course'] ) : 0;
				echo '<select name="ibedu_course">';
				echo '<option value="0">' . __( 'All courses', 'ibeducator' ) . '</option>';
				foreach ( $courses as $course ) {
					echo '<option value="' . absint( $course->ID ) . '"' . ( $course->ID == $selected_course ? ' selected="selected"' : '' ) . '>' . esc_html( $course->post_title ) . '</option>';
				}
				echo '</select>';
			}
		}
	}

	/**
	 * Filter lessons output in the lessons list.
	 *
	 * @param WP_Query $query
	 */
	public static function lessons_parse_filters( $query ) {
		if ( is_admin() && $query->is_main_query() && 'ib_educator_lesson' == $query->query['post_type'] ) {
			$selected_course = isset( $_GET['ibedu_course'] ) ? absint( $_GET['ibedu_course'] ) : 0;

			if ( $selected_course ) {
				$query->set( 'meta_query', array(
					array(
						'key'     => '_ibedu_course',
						'value'   => $selected_course,
						'compare' => '=',
						'type'    => 'UNSIGNED'
					)
				) );
			}
		}
	}

	/**
	 * Setup admin menu.
	 */
	public static function admin_menu() {
		add_menu_page(
			__( 'Educator', 'ibeducator' ),
			__( 'Educator', 'ibeducator' ),
			'manage_educator',
			'ib_educator_admin',
			array( 'IB_Educator_Admin', 'admin_index' )
		);

		add_submenu_page(
			'ib_educator_admin',
			__( 'Educator Settings', 'ibeducator' ),
			__( 'Settings', 'ibeducator' ),
			'manage_educator',
			'ib_educator_admin'
		);

		add_submenu_page(
			'ib_educator_admin',
			__( 'Educator Payments', 'ibeducator' ),
			__( 'Payments', 'ibeducator' ),
			'manage_educator',
			'ib_educator_payments',
			array( 'IB_Educator_Admin', 'admin_payments' )
		);

		if ( current_user_can( 'manage_educator' ) ) {
			add_submenu_page(
				'ib_educator_admin',
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'manage_educator',
				'ib_educator_entries',
				array( 'IB_Educator_Admin', 'admin_entries' )
			);
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			add_menu_page(
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'educator_edit_entries',
				'ib_educator_entries',
				array( 'IB_Educator_Admin', 'admin_entries' )
			);
		}
	}

	/**
	 * Setup settings.
	 */
	public static function setup_settings() {
		add_settings_section(
			'ib_educator_pages', // id
			__( 'Pages', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'section_description' ),
			'ib_educator_general' // page
		);

		// Get pages.
		$tmp_pages = get_pages();
		$pages = array();
		foreach ( $tmp_pages as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}
		unset( $tmp_pages );

		add_settings_field(
			'student_courses_page',
			__( 'Student\'s Courses', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'        => 'student_courses_page',
				'choices'     => $pages,
				'description' => sprintf( __( 'This page outputs the student\'s pending, in progress and complete courses. Add the following shortcode to this page: %s', 'ibeducator' ), '[ibedu_student_courses]' ),
			)
		);

		add_settings_field(
			'payment_page',
			__( 'Payment', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'        => 'payment_page',
				'choices'     => $pages,
				'description' => sprintf( __( 'This page outputs the payment details of the course. Add the following shortcode to this page: %s', 'ibeducator' ), '[ibedu_payment_page]' ),
			)
		);

		// General settings.
		add_settings_section(
			'ib_educator_currency', // id
			__( 'Currency', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'section_description' ),
			'ib_educator_general' // page
		);

		// Currency.
		add_settings_field(
			'currency',
			__( 'Currency', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'    => 'currency',
				'choices' => ib_edu_get_currencies(),
			)
		);

		// Currency position.
		add_settings_field(
			'currency_position',
			__( 'Currency Position', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'    => 'currency_position',
				'choices' => array(
					'before' => __( 'Before', 'ibeducator' ),
					'after'  => __( 'After', 'ibeducator' ),
				),
			)
		);

		// Decimal point separator.
		add_settings_field(
			'decimal_point',
			__( 'Decimal Point Separator', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_text' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'    => 'decimal_point',
				'size'    => 3,
				'default' => '.',
			)
		);

		// Thousands separator.
		add_settings_field(
			'thousands_sep',
			__( 'Thousands Separator', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_text' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'    => 'thousands_sep',
				'size'    => 3,
				'default' => ',',
			)
		);

		register_setting(
			'ib_educator_settings', // option group
			'ib_educator_settings',
			array( 'IB_Educator_Admin', 'settings_validate' )
		);
	}

	/**
	 * General settings section description.
	 */
	public static function section_description() {}

	/**
	 * Validate general settings section.
	 *
	 * @param array $input
	 * @return array
	 */
	public static function settings_validate( $input ) {
		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'student_courses_page':
				case 'payment_page':
					$clean[ $key ] = intval( $value );
					break;

 				case 'currency':
 					if ( array_key_exists( $input[ $key ], ib_edu_get_currencies() ) ) {
 						$clean[ $key ] = $input[ $key ];
 					}
 					break;

 				case 'currency_position':
 					if ( in_array( $value, array( 'before', 'after' ) ) ) {
 						$clean[ $key ] = $value;
 					}
 					break;

 				case 'decimal_point':
 				case 'thousands_sep':
 					$clean[ $key ] = preg_replace( '/[^,. ]/', '', $value );
 					break;
			}
		}

		return $clean;
	}

	/**
	 * Text field.
	 *
	 * @param array $args
	 */
	public static function setting_text( $args ) {
		$settings = get_option( 'ib_educator_settings', array() );
		$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
		if ( empty( $value ) && isset( $args['default'] ) ) $value = $args['default'];
		$size = isset( $args['size'] ) ? ' size="' . intval( $args['size'] ) . '"' : '';

		echo '<input type="text" name="ib_educator_settings[' . esc_attr( $args['name'] ) . ']"' . $size . ' value="' . esc_attr( $value ) . '">';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Select field.
	 *
	 * @param array $args
	 */
	public static function setting_select( $args ) {
		$settings = get_option( 'ib_educator_settings', array() );
		$current_value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];

		echo '<select name="ib_educator_settings[' . esc_attr( $args['name'] ) . ']">';
		echo '<option value="">&mdash; ' . __( 'Select', 'ibeducator' ) . ' &mdash;</option>';

		foreach ( $args['choices'] as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . ( $value == $current_value ? ' selected="selected"' : '' ) . '>' . esc_html( $label ) . '</option>';
		}

		echo '</select>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Output Educator settings page.
	 */
	public static function admin_index() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';

		switch ( $current_tab ) {
			case 'general':
			case 'payment':
				require IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-' . $current_tab . '.php';
				break;
		}
	}

	/**
	 * Output admin settings tabs.
	 *
	 * @param string $current_tab
	 */
	public static function settings_tabs( $current_tab ) {
		$tabs = array(
			'general' => __( 'General', 'ibeducator' ),
			'payment' => __( 'Payment', 'ibeducator' ),
		);
		?>
		<h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_key => $tab_name ) : ?>
		<a class="nav-tab<?php if ( $tab_key == $current_tab ) echo ' nav-tab-active'; ?>" href="<?php echo admin_url( 'admin.php?page=ib_educator_admin&tab=' . $tab_key ); ?>"><?php echo $tab_name; ?></a>
		<?php endforeach; ?>
		</h2>
		<?php
	}

	/**
	 * Output Educator payments page.
	 */
	public static function admin_payments() {
		$action = isset( $_GET['edu-action'] ) ? $_GET['edu-action'] : 'payments';

		switch ( $action ) {
			case 'payments':
			case 'edit-payment':
				require( IBEDUCATOR_PLUGIN_DIR . 'admin/templates/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Output Educator entries page.
	 */
	public static function admin_entries() {
		$action = isset( $_GET['edu-action'] ) ? $_GET['edu-action'] : 'entries';

		switch ( $action ) {
			case 'entries':
			case 'edit-entry':
			case 'entry-progress':
				require( IBEDUCATOR_PLUGIN_DIR . 'admin/templates/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Process admin actions.
	 */
	public static function admin_actions() {
		if ( isset( $_GET['edu-action'] ) ) {
			require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-admin-actions.php';

			switch ( $_GET['edu-action'] ) {
				case 'edit-entry':
					IB_Educator_Admin_Actions::edit_entry();
					break;

				case 'edit-payment':
					IB_Educator_Admin_Actions::edit_payment();
					break;

				case 'edit-payment-gateway':
					IB_Educator_Admin_Actions::edit_payment_gateway();
					break;
			}
		}
	}

	/**
	 * AJAX: delete payment.
	 */
	public static function admin_payments_delete() {
		if ( ! current_user_can( 'manage_educator' ) ) {
			exit;
		}
		
		$payment_id = isset( $_POST['payment_id'] ) ? absint( $_POST['payment_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ib_educator_delete_payment_' . $payment_id ) ) {
			exit;
		}

		$response = '';
		$payment = IB_Educator_Payment::get_instance( $payment_id );

		if ( $payment && $payment->delete() ) {
			$response = 'success';
		} else {
			$response = 'failure';
		}

		echo $response;
		exit;
	}

	/**
	 * AJAX: delete entry.
	 */
	public static function admin_entries_delete() {
		if ( ! current_user_can( 'manage_educator' ) ) {
			exit;
		}

		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ib_educator_delete_entry_' . $entry_id ) ) {
			exit;
		}

		$response = '';
		$entry = IB_Educator_Entry::get_instance( $entry_id );

		if ( $entry && $entry->delete() ) {
			$response = 'success';
		} else {
			$response = 'failure';
		}

		echo $response;
		exit;
	}
}