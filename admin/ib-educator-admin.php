<?php

class IB_Educator_Admin {
	/**
	 * Initialize admin.
	 */
	public static function init() {
		// Enqueue scripts and stylesheets.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ), 9 );

		// Order lessons by menu_order in lessons list admin screen.
		add_action( 'pre_get_posts', array( __CLASS__, 'lessons_menu_order' ) );

		// Add the course column name to lessons list admin screen.		
		add_filter( 'manage_ib_educator_lesson_posts_columns', array( __CLASS__, 'lessons_columns' ) );

		// Add the course column content to lessons list admin screen.
		add_filter( 'manage_ib_educator_lesson_posts_custom_column', array( __CLASS__, 'lessons_column_output' ), 10, 2 );

		// Add filters controls to the lessons list admin screen.
		add_filter( 'restrict_manage_posts', array( __CLASS__, 'lessons_add_filters' ) );

		// Filter lessons list admin screen (by course).
		add_filter( 'pre_get_posts', array( __CLASS__, 'lessons_parse_filters' ) );

		// Admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 9 );

		// Settings.
		add_action( 'admin_init', array( __CLASS__, 'setup_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_actions' ) );

		// AJAX actions.
		add_action( 'wp_ajax_ib_educator_delete_payment', array( __CLASS__, 'admin_payments_delete' ) );
		add_action( 'wp_ajax_ib_educator_delete_entry', array( __CLASS__, 'admin_entries_delete' ) );
		add_action( 'wp_ajax_ib_educator_autocomplete', array( __CLASS__, 'ajax_autocomplete' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		$screen = get_current_screen();

		if ( $screen && in_array( $screen->id, array( 'educator_page_ib_educator_payments', 'educator_page_ib_educator_entries' ) ) ) {
			wp_enqueue_script( 'ib-educator-autocomplete', IBEDUCATOR_PLUGIN_URL . 'admin/js/autocomplete.js', array( 'jquery' ), '1.0' );
		}

		wp_enqueue_style( 'ib-educator-admin', IBEDUCATOR_PLUGIN_URL . 'admin/css/admin.css', array(), '1.0' );
	}

	/**
	 * Modify lessons order in the admin panel.
	 *
	 * @param WP_Query $query
	 */
	public static function lessons_menu_order( $query ) {
		if ( $query->is_main_query() && 'ib_educator_lesson' == $query->query['post_type'] ) {
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
					echo '<option value="' . absint( $course->ID ) . '"' . ( $course->ID == $selected_course ? ' selected="selected"' : '' )
						 . '>' . esc_html( $course->post_title ) . '</option>';
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
			array( __CLASS__, 'admin_index' ),
			IBEDUCATOR_PLUGIN_URL . '/admin/images/educator-icon.png'
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
			array( __CLASS__, 'admin_payments' )
		);

		if ( current_user_can( 'manage_educator' ) ) {
			add_submenu_page(
				'ib_educator_admin',
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'manage_educator',
				'ib_educator_entries',
				array( __CLASS__, 'admin_entries' )
			);
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			add_menu_page(
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'educator_edit_entries',
				'ib_educator_entries',
				array( __CLASS__, 'admin_entries' )
			);
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
			case 'email':
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
			'email'   => __( 'Emails', 'ibeducator' ),
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
	 * Setup settings.
	 */
	public static function setup_settings() {
		add_settings_section(
			'ib_educator_pages', // id
			__( 'Pages', 'ibeducator' ),
			array( __CLASS__, 'section_description' ),
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
			array( __CLASS__, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'           => 'student_courses_page',
				'settings_group' => 'ib_educator_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the student\'s pending, in progress and complete courses. Add the following shortcode to this page: %s', 'ibeducator' ), '[ibedu_student_courses]' ),
			)
		);

		add_settings_field(
			'payment_page',
			__( 'Payment', 'ibeducator' ),
			array( __CLASS__, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_pages', // section
			array(
				'name'           => 'payment_page',
				'settings_group' => 'ib_educator_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the payment details of the course. Add the following shortcode to this page: %s', 'ibeducator' ), '[ibedu_payment_page]' ),
			)
		);

		// General settings.
		add_settings_section(
			'ib_educator_currency', // id
			__( 'Currency', 'ibeducator' ),
			array( __CLASS__, 'section_description' ),
			'ib_educator_general' // page
		);

		// Currency.
		add_settings_field(
			'currency',
			__( 'Currency', 'ibeducator' ),
			array( __CLASS__, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'currency',
				'settings_group' => 'ib_educator_settings',
				'choices'        => ib_edu_get_currencies(),
			)
		);

		// Currency position.
		add_settings_field(
			'currency_position',
			__( 'Currency Position', 'ibeducator' ),
			array( __CLASS__, 'setting_select' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'currency_position',
				'settings_group' => 'ib_educator_settings',
				'choices'        => array(
					'before' => __( 'Before', 'ibeducator' ),
					'after'  => __( 'After', 'ibeducator' ),
				),
			)
		);

		// Decimal point separator.
		add_settings_field(
			'decimal_point',
			__( 'Decimal Point Separator', 'ibeducator' ),
			array( __CLASS__, 'setting_text' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'decimal_point',
				'settings_group' => 'ib_educator_settings',
				'size'           => 3,
				'default'        => '.',
			)
		);

		// Thousands separator.
		add_settings_field(
			'thousands_sep',
			__( 'Thousands Separator', 'ibeducator' ),
			array( __CLASS__, 'setting_text' ),
			'ib_educator_general', // page
			'ib_educator_currency', // section
			array(
				'name'           => 'thousands_sep',
				'settings_group' => 'ib_educator_settings',
				'size'           => 3,
				'default'        => ',',
			)
		);

		register_setting(
			'ib_educator_settings', // option group
			'ib_educator_settings',
			array( __CLASS__, 'validate_general_settings' )
		);

		// Email settings.
		self::register_email_settings();
	}

	public static function register_email_settings() {
		add_settings_section(
			'ib_educator_email_settings', // id
			__( 'Email Settings', 'ibeducator' ),
			array( __CLASS__, 'section_description' ),
			'ib_educator_email_page' // page
		);

		// Setting: From Name.
		add_settings_field(
			'ib_educator_from_name',
			__( 'From Name', 'ibeducator' ),
			array( __CLASS__, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_settings', // section
			array(
				'name'           => 'from_name',
				'settings_group' => 'ib_educator_email',
				'description'    => __( 'The name email notifications are said to come from.', 'ibeducator' ),
				'default'        => get_bloginfo( 'name' ),
			)
		);

		// Setting: From Email.
		add_settings_field(
			'ib_educator_from_email',
			__( 'From Email', 'ibeducator' ),
			array( __CLASS__, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_settings', // section
			array(
				'name'           => 'from_email',
				'settings_group' => 'ib_educator_email',
				'description'    => __( 'Email to send notifications from.', 'ibeducator' ),
				'default'        => get_bloginfo( 'admin_email' ),
			)
		);

		// Email templates.
		add_settings_section(
			'ib_educator_email_templates', // id
			__( 'Email Templates', 'ibeducator' ),
			array( __CLASS__, 'section_description' ),
			'ib_educator_email_page' // page
		);

		// Subject: student registered.
		add_settings_field(
			'ib_subject_student_registered',
			__( 'Student registered subject', 'ibeducator' ),
			array( __CLASS__, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'ib_educator_student_registered',
				'description'    => sprintf( __( 'Subject of the student registered notification email. Placeholders: %s', 'ibeducator' ), '{course_title}, {login_link}' ),
			)
		);

		// Template: student registered.
		add_settings_field(
			'ib_template_student_registered',
			__( 'Student registered template', 'ibeducator' ),
			array( __CLASS__, 'setting_textarea' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'ib_educator_student_registered',
				'description'    => sprintf( __( 'Placeholders: %s', 'ibeducator' ), '{student_name}, {course_title}, {course_excerpt}, {login_link}' ),
			)
		);

		// Subject: quiz grade.
		add_settings_field(
			'ib_subject_quiz_grade',
			__( 'Quiz grade subject', 'ibeducator' ),
			array( __CLASS__, 'setting_text' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'ib_educator_quiz_grade',
				'description'    => __( 'Subject of the quiz grade email.', 'ibeducator' ),
			)
		);

		// Template: quiz grade.
		add_settings_field(
			'ib_template_quiz_grade',
			__( 'Quiz grade template', 'ibeducator' ),
			array( __CLASS__, 'setting_textarea' ),
			'ib_educator_email_page', // page
			'ib_educator_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'ib_educator_quiz_grade',
				'description'    => sprintf( __( 'Placeholders: %s', 'ibeducator' ), '{student_name}, {lesson_title}, {grade}, {login_link}' ),
			)
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_email',
			array( __CLASS__, 'validate_email_settings' )
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_student_registered',
			array( __CLASS__, 'validate_email_template' )
		);

		register_setting(
			'ib_educator_email_settings', // option group
			'ib_educator_quiz_grade',
			array( __CLASS__, 'validate_email_template' )
		);
	}

	/**
	 * General settings section description.
	 */
	public static function section_description( $arg ) {
		if ( is_array( $arg ) && isset( $arg['id'] ) ) {
			switch ( $arg['id'] ) {
				case 'ib_educator_pages':
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><?php _e( 'Courses Archive', 'ibeducator' ); ?></th>
								<td>
									<?php
										$archive_link = get_post_type_archive_link( 'ib_educator_course' );

										if ( $archive_link ) {
											echo '<a href="' . esc_url( $archive_link ) . '" target="_blank">' . esc_url( $archive_link ) . '</a>';
										}
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php
					break;
			}
		}
	}

	/**
	 * Validate general settings section.
	 *
	 * @param array $input
	 * @return array
	 */
	public static function validate_general_settings( $input ) {
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
	 * Validate email settings section.
	 *
	 * @param array $input
	 * @return array
	 */
	public static function validate_email_settings( $input ) {
		if ( ! is_array( $input ) ) return '';

		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'from_name':
					$clean[ $key ] = esc_html( $value );
					break;

				case 'from_email':
					$clean[ $key ] = sanitize_email( $value );
					break;
			}
		}

		return $clean;
	}

	/**
	 * Validate email template content.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function validate_email_template( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 * Text field.
	 *
	 * @param array $args
	 */
	public static function setting_text( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && isset( $args['default'] ) ) $value = $args['default'];
		$size = isset( $args['size'] ) ? ' size="' . intval( $args['size'] ) . '"' : '';

		echo '<input type="text" name="' . esc_attr( $name ) . '" class="regular-text"' . $size . ' value="' . esc_attr( $value ) . '">';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Textarea field.
	 *
	 * @param array $args
	 */
	public static function setting_textarea( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && isset( $args['default'] ) ) $value = $args['default'];

		echo '<textarea name="' . esc_attr( $name ) . '" class="large-text" rows="5" cols="40">' . esc_textarea( $value ) . '</textarea>';

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
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		echo '<select name="' . esc_attr( $name ) . '">';
		echo '<option value="">&mdash; ' . __( 'Select', 'ibeducator' ) . ' &mdash;</option>';

		foreach ( $args['choices'] as $choice => $label ) {
			echo '<option value="' . esc_attr( $choice ) . '"' . ( $choice == $value ? ' selected="selected"' : '' ) . '>' . esc_html( $label ) . '</option>';
		}

		echo '</select>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
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

	/**
	 * AJAX: autocomplete.
	 */
	public static function ajax_autocomplete() {
		if ( ! current_user_can( 'manage_educator' ) ) {
			exit;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ib_educator_autocomplete' ) ) {
			exit;
		}

		if ( ! isset( $_GET['entity'] ) ) {
			exit;
		}

		$entity = $_GET['entity'];
		$response = array();
		
		switch ( $entity ) {
			case 'student':
				$user_args = array();
				
				if ( ! empty( $_GET['input'] ) ) {
					$user_args['search'] = '*' . $_GET['input'] . '*';
					$user_args['search_columns'] = array( 'user_login', 'user_nicename' );
				}

				$user_args['number'] = 15;
				$user_args['role'] = 'student';
				$user_query = new WP_User_Query( $user_args );

				if ( ! empty( $user_query->results ) ) {
					foreach ( $user_query->results as $user ) {
						$response[ $user->ID ] = esc_html( $user->display_name );
					}
				}
				break;

			case 'post':
			case 'course':
				$post_args = array();

				if ( ! empty( $_GET['input'] ) ) {
					$post_args['s'] = $_GET['input'];
				}

				if ( 'course' == $entity ) {
					$post_args['post_type'] = 'ib_educator_course';
				}

				$post_args['post_status'] = 'publish';
				$post_args['posts_per_page'] = 15;
				$posts_query = new WP_Query( $post_args );

				if ( $posts_query->have_posts() ) {
					while ( $posts_query->have_posts() ) {
						$posts_query->the_post();
						$response[ get_the_ID() ] = get_the_title();
					}

					wp_reset_postdata();
				}
				break;
		}

		echo json_encode( $response );
		exit;
	}
}