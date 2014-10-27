<?php

class IB_Educator_Install {
	private $payments;
	private $entries;
	private $questions;
	private $choices;
	private $answers;
	private $grades;

	public function __construct() {
		$tables = ib_edu_table_names();
		$this->payments = $tables['payments'];
		$this->entries = $tables['entries'];
		$this->questions = $tables['questions'];
		$this->choices = $tables['choices'];
		$this->answers = $tables['answers'];
		$this->grades = $tables['grades'];
	}

	/**
	 * Install.
	 */
	public function activate() {
		// Setup database tables.
		$this->setup_tables();

		// Setup user roles and capabilities.
		$this->setup_roles();

		// Add post types and endpoints to flush rewrite rules properly.
		IB_Educator_Main::register_post_types();
		IB_Educator_Main::add_rewrite_endpoints();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation cleanup.
	 */
	public function deactivate() {
		flush_rewrite_rules();
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
  grade decimal(5,2) unsigned NOT NULL,
  entry_status varchar(20) NOT NULL,
  entry_date datetime NOT NULL default '0000-00-00 00:00:00',
  complete_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY record_status (entry_status)
) $charset_collate;
CREATE TABLE {$this->payments} (
  ID bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL,
  course_id bigint(20) unsigned NOT NULL,
  payment_gateway varchar(20) NOT NULL,
  payment_status varchar(20) NOT NULL,
  amount decimal(8, 2) NOT NULL,
  currency char(3) NOT NULL,
  payment_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY user_id (user_id),
  KEY course_id (course_id)
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
				'read' => true
			) );

			// Student role.
			add_role( 'student', __( 'Student', 'ibeducator' ), array(
				'read' => true
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
	 * Get capabilities per role.
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
					'ibedu_edit_entries'
				);

				// Capabilities for custom post types.
				$capability_types = array( 'ibedu_course', 'ibedu_lesson' );

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
					'ibedu_edit_entries',
					'level_2'
				);

				// Course capabilities.
				$capabilities['ibedu_course'] = array(
					'edit_ibedu_courses',
					'publish_ibedu_courses',
					'delete_ibedu_courses',
					'delete_published_ibedu_courses',
					'edit_published_ibedu_courses',
				);

				// Lesson capabilities.
				$capabilities['ibedu_lesson'] = array(
					'edit_ibedu_lessons',
					'publish_ibedu_lessons',
					'delete_ibedu_lessons',
					'delete_published_ibedu_lessons',
					'edit_published_ibedu_lessons',
				);
				break;
		}

		return $capabilities;
	}
}