<?php

class IB_Educator_Post_Types {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 8 ); // Run before the plugin update.
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 8 ); // Run before the plugin update.
	}

	/**
	 * Register post types.
	 */
	public static function register_post_types() {
		$permalink_settings = get_option( 'ib_educator_permalinks' );

		// Courses.
		$course_slug = ( $permalink_settings && ! empty( $permalink_settings['course_base'] ) ) ? $permalink_settings['course_base'] : _x( 'courses', 'course slug', 'ibeducator' );
		$courses_archive_slug = ( $permalink_settings && ! empty( $permalink_settings['courses_archive_base'] ) ) ? $permalink_settings['courses_archive_base'] : _x( 'courses', 'courses archive slug', 'ibeducator' );

		register_post_type(
			'ib_educator_course',
			apply_filters( 'ib_educator_cpt_course', array(
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
				'has_archive'         => $courses_archive_slug,
				'rewrite'             => array( 'slug' => $course_slug ),
				'query_var'           => 'course',
				'can_export'          => true,
			) )
		);

		// Lessons.
		register_post_type(
			'ib_educator_lesson',
			apply_filters( 'ib_educator_cpt_lesson', array(
				'labels'              => array(
					'name'          => __( 'Lessons', 'ibeducator' ),
					'singular_name' => __( 'Lesson', 'ibeducator' ),
				),
				'public'              => true,
				'exclude_from_search' => false,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => true,
				'capability_type'     => 'ib_educator_lesson',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => ( ! empty( $permalink_settings['lessons_archive_base'] ) ) ? $permalink_settings['lessons_archive_base'] : _x( 'lessons', 'lesson slug', 'ibeducator' ),
				'rewrite'             => array(
					'slug' => ( ! empty( $permalink_settings['lesson_base'] ) ) ? $permalink_settings['lesson_base'] : _x( 'lessons', 'lessons archive slug', 'ibeducator' ),
				),
				'query_var'           => 'lesson',
				'can_export'          => true,
			) )
		);

		// Memberships.
		register_post_type(
			'ib_edu_membership',
			apply_filters( 'ib_educator_cpt_membership', array(
				'label'               => __( 'Membership Levels', 'ibeducator' ),
				'labels'              => array(
					'name'               => __( 'Membership Levels', 'ibeducator' ),
					'singular_name'      => __( 'Membership Level', 'ibeducator' ),
					'add_new_item'       => __( 'Add New Membership Level', 'ibeducator' ),
					'edit_item'          => __( 'Edit Membership Level', 'ibeducator' ),
					'new_item'           => __( 'New Membership Level', 'ibeducator' ),
					'view_item'          => __( 'View Membership Level', 'ibeducator' ),
					'search_items'       => __( 'Search Membership Levels', 'ibeducator' ),
					'not_found'          => __( 'No membership levels found', 'ibeducator' ),
					'not_found_in_trash' => __( 'No membership levels found in Trash', 'ibeducator' ),
				),
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => 'ib_educator_admin',
				'exclude_from_search' => true,
				'capability_type'     => 'ib_edu_membership',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
				'has_archive'         => false,
				'rewrite'             => array( 'slug' => 'membership' ),
				'query_var'           => 'membership',
				'can_export'          => true,
			) )
		);
	}

	/**
	 * Register taxonomies.
	 */
	public static function register_taxonomies() {
		$permalink_settings = get_option( 'ib_educator_permalinks' );
		
		// Course categories.
		register_taxonomy(
			'ib_educator_category',
			'ib_educator_course',
			apply_filters( 'ib_educator_ct_category', array(
				'label'             => __( 'Course Categories', 'ibeducator' ),
				'public'            => true,
				'show_ui'           => true,
				'show_in_nav_menus' => true,
				'hierarchical'      => true,
				'rewrite'           => array(
					'slug' => ( ! empty( $permalink_settings['category_base'] ) ) ? $permalink_settings['category_base'] : _x( 'course-category', 'slug', 'ibeducator' ),
				),
				'capabilities'      => array(
					'assign_terms' => 'edit_ib_educator_courses',
				),
			) )
		);
	}
}