<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package Sydney
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
<?php if ( get_theme_mod('site_favicon') ) : ?>
	<link rel="shortcut icon" href="<?php echo esc_url(get_theme_mod('site_favicon')); ?>" />
<?php endif; ?>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php do_action('sydney_before_site'); //Hooked: sydney_preloader() ?>

<div id="page" class="hfeed site">
	<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'sydney' ); ?></a>

	<?php sydney_contact_info(); ?>

	<?php do_action('sydney_before_header'); //Hooked: sydney_header_clone() ?>

	<header id="masthead" class="site-header" role="banner">
		<div class="header-wrap">
            <div class="container">
                <div class="row">
				<div class="col-md-4 col-sm-8 col-xs-12">
		        <?php if ( get_theme_mod('site_logo') ) : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php bloginfo('name'); ?>"><img class="site-logo" src="<?php echo esc_url(get_theme_mod('site_logo')); ?>" alt="<?php bloginfo('name'); ?>" /></a>
		        <?php else : ?>
					<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
					<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>	        
		        <?php endif; ?>
				</div>
				<div class="col-md-8 col-sm-4 col-xs-12">
					<div class="btn-menu"></div>
					<nav id="mainnav" class="mainnav" role="navigation">
						<?php wp_nav_menu( array( 'theme_location' => 'primary', 'fallback_cb' => 'sydney_menu_fallback' ) ); ?>
					</nav><!-- #site-navigation -->
				</div>
				</div>
			</div>
		</div>
	</header><!-- #masthead -->

	<?php do_action('sydney_after_header'); ?>	

	<?php $featured_image = wp_get_attachment_url(get_post_thumbnail_id($post->ID)); ?>
	<div class="header-image single-header-image" style="background-image:url('<?php echo $featured_image; ?>');">
		<?php sydney_header_overlay(); ?>
		<img class="header-inner" src="<?php echo $featured_image; ?>"/>
	</div>

	<?php do_action('sydney_after_hero'); ?>
	
	<?php if ( is_active_sidebar( 'sidebar-header' ) && (!is_front_page() || ( is_front_page() && get_theme_mod('hide_bh_widgets') != 1 ) ) ) : ?>
    <div class="header-widgets">
	    <div class="container">
	        <?php dynamic_sidebar( 'sidebar-header' ); ?>
	    </div>
    </div>
    <?php endif; ?>   

	<div id="content" class="page-wrap">
		<div class="content-wrapper container">
			<div class="row">	