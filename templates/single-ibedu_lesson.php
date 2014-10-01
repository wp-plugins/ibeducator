<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php get_header( 'ibeducator' ); ?>

<?php
	/**
	 * Add HTML before output of educator's content.
	 */
	do_action( 'ibedu_before_main_loop' );
?>

<?php while ( have_posts() ) : the_post(); ?>
<?php IBEdu_View::template_part( 'content', 'single-lesson' ); ?>
<?php endwhile; ?>

<?php
	/**
	 * Add HTML after output of educator's content.
	 */
	do_action( 'ibedu_after_main_loop' );
?>

<?php
	/**
	 * Add sidebar.
	 */
	do_action( 'ibedu_sidebar' );
?>

<?php get_footer( 'ibeducator' ); ?>