<?php
	$api = IB_Educator::get_instance();
?>
<article id="course-<?php the_ID(); ?>" <?php post_class( 'ibedu-course-single' ); ?>>
	<h1 class="course-title entry-title"><?php the_title(); ?></h1>

	<?php do_action( 'ib_educator_after_course_title' ); ?>

	<div class="course-content">
		<?php
			$status = '';

			if ( is_user_logged_in() ) {
				$status = $api->get_access_status( get_the_ID(), get_current_user_id() );
			}

			switch ( $status ) {
				case 'inprogress':
					echo '<div class="ibedu-message info">' . __( 'You are registered for this course.', 'ibeducator' ) . '</div>';
					break;

				case 'pending_entry':
					echo '<div class="ibedu-message info">' . __( 'Your registration for this course is pending.', 'ibeducator' ) . '</div>';
					break;

				case 'pending_payment':
					echo '<div class="ibedu-message info">' . __( 'Your payment for this course is pending.', 'ibeducator' ) . '</div>';
					break;

				//case 'course_complete':
				//case 'forbidden':
				default:
					$price = get_post_meta( get_the_ID(), '_ibedu_price', true );

					if ( ! $price ) $price = 0;
					?>
					<div class="ibedu-course-price">
						<span class="price"><?php echo ( 0 == $price ) ? __( 'Free', 'ibeducator' ) : ib_edu_format_course_price( $price ); ?></span>
						<a href="<?php echo esc_url( ib_edu_get_endpoint_url( 'edu-course', get_the_ID(), get_permalink( ib_edu_page_id( 'payment' ) ) ) ); ?>" class="ibedu-button"><?php _e( 'Register', 'ibeducator' ); ?></a>
					</div>
					<?php
			}

			do_action( 'ib_educator_before_course_content' );
			the_content();
		?>
	</div>

	<?php
		$api = IB_Educator::get_instance();
		$lessons_query = $api->get_lessons( get_the_ID() );
	?>

	<?php if ( $lessons_query && $lessons_query->have_posts() ) : ?>
	<section class="ibedu-lessons">
		<h2><?php _e( 'Lessons', 'incbeducator' ); ?></h2>
		<?php
			while ( $lessons_query->have_posts() ) {
				$lessons_query->the_post();
				IB_Educator_View::template_part( 'content', 'lesson' );
			}

			wp_reset_postdata();
		?>
	</section>
	<?php endif; ?>
</article>