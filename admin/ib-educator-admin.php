<?php

class IB_Educator_Admin {
	/**
	 * Initialize admin.
	 */
	public static function init() {
		// Enqueue scripts and stylesheets.
		add_action( 'admin_enqueue_scripts', array( 'IB_Educator_Admin', 'enqueue_scripts_styles' ), 9 );

		// Add meta boxes.
		add_action( 'add_meta_boxes', array( 'IB_Educator_Admin', 'add_meta_boxes' ) );

		// Save lesson meta box.
		add_action( 'save_post', array( 'IB_Educator_Admin', 'save_lesson_meta_box' ), 10, 3 );

		// Save course meta box.
		add_action( 'save_post', array( 'IB_Educator_Admin', 'save_course_meta_box' ), 10, 3 );

		// Order lessons by menu_order in lessons list admin screen.
		add_action( 'pre_get_posts', array( 'IB_Educator_Admin', 'lessons_menu_order' ) );

		// Add the course column name to lessons list admin screen.		
		add_filter( 'manage_ibedu_lesson_posts_columns', array( 'IB_Educator_Admin', 'lessons_columns' ) );

		// Add the course column content to lessons list admin screen.
		add_filter( 'manage_ibedu_lesson_posts_custom_column', array( 'IB_Educator_Admin', 'lessons_column_output' ), 10, 2 );

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
		add_action( 'wp_ajax_ibedu_delete_payment', array( 'IB_Educator_Admin', 'admin_payments_delete' ) );
		add_action( 'wp_ajax_ibedu_delete_entry', array( 'IB_Educator_Admin', 'admin_entries_delete' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		//$screen = get_current_screen();
		wp_enqueue_style( 'ib-educator-admin', IBEDUCATOR_PLUGIN_URL . 'admin/css/admin.css', array(), '1.0' );
	}

	/**
	 * Add meta boxes.
	 */
	public static function add_meta_boxes() {
		// Course meta box
		add_meta_box(
			'ibedu_course_meta',
			__( 'Course Settings', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'course_meta_box' ),
			'ibedu_course'
		);

		// Lesson meta box
		add_meta_box(
			'ibedu_lesson_meta',
			__( 'Lesson Settings', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'lesson_meta_box' ),
			'ibedu_lesson'
		);
	}

	/**
	 * Output course meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function course_meta_box( $post ) {
		wp_nonce_field( 'ibedu_course_meta_box', 'ibedu_course_meta_box_nonce' );

		$price = get_post_meta( $post->ID, '_ibedu_price', true );
		?>
		<div class="ibedu-field">
			<div class="ibedu-label"><label for="ibedu_price"><?php _e( 'Price', 'ibeducator' ); ?></label></div>
			<div class="ibedu-control">
				<input type="text" id="ibedu_price" name="_ibedu_price" value="<?php echo esc_attr( $price ); ?>">
			</div>
		</div>

		<div class="ibedu-field">
			<div class="ibedu-label"><label for="ibedu_difficulty"><?php _e( 'Difficulty', 'ibeducator' ); ?></label></div>
			<div class="ibedu-control">
				<?php
					$difficulty = get_post_meta( $post->ID, '_ib_educator_difficulty', true );
					$difficulty_levels = ib_edu_get_difficulty_levels();
				?>
				<select name="_ib_educator_difficulty">
					<option value=""><?php _e( 'None', 'ibeducator' ); ?></option>
					<?php
						foreach ( $difficulty_levels as $key => $label ) {
							echo '<option value="' . esc_attr( $key ) . '"' . ( $key == $difficulty ? ' selected="selected"' : '' ) . '>' . esc_html( $label ) . '</option>';
						}
					?>
				</select>
			</div>
		</div>
		<?php
	}

	/**
	 * Output lesson meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function lesson_meta_box( $post ) {
		wp_nonce_field( 'ibedu_lesson_meta_box', 'ibedu_lesson_meta_box_nonce' );

		$value = get_post_meta( $post->ID, '_ibedu_course', true );
		$courses = get_posts( array( 'post_type' => 'ibedu_course', 'posts_per_page' => -1 ) );
		?>
		<?php if ( ! empty( $courses ) ) : ?>
		<div class="ibedu-field">
			<div class="ibedu-label"><label for="_ibedu_course"><?php _e( 'Course', 'ibeducator' ); ?></label></div>
			<div class="ibedu-control">
				<select name="_ibedu_course">
					<option value=""><?php _e( 'Select Course', 'ibeducator' ); ?></option>
					<?php foreach ( $courses as $post ) : ?>
					<option value="<?php echo intval( $post->ID ); ?>"<?php if ( $value == $post->ID ) echo ' selected="selected"'; ?>><?php echo esc_html( $post->post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Save course meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_course_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['ibedu_course_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ibedu_course_meta_box_nonce'], 'ibedu_course_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ibedu_course' != $post->post_type || ! current_user_can( 'edit_ibedu_course', $post_id ) ) {
			return;
		}

		// Course price.
		$price = ( isset( $_POST['_ibedu_price'] ) && is_numeric( $_POST['_ibedu_price'] ) ) ? $_POST['_ibedu_price'] : '';
		update_post_meta( $post_id, '_ibedu_price', $price );

		// Course difficulty.
		$difficulty = ( isset( $_POST['_ib_educator_difficulty'] ) ) ? $_POST['_ib_educator_difficulty'] : '';

		if ( empty( $difficulty ) || array_key_exists( $difficulty, ib_edu_get_difficulty_levels() ) ) {
			update_post_meta( $post_id, '_ib_educator_difficulty', $difficulty );
		}
	}

	/**
	 * Save lesson meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_lesson_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['ibedu_lesson_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ibedu_lesson_meta_box_nonce'], 'ibedu_lesson_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ibedu_lesson' != $post->post_type || ! current_user_can( 'edit_ibedu_lesson', $post_id ) ) {
			return;
		}

		$value = ( isset( $_POST['_ibedu_course'] ) && is_numeric( $_POST['_ibedu_course'] ) ) ? $_POST['_ibedu_course'] : '';
		update_post_meta( $post_id, '_ibedu_course', $value );
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
			$course_id = get_post_meta( $post_id, '_ibedu_course', true );

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

		if ( 'ibedu_lesson' == $screen->post_type ) {
			$args = array(
				'post_type'      => 'ibedu_course',
				'posts_per_page' => -1,
			);

			if ( ! current_user_can( 'edit_others_ibedu_lessons' ) ) {
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
		if ( is_admin() && $query->is_main_query() && 'ibedu_lesson' == $query->query['post_type'] ) {
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
		} else if ( current_user_can( 'ibedu_edit_entries' ) ) {
			add_menu_page(
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'ibedu_edit_entries',
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
			'ibedu_pages',
			'',
			array( 'IB_Educator_Admin', 'general_settings' ),
			'ibedu_pages'
		);

		add_settings_field(
			'student_courses',
			__( 'Student\'s Courses', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_select_page' ),
			'ibedu_pages',
			'ibedu_pages',
			array(
				'label'       => __( 'Student\'s Courses', 'ibeducator' ),
				'name'        => 'student_courses',
				'description' => sprintf( __( 'This page outputs the student\'s pending, in progress and complete courses. Add the following shortcode to this page: %s', 'ibeducator' ), '[ibedu_student_courses]' )
			)
		);

		add_settings_field(
			'payment',
			__( 'Payment', 'ibeducator' ),
			array( 'IB_Educator_Admin', 'setting_select_page' ),
			'ibedu_pages',
			'ibedu_pages',
			array(
				'label'       => __( 'Payment', 'ibeducator' ),
				'name'        => 'payment',
				'description' => sprintf( __( 'This page outputs the payment details of the course. Add the following shortcode to this page: %s', 'ibeducator' ), '[ibedu_payment_page]' ),
			)
		);

		register_setting( 'ibedu_pages', 'ibedu_pages', array( 'IB_Educator_Admin', 'general_settings_validate' ) );
	}

	/**
	 * General settings section description.
	 */
	public static function general_settings() {}

	/**
	 * Validate general settings section.
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public static function general_settings_validate( $input ) {
		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'courses':
				case 'student_courses':
				case 'payment':
					$input[ $key ] = absint( $value );
 					break;
			}
		}
		
		return $input;
	}

	/**
	 * Setting form control: select page.
	 *
	 * @param array $args
	 */
	public static function setting_select_page( $args ) {
		$settings = get_option( 'ibedu_pages', array() );
		$current_page = isset( $settings[ $args['name'] ] ) ? $settings[ $args['name'] ] : '';
		$pages = get_pages();

		echo '<select name="ibedu_pages[' . $args['name'] . ']">';
		echo '<option value="">' . __( 'No page selected', 'ibeducator' ) . '</option>';
		foreach ( $pages as $page ) {
			echo '<option value="' . $page->ID . '"' . ( $page->ID == $current_page ? ' selected="selected"' : '' ) . '>' . $page->post_title . '</option>';
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
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'pages';

		switch ( $current_tab ) {
			case 'pages':
			case 'payment':
				require( IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-' . $current_tab . '.php' );
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
			'pages' => __( 'Pages', 'ibeducator' ),
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

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_delete_payment_' . $payment_id ) ) {
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

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ibedu_delete_entry_' . $entry_id ) ) {
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