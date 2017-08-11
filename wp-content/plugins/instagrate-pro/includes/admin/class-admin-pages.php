<?php

class Instagrate_Pro_Admin_Pages {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_sub_menus' ) );
		add_action( 'admin_menu', array( $this, 'remove_sub_menus' ) );
	}

	function add_sub_menus() {
		// moderation
		$awaiting_mod = instagrate_pro()->images->moderation_images_total();
		$title        = sprintf( __( 'Moderate %s' ), "<span class='awaiting-mod count-$awaiting_mod'><span class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span></span>" );
		add_submenu_page(
			'edit.php?post_type=' . INSTAGRATEPRO_POST_TYPE, 'Image Moderation', $title, 'manage_options', 'moderation', array(
				$this,
				'moderation_page'
			)
		);

		// settings
		add_submenu_page(
			'edit.php?post_type=' . INSTAGRATEPRO_POST_TYPE, 'Settings', 'Settings', 'manage_options', 'instagrate-pro-settings', array(
				$this,
				'settings_page'
			)
		);
	}

	function remove_sub_menus() {
		remove_submenu_page( 'edit.php?post_type=' . INSTAGRATEPRO_POST_TYPE, 'edit-tags.php?taxonomy=post_tag&amp;post_type=' . INSTAGRATEPRO_POST_TYPE );
	}

	function settings_page() {
		global $wpsfigp_settings;
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2><?php _e( 'Intagrate Settings', 'instagrate-pro' ) ?></h2>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $wpsfigp_settings as $tab ) { ?>
					<a href="?post_type=<?php echo INSTAGRATEPRO_POST_TYPE; ?>&page=<?php echo $_GET['page']; ?>&tab=<?php echo $tab['section_id']; ?>" class="nav-tab<?php echo $active_tab == $tab['section_id'] ? ' nav-tab-active' : ''; ?>">
						<?php echo $tab['section_title']; ?>
					</a>
				<?php } ?>
			</h2>

			<form action="options.php" method="post">
				<?php settings_fields( instagrate_pro()->settings->wpsf->get_option_group() ); ?>
				<?php instagrate_pro()->settings->do_settings_sections( instagrate_pro()->settings->wpsf->get_option_group() ); ?>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'instagrate-pro' ); ?>" />
				</p>
			</form>

		</div>
	<?php
	}

	function moderation_page() {
		include( INSTAGRATEPRO_PLUGIN_DIR . '/includes/admin/class-image-moderation-table.php' );

		$images_table = new Instagrate_Pro_Image_Moderation_Table();

		$images_table->prepare_items();
		?>
		<div class="wrap">
			<h2><?php _e( 'Image Moderation', 'instagrate-pro' ); ?></h2>

			<form id="igp-image-moderation" method="get" action="<?php echo admin_url( 'edit.php?post_type=' . INSTAGRATEPRO_POST_TYPE . '&page=moderation' ); ?>">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<input type="hidden" name="post_type" value="<?php echo INSTAGRATEPRO_POST_TYPE; ?>" />

				<?php
				$images_table->display();

				echo '<style type="text/css">';
				echo '.row-actions .pending a { color: #006505; }';
				echo '.row-actions .ignore a { color: #a00; }';
				echo '</style>';
				?>
			</form>
		</div>
	<?php
	}

}

new Instagrate_Pro_Admin_Pages();