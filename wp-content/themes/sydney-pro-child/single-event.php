<?php

/*
WP Post Template: Events Template
*/

get_header('featured-image'); ?>

<div id="primary" class="content-area col-md-9 events-page">
  <main id="main" class="post-wrap" role="main">

    <?php while ( have_posts() ) : the_post(); ?>
      <div class="events-header">
        <h2 class="events-title"><?php the_title(); ?></h2>
        <span class="events-location"><i class="fa fa-map-marker" aria-hidden="true"> - </i>
        <?php the_field('location'); ?> | 
          <i class="fa fa-calendar" aria-hidden="true"> - </i>
          <?php the_field('date'); ?></span>
        </div>

          <div class="event-img"><?php the_post_thumbnail('large-thumb'); ?></div>

        
        <div class="event-description"><?php the_content(); ?></div>

        <?php
        if(get_field('add_button'))
        {
          echo '<a class="more-btn" href="' . get_field('btn_url') . '" target="_blank">' . get_field('btn_label') . '</a>';
        }
        ?>


      <?php endwhile; // end of the loop. ?>

    </main><!-- #main -->
  </div><!-- #primary -->

  <?php get_sidebar(); ?>
  <?php get_footer(); ?>



