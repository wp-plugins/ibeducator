<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php get_header( 'ibeducator' ); ?>

<?php
	/**
	 * Add HTML before output of educator's content.
	 */
	do_action( 'ibedu_before_main_loop', 'archive' );
?>

<header class="page-header">
	<h1 class="page-title">
		<?php _e( 'Lessons', 'ibeducator' ); ?>
	</h1>
</header>

<?php while ( have_posts() ) : the_post(); ?>
<?php get_template_part( 'content', get_post_format() ); ?>
<?php endwhile; ?>

<?php
	/**
	 * Add HTML after output of educator's content.
	 */
	do_action( 'ibedu_after_main_loop', 'archive' );
?>

<?php
	/**
	 * Add sidebar.
	 */
	do_action( 'ibedu_sidebar' );
?>

<?php get_footer( 'ibeducator' ); ?>