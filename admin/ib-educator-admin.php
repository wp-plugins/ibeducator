<?php

class IB_Educator_Admin {
	/**
	 * Initialize admin.
	 */
	public static function init() {
		// Enqueue scripts and stylesheets.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ), 9 );

		// Admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 9 );

		// Process admin actions.
		add_action( 'admin_init', array( __CLASS__, 'admin_actions' ) );

		// AJAX actions.
		add_action( 'wp_ajax_ib_educator_delete_payment', array( __CLASS__, 'admin_payments_delete' ) );
		add_action( 'wp_ajax_ib_educator_delete_entry', array( __CLASS__, 'admin_entries_delete' ) );

		add_action( 'pre_get_posts', array( __CLASS__, 'memberships_menu_order' ) );

		// Setup the autocomplete form field.
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-autocomplete.php';
		IB_Educator_Autocomplete::init();

		// Add various enhancements for the admin screens of courses, lessons, etc.
		require_once( IBEDUCATOR_PLUGIN_DIR . 'admin/custom-post-columns.php' );
		IB_Educator_Custom_Post_Columns::init();

		// Setup educator admin settings.
		require_once( IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-admin-settings.php' );
		IB_Educator_Admin_Settings::init();

		// Meta boxes.
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-admin-meta.php';
		IB_Educator_Admin_Meta::init();

		// Setup educator quiz admin.
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-quiz-admin.php';
		IB_Educator_Quiz_Admin::init();
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
			array( 'IB_Educator_Admin_Settings', 'admin_index' ),
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

		add_submenu_page(
			'ib_educator_admin',
			__( 'Educator Members', 'ibeducator' ),
			__( 'Members', 'ibeducator' ),
			'manage_educator',
			'ib_educator_members',
			array( __CLASS__, 'admin_members' )
		);
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		wp_enqueue_style( 'ib-educator-admin', IBEDUCATOR_PLUGIN_URL . 'admin/css/admin.css', array(), '1.0' );
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
	 * Output Educator members page.
	 */
	public static function admin_members() {
		$action = isset( $_GET['edu-action'] ) ? $_GET['edu-action'] : 'members';

		switch ( $action ) {
			case 'members':
			case 'edit-member':
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

				case 'edit-member':
					IB_Educator_Admin_Actions::edit_member();
					break;

				case 'edit-payment-gateway':
					IB_Educator_Admin_Actions::edit_payment_gateway();
					break;
			}
		}
	}

	public static function memberships_menu_order( $query ) {
		if ( $query->is_main_query() && 'ib_edu_membership' == $query->query['post_type'] ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
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