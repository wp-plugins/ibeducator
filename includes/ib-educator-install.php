<?php

class IB_Educator_Install {
	/**
	 * @var string
	 */
	private $payments;

	/**
	 * @var string
	 */
	private $entries;

	/**
	 * @var string
	 */
	private $questions;

	/**
	 * @var string
	 */
	private $choices;

	/**
	 * @var string
	 */
	private $answers;

	/**
	 * @var string
	 */
	private $grades;

	public function __construct() {
		$tables = ib_edu_table_names();
		$this->payments  = $tables['payments'];
		$this->entries   = $tables['entries'];
		$this->questions = $tables['questions'];
		$this->choices   = $tables['choices'];
		$this->answers   = $tables['answers'];
		$this->grades    = $tables['grades'];
		$this->members   = $tables['members'];
	}

	/**
	 * Install.
	 */
	public function activate( $inc_post_types = true, $inc_endpoints = true ) {
		/*$current_version = get_option( 'ib_educator_version' );

		if ( $current_version ) {
			if ( version_compare( $current_version, '1.2.0', '<=' ) ) {}
		}*/

		// Setup the database tables.
		$this->setup_tables();

		// Setup the user roles and capabilities.
		$this->setup_roles();

		// Post types.
		if ( $inc_post_types ) {
			IB_Educator_Post_Types::register_post_types();
			IB_Educator_Post_Types::register_taxonomies();
		}
		
		if ( $inc_endpoints ) {
			IB_Educator_Main::add_rewrite_endpoints();
		}

		// Setup email templates.
		$this->setup_email_templates();

		// Schedule cron events.
		$this->schedule_events();

		/**
		 * Plugin activation hook.
		 */
		do_action( 'ib_educator_activation' );

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Update the plugin version in database.
		update_option( 'ib_educator_version', IBEDUCATOR_VERSION );
	}

	/**
	 * Plugin deactivation cleanup.
	 */
	public function deactivate() {
		$this->remove_scheduled_events();
		flush_rewrite_rules();
	}

	public function schedule_events() {
		// CRON: process expired memberships.
		if ( ! wp_next_scheduled( 'ib_educator_expired_memberships' ) ) {
			wp_schedule_event( strtotime( date( 'Y-m-d 00:00:00' ) ), 'daily', 'ib_educator_expired_memberships' );
		}

		// CRON: send the membership expiration notifications.
		if ( ! wp_next_scheduled( 'ib_educator_membership_notifications' ) ) {
			wp_schedule_event( strtotime( date( 'Y-m-d 00:00:00' ) ), 'daily', 'ib_educator_membership_notifications' );
		}
	}

	public function remove_scheduled_events() {
		wp_clear_scheduled_hook( 'ib_educator_expired_memberships' );
		wp_clear_scheduled_hook( 'ib_educator_membership_notifications' );
	}

	/**
	 * Get the capabilities for a post type.
	 *
	 * @return array
	 */
	public function get_post_type_caps() {
		return array(
			'edit_{post_type}',
			'read_{post_type}',
			'delete_{post_type}',
			'edit_{post_type}s',
			'edit_others_{post_type}s',
			'publish_{post_type}s',
			'read_private_{post_type}s',
			'delete_{post_type}s',
			'delete_private_{post_type}s',
			'delete_published_{post_type}s',
			'delete_others_{post_type}s',
			'edit_private_{post_type}s',
			'edit_published_{post_type}s',
		);
	}

	/**
	 * Setup database tables.
	 */
	public function setup_tables() {
		global $wpdb;
		$installed_ver = get_option( 'ibedu_db_version' );

		if ( $installed_ver != IBEDUCATOR_DB_VERSION ) {
			$charset_collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) {
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				}
				
				if ( ! empty( $wpdb->collate ) ) {
					$charset_collate .= " COLLATE $wpdb->collate";
				}
			}

			// Entries and payments.
			$sql = "CREATE TABLE {$this->entries} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  course_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  payment_id bigint(20) unsigned NOT NULL,
  object_id bigint(20) unsigned NOT NULL,
  grade decimal(5,2) unsigned NOT NULL,
  entry_origin varchar(20) NOT NULL default 'payment',
  entry_status varchar(20) NOT NULL,
  entry_date datetime NOT NULL default '0000-00-00 00:00:00',
  complete_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY record_status (entry_status)
) $charset_collate;
CREATE TABLE {$this->payments} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  parent_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  course_id bigint(20) unsigned NOT NULL,
  object_id bigint(20) unsigned default NULL,
  payment_type varchar(20) NOT NULL default 'course',
  payment_gateway varchar(20) NOT NULL,
  payment_status varchar(20) NOT NULL,
  txn_id varchar(20) NOT NULL default '',
  amount decimal(8, 2) NOT NULL,
  currency char(3) NOT NULL,
  payment_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY user_id (user_id),
  KEY course_id (course_id),
  KEY object_id (object_id),
  KEY parent_id (parent_id),
  KEY txn_id (txn_id)
) $charset_collate;";

			// Quiz.
			$sql .= "CREATE TABLE {$this->questions} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  lesson_id bigint(20) unsigned NOT NULL,
  question text default NULL,
  question_type enum('','multiplechoice', 'writtenanswer'),
  menu_order int(10) NOT NULL default 0,
  PRIMARY KEY  (ID),
  KEY lesson_id (lesson_id)
) $charset_collate;
CREATE TABLE {$this->choices} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  question_id bigint(20) unsigned NOT NULL,
  choice_text text default NULL,
  correct tinyint(1) NOT NULL,
  menu_order tinyint(3) unsigned NOT NULL default 0,
  PRIMARY KEY  (ID),
  KEY question_id (question_id),
  KEY menu_order (menu_order)
) $charset_collate;
CREATE TABLE {$this->answers} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  question_id bigint(20) unsigned NOT NULL,
  entry_id bigint(20) unsigned NOT NULL,
  choice_id bigint(20) unsigned NOT NULL,
  correct tinyint(2) NOT NULL default -1,
  answer_text text default NULL,
  PRIMARY KEY  (ID),
  KEY entry_id (entry_id)
) $charset_collate;
CREATE TABLE {$this->grades} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  lesson_id bigint(20) unsigned NOT NULL,
  entry_id bigint(20) unsigned NOT NULL,
  grade decimal(5,2) unsigned NOT NULL,
  status enum('pending','approved') NOT NULL default 'pending',
  PRIMARY KEY  (ID),
  KEY lesson_id (lesson_id),
  KEY entry_id (entry_id)
) $charset_collate;
CREATE TABLE {$this->members} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL,
  membership_id bigint(20) unsigned NOT NULL,
  status varchar(20) NOT NULL default '',
  expiration datetime NOT NULL default '0000-00-00 00:00:00',
  paused datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY user_id (user_id),
  KEY status (status),
  KEY expiration (expiration)
) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			update_option( 'ibedu_db_version', IBEDUCATOR_DB_VERSION );
		}
	}

	/**
	 * Setup user roles and capabilities.
	 */
	public function setup_roles() {
		global $wp_roles;

		if ( isset( $wp_roles ) && is_object( $wp_roles ) ) {
			// Lecturer role.
			add_role( 'lecturer', __( 'Lecturer', 'ibeducator' ), array(
				'read' => true,
			) );

			// Student role.
			add_role( 'student', __( 'Student', 'ibeducator' ), array(
				'read' => true,
			) );

			// Assign capabilities to administrator.
			$all_capabilities = $this->get_role_capabilities( 'administrator' );

			foreach ( $all_capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}

			// Assign capabilities to lecturer.
			$lecturer_capabilities = $this->get_role_capabilities( 'lecturer' );

			foreach ( $lecturer_capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'lecturer', $cap );
				}
			}
		}
	}

	/**
	 * Get the capabilities for a given role.
	 *
	 * @param string $role
	 * @return array
	 */
	public function get_role_capabilities( $role ) {
		$capabilities = array();

		switch ( $role ) {
			// ROLE: administrator.
			case 'administrator':
				// Various capabilities.
				$capabilities['core'] = array(
					'manage_educator',
					'educator_edit_entries'
				);

				// Capabilities for custom post types.
				$capability_types = array( 'ib_educator_course', 'ib_educator_lesson', 'ib_edu_membership' );

				// Post types capabilities.
				foreach ( $capability_types as $capability_type ) {
					$capabilities[ $capability_type ] = array(
						"edit_{$capability_type}",
						"read_{$capability_type}",
						"delete_{$capability_type}",
						"edit_{$capability_type}s",
						"edit_others_{$capability_type}s",
						"publish_{$capability_type}s",
						"read_private_{$capability_type}s",
						"delete_{$capability_type}s",
						"delete_private_{$capability_type}s",
						"delete_published_{$capability_type}s",
						"delete_others_{$capability_type}s",
						"edit_private_{$capability_type}s",
						"edit_published_{$capability_type}s",
					);
				}
				break;

			// ROLE: lecturer.
			case 'lecturer':
				// Various capabilities.
				$capabilities['core'] = array(
					'read',
					'upload_files',
					'educator_edit_entries',
					'level_2',
				);

				// Course capabilities.
				$capabilities['ib_educator_course'] = array(
					'edit_ib_educator_courses',
					'publish_ib_educator_courses',
					'delete_ib_educator_courses',
					'delete_published_ib_educator_courses',
					'edit_published_ib_educator_courses',
				);

				// Lesson capabilities.
				$capabilities['ib_educator_lesson'] = array(
					'edit_ib_educator_lessons',
					'publish_ib_educator_lessons',
					'delete_ib_educator_lessons',
					'delete_published_ib_educator_lessons',
					'edit_published_ib_educator_lessons',
				);

				// Allow lecturers to add posts, administrator has to approve these posts though.
				$capabilities['post'] = array(
					'delete_posts',
					'edit_posts',
				);
				break;
		}

		return $capabilities;
	}

	/**
	 * Setup email templates.
	 */
	public function setup_email_templates() {
		if ( ! get_option( 'ib_educator_student_registered' ) ) {
			update_option( 'ib_educator_student_registered', array(
				'subject' => sprintf( __( 'Registration for %s is complete', 'ibeducator' ), '{course_title}' ),
				'template' => 'Dear {student_name},

You\'ve got access to {course_title}.

{course_excerpt}

Log in: {login_link}

Best regards,
Administration',
			) );
		}

		if ( ! get_option( 'ib_educator_quiz_grade' ) ) {
			update_option( 'ib_educator_quiz_grade', array(
				'subject' => __( 'You\'ve got a grade', 'ibeducator' ),
				'template' => 'Dear {student_name},

You\'ve got {grade} for {lesson_title}.

Log in: {login_link}

Best regards,
Administration',
			) );
		}

		if ( ! get_option( 'ib_educator_membership_register' ) ) {
			update_option( 'ib_educator_membership_register', array(
				'subject' => __( 'You\'ve been registered for a membership', 'ibeducator' ),
				'template' => 'Dear {student_name},

Thank you for registering for the {membership} membership.

Membership: {membership}
Expiration: {expiration}
Price: {price}

Log in: {login_link}

Best regards,
Administration',
			) );
		}

		if ( ! get_option( 'ib_educator_membership_renew' ) ) {
			update_option( 'ib_educator_membership_renew', array(
				'subject' => __( 'Your membership expires', 'ibeducator' ),
				'template' => 'Dear {student_name},

Your {membership} membership expires on {expiration}.

Please renew your membership: {membership_payment_url}

Log in: {login_link}

Best regards,
Administration',
			) );
		}
	}
}