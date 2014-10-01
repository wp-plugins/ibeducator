<article id="lesson-<?php the_ID(); ?>" <?php post_class( 'ibedu-lesson-single' ); ?>>
	<?php if ( apply_filters( 'ibedu_show_single_lesson_title', true ) ) : ?>
	<h1 class="lesson-title entry-title"><?php the_title(); ?></h1>
	<?php endif; ?>

	<div id="ibedu-breadcrumbs"><?php ibedu_breadcrumbs(); ?></div>

	<div class="lesson-content">
		<?php
			if ( ibedu_can_study( get_the_ID() ) ) {
				the_content();
				IBEdu_View::template_part( 'quiz' );
			} else {
				echo '<p>';
				printf(
					__( 'Please register for the %s to view this lesson.', 'incbeducator' ),
					'<a href="' . esc_url( get_permalink( ibedu_lesson_course_id() ) ) . '">' . __( 'course', 'ibeducator' ) . '</a>'
				);
				echo '</p>';
			}
		?>
	</div>
</article>