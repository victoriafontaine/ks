<?php

/**
 * Template Tags Class
 *
 * @package     instagrate-pro
 * @subpackage  template-tags
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Template_Tags {

	private $tag_sep = '%%';

	private $template_image;
	private $template_video;
	private $template_caption;
	private $template_caption_escaped;
	private $template_caption_tags_no_hash;
	private $template_caption_tags_no_hash_escaped;
	private $template_caption_no_tags;
	private $template_caption_no_tags_escaped;
	private $template_caption_no_usernames;
	private $template_caption_no_usernames_escaped;
	private $template_caption_no_usernames_no_tags;
	private $template_caption_no_usernames_no_tags_escaped;
	private $template_tags;
	private $template_tags_first;
	private $template_username;
	private $template_user_profile_url;
	private $template_user_profile_image_url;
	private $template_instagram_media_type;
	private $template_instagram_url;
	private $template_instagram_image_url;
	private $template_instagram_video_url;
	private $template_wordpress_image_url;
	private $template_wordpress_video_url;
	private $template_wordpress_post_url;
	private $template_map;
	private $template_location_lat;
	private $template_location_lng;
	private $template_location_name;
	private $template_date;
	private $template_filter;
	private $template_likes;
	private $template_instagram_media_id;
	private $template_instagram_embed_url;
	private $template_image_class;

	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'custom_quicktags' ) );
		}
	}

	/**
	 * Definition of all the tags
	 *
	 * @return array
	 */
	private function all_tags() {
		$template_tags = array(
			array(
				'name'         => 'image',
				'desc'         => __( 'The image url, either direct from Instagram or from the WP media library.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_image
			),
			array(
				'name'         => 'video',
				'desc'         => __( 'The video url, either direct from Instagram or from the WP media library.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_video
			),
			array(
				'name'         => 'caption',
				'desc'         => __( 'The image caption from Instagram including tags.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption
			),
			array(
				'name'         => 'caption-escaped',
				'desc'         => __( 'The image caption from Instagram including tags, escaped for use in HTML attributes like title and alt.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_escaped
			),
			array(
				'name'         => 'caption-tags-no-hash',
				'desc'         => __( 'The image caption from Instagram including tags without the #.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_tags_no_hash
			),
			array(
				'name'         => 'caption-tags-no-hash-escaped',
				'desc'         => __( 'The image caption from Instagram including tags without the #, escaped for use in HTML attributes like title and alt.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_tags_no_hash_escaped
			),
			array(
				'name'         => 'caption-no-tags',
				'desc'         => __( 'The image caption from Instagram excluding tags.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_no_tags
			),
			array(
				'name'         => 'caption-no-tags-escaped',
				'desc'         => __( 'The image caption from Instagram excluding tags, escaped for use in HTML attributes like title and alt.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_no_tags_escaped
			),
			array(
				'name'         => 'caption-no-usernames',
				'desc'         => __( 'The image caption from Instagram excluding username mentions.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_no_usernames
			),
			array(
				'name'         => 'caption-no-usernames-escaped',
				'desc'         => __( 'The image caption from Instagram excluding username mentions, escaped for use in HTML attributes like title and alt.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_no_usernames_escaped
			),
			array(
				'name'         => 'caption-no-usernames-no-tags',
				'desc'         => __( 'The image caption from Instagram excluding tags and username mentions.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_no_usernames_no_tags
			),
			array(
				'name'         => 'caption-no-usernames-no-tags-escaped',
				'desc'         => __( 'The image caption from Instagram excluding tags and username mentions, escaped for use in HTML attributes like title and alt.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_caption_no_usernames_no_tags_escaped
			),
			array(
				'name'         => 'tags',
				'desc'         => __( 'The image tags from Instagram', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_tags
			),
			array(
				'name'         => 'tags-first',
				'desc'         => __( 'The first image tag from Instagram', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_tags_first
			),
			array(
				'name'         => 'username',
				'desc'         => __( 'The username of who posted the image on Instagram.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_username
			),
			array(
				'name'         => 'instagram-user-profile-url',
				'desc'         => __( 'The link to the Instagram profile of who posted the image.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_user_profile_url
			),
			array(
				'name'         => 'instagram-user-profile-image-url',
				'desc'         => __( 'The link to the Instagram profile image of who posted the image.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_user_profile_image_url
			),
			array(
				'name'         => 'instagram-media-type',
				'desc'         => __( 'The type of Instagram media, i.e. image or video.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_instagram_media_type
			),
			array(
				'name'         => 'instagram-media-id',
				'desc'         => __( 'The ID of the Instagram media', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_instagram_media_id
			),
			array(
				'name'         => 'instagram-url',
				'desc'         => __( 'The link to the Instagram media page.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_instagram_url
			),
			array(
				'name'         => 'instagram-image-url',
				'desc'         => __( 'The link to the Instagram image.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_instagram_image_url
			),
			array(
				'name'         => 'instagram-video-url',
				'desc'         => __( 'The link to the Instagram video.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_instagram_video_url
			),
			array(
				'name'         => 'instagram-embed-url',
				'desc'         => __( 'The Instagram embed link for images and videos.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_instagram_embed_url
			),
			array(
				'name'         => 'wordpress-image-url',
				'desc'         => __( 'The link to the image if saved in the WP media library.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_wordpress_image_url
			),
			array(
				'name'         => 'wordpress-video-url',
				'desc'         => __( 'The link to the video if saved in the WP media library.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_wordpress_video_url
			),
			array(
				'name'         => 'wordpress-post-url',
				'desc'         => __( 'The link to the post created in WP.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_wordpress_post_url
			),
			array(
				'name'         => 'gallery',
				'desc'         => __( 'A gallery of all images that have been saved to the WP media library for the post.', 'instagrate-pro' ),
				'exclude_from' => array( 'title', 'meta' ),
				'value'        => '[gallery]'
			),
			array(
				'name'         => 'map',
				'desc'         => __( 'A Google map of the location of a geo tagged image.', 'instagrate-pro' ),
				'exclude_from' => array( 'title', 'meta' ),
				'value'        => $this->template_map
			),
			array(
				'name'         => 'location-name',
				'desc'         => __( 'The name of the location of a geo tagged image.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_location_name
			),
			array(
				'name'         => 'location-lat',
				'desc'         => __( 'The latitude of the location of a geo tagged image.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_location_lat
			),
			array(
				'name'         => 'location-lng',
				'desc'         => __( 'The longitude of the location of a geo tagged image.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_location_lng
			),
			array(
				'name'         => 'image-date',
				'desc'         => __( 'The date the image was taken on Instagram.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_date
			),
			array(
				'name'         => 'filter',
				'desc'         => __( 'The Instagram filter used on the image.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_filter
			),
			array(
				'name'         => 'likes',
				'desc'         => __( 'The Instagram Likes count for the image.', 'instagrate-pro' ),
				'exclude_from' => array(),
				'value'        => $this->template_likes
			),
			array(
				'name'         => 'image-class',
				'desc'         => __( 'WordPress image class to enable responsive images if saved in the WP media library.', 'instagrate-pro' ),
				'exclude_from' => array( 'title' ),
				'value'        => $this->template_image_class
			),

		);

		return $template_tags;
	}

	/**
	 * Set the value for the tag
	 *
	 * @param $tag
	 * @param $value
	 */
	public function set_tag( $tag, $value ) {
		$tag        = 'template_' . $tag;
		$this->$tag = $value;
	}

	/**
	 * Clear all of the tag values
	 */
	public function clear_values() {
		$properties = get_object_vars( $this );
		foreach ( $properties as $property => $value ) {
			if ( substr( $property, 0, 9 ) == 'template_' ) {
				$this->$property = '';
			}
		}
	}

	/**
	 * Get a subset of the tags based on a filter value
	 * The return value gets passed through a filter e.g. igp_template_caption
	 *
	 * @param string $filter - title, meta, content
	 *
	 * @return array
	 */
	public function get_template_tags( $filter = '' ) {

		$template_tags = $this->all_tags();

		if ( $filter != '' ) {
			foreach ( $template_tags as $tag_key => $tag ) {
				if ( ! empty( $tag['exclude_from'] ) ) {
					foreach ( $tag['exclude_from'] as $key => $exclude ) {
						if ( $exclude == $filter ) {
							unset( $template_tags[ $tag_key ] );
						}
					}
				}
			}
		}

		// Apply filters to template tags
		foreach ( $template_tags as $tag_key => $tag ) {
			$filter_name                        = 'igp_template_' . str_replace( '-', '_', $tag['name'] );
			$filter_value                       = $tag['value'];
			$template_tags[ $tag_key ]['value'] = apply_filters( $filter_name, $filter_value );
		}

		return $template_tags;
	}

	/**
	 * Replaces tag placeholders with tag values in some content
	 *
	 * @param $text
	 * @param $tags
	 * @param $default
	 *
	 * @return mixed
	 */
	public function replace_template_tags( $text, $tags, $default ) {
		foreach ( $tags as $tag_key => $tag ) {
			$find_text    = $this->tag_sep . $tag['name'] . $this->tag_sep;
			$replace_text = $tag['value'];
			$text         = str_replace( $find_text, $replace_text, $text );
		}
		if ( $text == '' ) {
			$text = $default;
		}

		return $text;
	}

	/**
	 * Adds the tag names to the Text tab of the content editor for accounts
	 */
	public function custom_quicktags() {
		global $post;
		if ( ( isset( $post->post_type ) && $post->post_type == INSTAGRATEPRO_POST_TYPE ) ) {
			if ( have_posts() ) {
				return;
			}
			$template_tags = $this->get_template_tags();
			$html          = '<script type="text/javascript">' . " \n";
			$count         = 1;
			foreach ( $template_tags as $tag ) {
				$slug     = 'igp_' . str_replace( '-', '_', $tag['name'] );
				$title    = ucwords( str_replace( '-', ' ', $tag['name'] ) );
				$tag_name = $this->tag_sep . $tag['name'] . $this->tag_sep;
				$num      = 200 + $count;
				$html .= "QTags.addButton( '" . $slug . "', '" . $title . "', '" . $tag_name . "', '', '', '" . $tag['desc'] . "', " . $num . "); \n";
				$count ++;
			}
			$html .= '</script>';
			echo $html;
		}
	}

	public function strip_mentions( $caption ){
		$caption = preg_replace( "/@(\w+)/", "", $caption );

		return $caption;
	}
}