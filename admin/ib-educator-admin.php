<?php

/**
 * Educator plugin's admin setup.
 */
class IB_Educator_Admin {
	/**
	 * Initialize admin.
	 */
	public static function init() {
		self::includes();
		add_action( 'current_screen', array( __CLASS__, 'maybe_includes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 9 );
		add_action( 'admin_init', array( __CLASS__, 'admin_actions' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ), 9 );
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_action( 'wp_ajax_ib_educator_delete_payment', array( __CLASS__, 'admin_payments_delete' ) );
	}

	/**
	 * Include the necessary files.
	 */
	public static function includes() {
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-autocomplete.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-admin-post-types.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-admin-meta.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/ib-educator-quiz-admin.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-admin-settings.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-general-settings.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-learning-settings.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-payment-settings.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-taxes-settings.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-email-settings.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-memberships-settings.php';
		require_once IBEDUCATOR_PLUGIN_DIR . 'admin/edr-syllabus-admin.php';

		new IB_Educator_General_Settings();
		new IB_Educator_Learning_Settings();
		new IB_Educator_Payment_Settings();
		new IB_Educator_Taxes_Settings();
		new IB_Educator_Email_Settings();
		new IB_Educator_Memberships_Settings();
		IB_Educator_Autocomplete::init();
		IB_Educator_Admin_Post_Types::init();
		IB_Educator_Admin_Meta::init();
		IB_Educator_Quiz_Admin::init();
		new EDR_Syllabus_Admin();
	}

	/**
	 * Include the files based on the current screen.
	 *
	 * @param WP_Screen $screen
	 */
	public static function maybe_includes( $screen ) {
		switch ( $screen->id ) {
			case 'options-permalink':
				include IBEDUCATOR_PLUGIN_DIR . 'admin/settings/ib-educator-permalink-settings.php';

				new IB_Educator_Permalink_Settings();

				break;
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
			array( __CLASS__, 'settings_page' ),
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

		$entries_hook = null;

		if ( current_user_can( 'manage_educator' ) ) {
			$entries_hook = add_submenu_page(
				'ib_educator_admin',
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'manage_educator',
				'ib_educator_entries',
				array( __CLASS__, 'admin_entries' )
			);
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			$entries_hook = add_menu_page(
				__( 'Educator Entries', 'ibeducator' ),
				__( 'Entries', 'ibeducator' ),
				'educator_edit_entries',
				'ib_educator_entries',
				array( __CLASS__, 'admin_entries' )
			);
		}

		if ( $entries_hook ) {
			// Set screen options for the entries admin page.
			add_action( "load-$entries_hook", array( __CLASS__, 'add_entries_screen_options' ) );
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
	 * Output the settings page.
	 */
	public static function settings_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

		do_action( 'ib_educator_settings_page', $tab );
	}

	/**
	 * Process the admin actions.
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

				case 'delete-entry':
					IB_Educator_Admin_Actions::delete_entry();
					break;
			}
		}
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
	 * Add screen options to the entries admin page.
	 */
	public static function add_entries_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_ib_educator_entries' != $screen->id || isset( $_GET['edu-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'entries_per_page',
			'label'   => __( 'Entries per page', 'ibeducator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
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
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		wp_enqueue_style( 'ib-educator-admin', IBEDUCATOR_PLUGIN_URL . 'admin/css/admin.css', array(), '1.5' );

		$screen = get_current_screen();

		if ( $screen ) {
			if ( 'educator_page_ib_educator_payments' == $screen->id ) {
				wp_enqueue_script( 'ib-educator-edit-payment', IBEDUCATOR_PLUGIN_URL . 'admin/js/edit-payment.js', array( 'jquery' ), '1.4.1', true );
				wp_enqueue_script( 'postbox' );
			} elseif ( 'educator_page_ib_educator_entries' == $screen->id ) {
				wp_enqueue_script( 'postbox' );
			} elseif ( 'educator_page_ib_educator_members' == $screen->id ) {
				wp_enqueue_script( 'postbox' );
			}
		}
	}

	/**
	 * Save screen options for various admin pages.
	 *
	 * @param mixed $result
	 * @param string $option
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set_screen_option( $result, $option, $value ) {
		if ( 'entries_per_page' == $option ) {
			$result = (int) $value;
		}

		return $result;
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
}
