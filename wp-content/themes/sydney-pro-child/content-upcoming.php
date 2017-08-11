<?php
/**
 * @package Sydney
 */
?>

<article class="upcoming" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php do_action('sydney_inside_top_post'); ?>
  <div class="col-md-5 col-sm-6">
   <div class="post-thumbnail">
    <?php the_post_thumbnail('large-thumb'); ?>
  </div>
</div>
<div class="col-md-7 col-sm-6">
  <div class="header">
    <?php the_title( '<h1 class="upcoming title-post entry-title">', '</h1>' ); ?>
    <span class="events-location"><i class="fa fa-map-marker" aria-hidden="true"> - </i>
      <?php the_field('location'); ?> | 
      <i class="fa fa-calendar" aria-hidden="true"> - </i>
      <?php the_field('date'); ?>
    </span>
  </div>
  <div class="entry-content">
    <?php the_excerpt(); ?>
  </div><!-- .entry-content -->

  <?php
  if(get_field('add_button'))
  {
    echo '<a class="more-btn" href="' . get_field('btn_url') . '" target="_blank">' . get_field('btn_label') . '</a>';
  }
  ?>

</div>
</article><!-- #post-## -->
