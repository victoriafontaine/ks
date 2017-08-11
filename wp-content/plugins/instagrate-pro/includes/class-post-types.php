<?php

class Instagrate_Pro_Post_Types {

	public $post_type = INSTAGRATEPRO_POST_TYPE;

	public function __construct() {

		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		add_action( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'edit_columns' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'custom_columns' ) );
		add_filter( 'admin_init', array( $this, 'remove_media_button' ) );
		add_filter( 'bulk_actions-edit-' . $this->post_type, array( $this, 'remove_bulk_edit' ) );
		add_filter( 'enter_title_here', array( $this, 'custom_enter_title_here' ) );
	}

	private function labels() {
		return array(
			'name'               => _x( 'Intagrate Accounts', 'post type general name' ),
			'singular_name'      => _x( 'Account', 'post type singular name' ),
			'add_new'            => __( 'Add Account', 'instagrate-pro' ),
			'all_items'          => __( 'All Accounts', 'instagrate-pro' ),
			'add_new_item'       => __( 'Add New Account', 'instagrate-pro' ),
			'edit_item'          => __( 'Edit Account', 'instagrate-pro' ),
			'new_item'           => __( 'New Account', 'instagrate-pro' ),
			'view_item'          => __( 'View Account', 'instagrate-pro' ),
			'search_items'       => __( 'Search Accounts', 'instagrate-pro' ),
			'not_found'          => __( 'No Accounts found', 'instagrate-pro' ),
			'not_found_in_trash' => __( 'No Accounts found in Trash', 'instagrate-pro' ),
			'menu_name'          => 'Intagrate',
		);
	}

	public function register() {
		register_post_type(
			$this->post_type,
			array(
				'labels'        => $this->labels(),
				'public'        => false,
				'show_ui'       => true,
				'menu_position' => 100,
				'supports'      => array( 'title', 'editor', 'custom-fields', 'thumbnail', 'excerpt' ),
				'taxonomies'    => array( 'post_tag' ),
				'menu_icon'     => 'dashicons-camera' //INSTAGRATEPRO_PLUGIN_URL . 'assets/img/favicon.png'
			)
		);
		if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) && get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'user_can_richedit', array( $this, 'disable_visual_editor' ) );
		}
	}

	public function disable_visual_editor( $default ) {
		global $post;
		if ( $this->post_type == get_post_type( $post ) ) {
			return false;
		}

		return $default;
	}

	public function updated_messages( $messages ) {
		global $post, $post_ID;

		$instagrate_messages = array(
			0  => '',
			1  => __( 'Account updated.', 'instagrate-pro' ),
			2  => __( 'Custom field updated.', 'instagrate-pro' ),
			3  => __( 'Custom field deleted.', 'instagrate-pro' ),
			4  => __( 'Account updated.', 'instagrate-pro' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Account restored to revision from %s', 'instagrate-pro' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Account published.', 'instagrate-pro' ),
			7  => __( 'Account saved.', 'instagrate-pro' ),
			8  => __( 'Account submitted.', 'instagrate-pro' ),
			9  => sprintf(
				__( 'Account scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Account</a>', 'instagrate-pro' ),
				date_i18n( __( 'M j, Y @ G:i', 'instagrate-pro' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) )
			),
			10 => __( 'Account draft updated.', 'instagrate-pro' ),
			11 => __( 'Instagram account connected.', 'instagrate-pro' ),
			12 => __( 'Instagram account disconnected.', 'instagrate-pro' ),
			13 => __( 'You did not authorise your Instagram account with this plugin.', 'instagrate-pro' ),
			14 => __( 'Account duplicated.', 'instagrate-pro' ),
			15 => __( 'Likes have been synced for this account successfully.', 'instagrate-pro' ),
			16 => __( 'Comments have been synced for this account successfully.', 'instagrate-pro' )
		);

		$messages[$this->post_type] = $instagrate_messages;

		return $messages;
	}

	function edit_columns( $columns ) {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'profile-img'     => __( '', 'instagrate-pro' ),
			'profile'         => __( 'Profile', 'instagrate-pro' ),
			'images'          => __( 'Media', 'instagrate-pro' ),
			'frequency'       => __( 'Frequency', 'instagrate-pro' ),
			'multiple-images' => __( 'Multiple Media', 'instagrate-pro' ),
			'type'            => __( 'Type', 'instagrate-pro' ),
			'imagesaving'     => __( 'Media Saving', 'instagrate-pro' ),
			'classification'  => __( 'Classification', 'instagrate-pro' ),
			'status'          => __( 'Status', 'instagrate-pro' ),
			'title'           => __( 'Custom Title', 'instagrate-pro' ),
			'igp-actions'     => __( 'Actions', 'instagrate-pro' ),
		);

		return $columns;
	}

	function custom_columns( $column ) {
		// todo default value
		// todo get images key
		// get location name
		global $post;
		$options      = get_post_meta( $post->ID, '_instagrate_pro_settings', true );
		$profile_src  = INSTAGRATEPRO_PLUGIN_URL . 'assets/img/not-connected.png';
		$profile_name = 'Not connected';
		if ( Instagrate_Pro_Helper::setting( 'token', '', $options ) != '' ) {
			$profile_name = $options['username'];
		}
		if ( Instagrate_Pro_Helper::setting( 'user_thumb', '', $options ) != '' ) {
			$profile_src = $options['user_thumb'];
		}
		$edit_url = get_admin_url() . 'post.php?post=' . $post->ID . '&amp;action=edit';
		switch ( $column ) {
			case 'profile-img':
				?>
				<a href="<?php echo $edit_url; ?>">
					<img src="<?php echo $profile_src; ?>" width="30" height="30" alt="<?php echo $profile_name; ?>">
				</a>
				<?php
				break;
			case 'profile':
				?>
				<strong>
					<a href="<?php echo $edit_url; ?>" title="Edit Account">
						<?php echo $profile_name ?>
					</a>
				</strong>
				<br />
				<?php
				echo instagrate_pro()->accounts->get_images_key( $post->ID, true );
				break;
			case 'images':
				$posting = Instagrate_Pro_Helper::setting( 'instagram_images', '', $options );
				if ( $posting == '' ) {
					$posting_text = __( 'Not configured', 'instagrate-pro' );
				} else {
					$posting_text = ucfirst( $posting ) . __( ' Media', 'instagrate-pro' );
					if ( $posting == 'users' && Instagrate_Pro_Helper::setting( 'instagram_user', '', $options ) != '' && Instagrate_Pro_Helper::setting( 'instagram_users_id', '', $options ) != '' ) {
						$posting_text .= '<br/>' . __( 'User', 'instagrate-pro' ) . ': <strong>' . Instagrate_Pro_Helper::setting( 'instagram_user', '', $options ) . '</strong>';
					}
					if ( $posting == 'location' && Instagrate_Pro_Helper::setting( 'instagram_location', '', $options ) != '' && Instagrate_Pro_Helper::setting( 'instagram_location_id', '', $options ) != '' ) {
						$posting_text .= '<br/><strong>' . instagrate_pro()->accounts->get_location_name( $post->ID, Instagrate_Pro_Helper::setting( 'instagram_location_id', '', $options ) ) . '</strong>';
					}

					$filter = Instagrate_Pro_Helper::setting( 'instagram_hashtags', '', $options );
					if ( $filter != '' ) {
						$posting_text .= '<br/>' . __( 'Filter', 'instagrate-pro' ) . ': <strong>' . $filter . '</strong>';
					}
				}
				echo $posting_text;
				break;
			case 'frequency':
				$frequency = Instagrate_Pro_Helper::setting( 'posting_frequency', 'constant', $options );
				$frequency = ucfirst( $frequency );
				if ( $frequency == 'Schedule' ) {
					$frequency .= ' - ' . instagrate_pro()->scheduler->get_all_schedules( Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $options ) );
					$frequency .= '<br/><div class="curtime"><span id="timestamp">' . __( 'Next', 'instagrate-pro' ) . ': <b>' . instagrate_pro()->scheduler->get_next_schedule( $post->ID, Instagrate_Pro_Helper::setting( 'posting_schedule', 'igp_daily', $options ) ) . '</b></span></div>';
				}
				echo $frequency;
				break;
			case 'multiple-images':
				$multiple = Instagrate_Pro_Helper::setting( 'posting_multiple', 'each', $options );
				switch ( $multiple ) {
					case 'each':
						$multiple_text = __( 'Post Per Media', 'instagrate-pro' );
						break;
					case 'group':
						$multiple_text = __( 'Media Grouped', 'instagrate-pro' );
						break;
					case 'single':
						$type      = Instagrate_Pro_Helper::setting( 'post_type', 'post', $options );
						$same_post = Instagrate_Pro_Helper::setting( 'posting_same_post', '', $options );
						if ( $same_post != '' ) {
							$same_post = get_post( $same_post );
							$same_post = $same_post->post_title;
							$same_post = '<br/><strong>' . $same_post . '</strong>';
						}
						$multiple_text = __( 'Same', 'instagrate-pro' ) . ' ' . ucfirst( $type ) . $same_post;
						break;
				}
				echo $multiple_text;
				break;
			case 'imagesaving':
				$feat   = ( Instagrate_Pro_Helper::setting( 'post_featured_image', 'off', $options ) == 'on' ) ? '<br/>Featured Image' : '';
				$saving = ( Instagrate_Pro_Helper::setting( 'post_save_media', 'off', $options ) == 'on' ) ? __( 'Media Library', 'instagrate-pro' ) . $feat : __( 'Instagram Media', 'instagrate-pro' );
				echo $saving;
				break;
			case 'classification':
				$tax = ( Instagrate_Pro_Helper::setting( 'post_taxonomy', '0', $options ) != '0' ) ? ucwords( Instagrate_Pro_Helper::setting( 'post_taxonomy', '0', $options ) ) : '';
				if ( $tax != '' ) {
					$terms = Instagrate_Pro_Helper::setting( 'post_term', array(), $options );
					if ( ! is_array( $terms ) ) {
						$terms = (array) $terms;
					}
					$term_text = '';
					if ( $terms && count( $terms ) > 0 ) {
						foreach ( $terms as $term_selected ) {
							$term_add = get_term( $term_selected, Instagrate_Pro_Helper::setting( 'post_taxonomy', '0', $options ) );
							if ( ! is_wp_error( $term_add ) ) {
								$term_text .= $term_add->name . ', ';
							}
						}
						if ( substr( $term_text, - 2 ) == ', ' ) {
							$term_text = substr( $term_text, 0, - 2 );
						}
					} else {
						$term_text = 'Not Selected';
					}

					echo __( 'Taxonomy', 'instagrate-pro' ) . ': <strong>' . $tax . '</strong><br/>' . __( 'Terms', 'instagrate-pro' ) . ': <strong>' . $term_text . '</strong>';
				} else {
					_e( 'None', 'instagrate-pro' );
				}
				break;
			case 'type':
				$type = Instagrate_Pro_Helper::setting( 'post_type', 'post', $options );
				echo ucfirst( $type );
				break;
			case 'status':
				$type = Instagrate_Pro_Helper::setting( 'post_status', 'publish', $options );
				echo ucfirst( $type );
				break;
			case 'igp-actions':
				$actions = '<a class="igp-duplicate" title="Duplicate Account" rel="' . $post->ID . '" href="#">Duplicate</a>';
				$actions .= '<p><strong>Sync:</strong></p>';
				$actions .= '<a class="igp-sync-likes" title="Sync Likes" rel="' . $post->ID . '" href="#">Likes</a>';
				if ( Instagrate_Pro_Helper::setting( 'igpsettings_comments_enable-comments', '0' ) == 1 ) {
					$actions .= ' | <a class="igp-sync-comments" title="Sync Comments" rel="' . $post->ID . '" href="#">Comments</a>';
				}
				echo $actions;
				break;
		}
	}

	function remove_media_button() {
		global $post;
		if ( ( isset( $post->post_type ) && $post->post_type == $this->post_type ) ||
			 ( isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->post_type )
		) {
			remove_all_actions( 'media_buttons' );
			add_action( 'media_buttons', array( $this, 'custom_content_header' ) );
		}
	}

	function remove_bulk_edit( $actions ) {
		unset( $actions['edit'] );

		return $actions;
	}

	function custom_content_header() {
		$link_text = __( 'here', 'instagrate-pro' );
		$link      = ' <a target="_blank" style="color: #21759B" href="https://intagrate.io/docs/template-tags/">' . $link_text . '</a>';
		$title     = __( 'You can find examples of template tag usage', 'instagrate-pro' ) . $link;
		echo $title;
	}

	function custom_enter_title_here( $title ) {
		$screen = get_current_screen();
		if ( $this->post_type == $screen->post_type ) {
			$title = __( 'Enter your custom title', 'instagrate-pro' ) . ', eg. %%caption%% ' . __( 'for just the Instagram image caption', 'instagrate-pro' );
		}

		return $title;
	}

}