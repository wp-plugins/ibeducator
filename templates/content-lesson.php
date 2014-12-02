<article class="ib-edu-lesson">
	<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>

	<?php if ( has_excerpt() ) : ?>
	<div class="excerpt">
		<?php the_excerpt(); ?>
	</div>
	<?php endif; ?>

	<?php
		if ( ib_edu_has_quiz( get_the_ID() ) ) {
			echo '<div class="ib-edu-lesson-meta"><span class="quiz">' . __( 'Quiz', 'ibeducator' ) . '</span></div>';
		}
	?>
</article>