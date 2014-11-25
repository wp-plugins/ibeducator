<?php

class IB_Educator_Admin_Meta {
	public static function init() {
		// Add meta boxes.
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );

		// Save lesson meta box.
		add_action( 'save_post', array( __CLASS__, 'save_lesson_meta_box' ), 10, 3 );

		// Save course meta box.
		add_action( 'save_post', array( __CLASS__, 'save_course_meta_box' ), 10, 3 );
	}

	/**
	 * Add meta boxes.
	 */
	public static function add_meta_boxes() {
		// Course meta box.
		add_meta_box(
			'ib_educator_course_meta',
			__( 'Course Settings', 'ibeducator' ),
			array( __CLASS__, 'course_meta_box' ),
			'ib_educator_course'
		);

		// Lesson meta box.
		add_meta_box(
			'ib_educator_lesson_meta',
			__( 'Lesson Settings', 'ibeducator' ),
			array( __CLASS__, 'lesson_meta_box' ),
			'ib_educator_lesson'
		);
	}

	/**
	 * Output course meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function course_meta_box( $post ) {
		wp_nonce_field( 'ib_educator_course_meta_box', 'ib_educator_course_meta_box_nonce' );

		$price = ib_edu_get_course_price( $post->ID );
		?>
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-educator-price"><?php _e( 'Price', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" id="ib-educator-price" name="_ibedu_price" value="<?php echo esc_attr( $price ); ?>">
			</div>
		</div>

		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-educator-difficulty"><?php _e( 'Difficulty', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<?php
					$difficulty = get_post_meta( $post->ID, '_ib_educator_difficulty', true );
					$difficulty_levels = ib_edu_get_difficulty_levels();
				?>
				<select id="ib-educator-difficulty" name="_ib_educator_difficulty">
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
		wp_nonce_field( 'ib_educator_lesson_meta_box', 'ib_educator_lesson_meta_box_nonce' );

		$value = get_post_meta( $post->ID, '_ibedu_course', true );
		$courses = get_posts( array( 'post_type' => 'ib_educator_course', 'posts_per_page' => -1 ) );
		?>
		<?php if ( ! empty( $courses ) ) : ?>
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="ib-educator-course"><?php _e( 'Course', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<select id="ib-educator-course" name="_ibedu_course">
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
		if ( ! isset( $_POST['ib_educator_course_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ib_educator_course_meta_box_nonce'], 'ib_educator_course_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ib_educator_course' != $post->post_type || ! current_user_can( 'edit_ib_educator_course', $post_id ) ) {
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
		if ( ! isset( $_POST['ib_educator_lesson_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['ib_educator_lesson_meta_box_nonce'], 'ib_educator_lesson_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'ib_educator_lesson' != $post->post_type || ! current_user_can( 'edit_ib_educator_lesson', $post_id ) ) {
			return;
		}

		$value = ( isset( $_POST['_ibedu_course'] ) && is_numeric( $_POST['_ibedu_course'] ) ) ? $_POST['_ibedu_course'] : '';
		update_post_meta( $post_id, '_ibedu_course', $value );
	}
}