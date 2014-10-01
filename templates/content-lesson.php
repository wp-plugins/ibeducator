<article class="ibedu-lesson">
	<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>

	<?php if ( has_excerpt() ) : ?>
	<div class="excerpt">
		<?php the_excerpt(); ?>
	</div>
	<?php endif; ?>
</article>