<?php

/*
Template Name:  Press Page
*/

get_header(); ?>

<div id="primary" class="fp-content-area">
<main id="main" class="site-main" role="main">

<div class="entry-content">
<?php while ( have_posts() ) : the_post(); ?>
<?php the_content(); ?>
<?php endwhile; ?>
</div><!-- .entry-content -->


<div id="fullpress" class="force-full-width siteorigin-panels-stretch panel-row-style panel-row-style-for-1325-0" data-stretch-type="full-stretched">
<div class="col-sm-3 col-md-2 hidden-xs press-filter" data-spy="affix" data-offset-top="197">
<?php echo do_shortcode('[ess_grid_nav id="filter"  alias="press"]'); ?>
</div>
<div class="col-sm-9 col-md-10 press-grid">
<?php echo do_shortcode('[ess_grid alias="press"]'); ?>
</div>
</div>

</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
