<?php
// Used by all layout previews.
get_header();
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">

		<?php
		while ( have_posts() ) {
			the_post();
			the_content();
		}
?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
