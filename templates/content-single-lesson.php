<article id="lesson-<?php the_ID(); ?>" <?php post_class( 'ib-edu-lesson-single' ); ?>>
	<h1 class="lesson-title entry-title"><?php the_title(); ?></h1>

	<div id="ib-edu-breadcrumbs"><?php ib_edu_breadcrumbs(); ?></div>

	<div class="lesson-content">
		<?php
			if ( ib_edu_student_can_study( get_the_ID() ) ) {
				the_content();
				IB_Educator_View::template_part( 'quiz' );
			} else {
				echo '<p>';
				printf(
					__( 'Please register for the %s to view this lesson.', 'incbeducator' ),
					'<a href="' . esc_url( get_permalink( ib_edu_get_course_id() ) ) . '">' . __( 'course', 'ibeducator' ) . '</a>'
				);
				echo '</p>';
			}
		?>
	</div>
</article>