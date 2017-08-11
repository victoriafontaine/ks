<?php

/**
 * Helper Class
 *
 * @package     instagrate-pro
 * @subpackage  helper
 * @copyright   Copyright (c) 2014, polevaultweb
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */
class Instagrate_Pro_Helper {

	function __construct() {
		add_filter( 'sanitize_file_name_chars', array( $this, 'add_to_sanitize' ) );
	}

	/**
	 * Load template
	 *
	 * @param        $template
	 * @param array  $args
	 * @param string $dir
	 */
	public function template( $template, $args = array(), $dir = '' ) {
		extract( $args, EXTR_OVERWRITE );
		$dir = ( ! empty( $dir ) ) ? trailingslashit( $dir ) : $dir;
		include INSTAGRATEPRO_PLUGIN_DIR . '/templates/' . $dir . $template . '.php';
	}

	/**
	 * Get setting or use default
	 *
	 * @param        $value
	 * @param string $default
	 * @param bool   $options
	 *
	 * @return string
	 */
	public static function setting( $value, $default = '', $options = false ) {
		if ( ! $options ) {
			$options = instagrate_pro()->settings->get_settings();
		}
		if ( ! is_array( $options ) ) {
			$options = (array) $options;
		}
		if ( ! isset( $options[ $value ] ) ) {
			return $default;
		} else {
			return $options[ $value ];
		}
	}

	/**
	 * Backwards compatible has_shortcode()
	 * @param        $content
	 * @param string $shortcode
	 *
	 * @return bool
	 */
	public function has_shortcode( $content, $shortcode = '' ) {
		if ( function_exists( 'has_shortcode' ) ) {
			return has_shortcode( $content, $shortcode );
		}
		$found = false;
		if ( ! $shortcode ) {
			return $found;
		}
		if ( stripos( $content, '[' . $shortcode ) !== false ) {
			$found = true;
		}

		return $found;
	}

	/**
	 * Get all published objects of a post type for a dropdown
	 *
	 * @param $post_type
	 *
	 * @return array
	 */
	public function get_post_objects( $post_type ) {
		global $post;
		$data = array();
		global $wpdb;
		$post_data = $wpdb->get_results(
			$wpdb->prepare(
				"	SELECT ID, post_title FROM $wpdb->posts
								WHERE post_status = 'publish'
								AND post_type = %s
								ORDER BY post_date desc 	", $post_type
			)
		);
		if ( $post_data ) {
			foreach ( $post_data as $post_item ) {
				$id          = $post_item->ID;
				$name        = $post_item->post_title;
				$data[ $id ] = $name;
			}
		}

		return $data;
	}

	/**
	 * Get all taxonomies of a post type for a dropdown
	 *
	 * @param string $post_type
	 *
	 * @return mixed
	 */
	public function get_all_taxonomies( $post_type = '' ) {
		$options[0] = '— ' . __( 'Select', 'instagrate-pro' ) . ' —';
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		foreach ( $taxonomies as $key => $tax ) {
			if ( $tax->hierarchical != 1 ) {
				continue;
			}
			$options[ $key ] = $tax->labels->singular_name;
		}

		return $options;
	}

	/**
	 * Get all terms of a taxonomy
	 *
	 * @param string $taxonomy
	 * @param bool   $select
	 *
	 * @return array
	 */
	public function get_all_terms( $taxonomy = '', $select = true ) {
		$options = array();
		if ( $select ) {
			$options[0] = '— ' . __( 'Select', 'instagrate-pro' ) . ' —';
		}
		if ( $taxonomy == '0' ) {
			return $options;
		}
		$terms = get_terms( $taxonomy, array( 'hide_empty' => 0 ) );
		if ( $terms ) {
			foreach ( $terms as $key => $term ) {
				if ( isset( $term->term_id ) ) {
					$options[ $term->term_id ] = $term->name;
				}
			}
		}

		return $options;
	}

	/**
	 * Get all non heirarchical taxonomies for a post type
	 *
	 * @param string $post_type
	 *
	 * @return mixed
	 */
	public function get_all_tag_taxonomies( $post_type = '' ) {
		$options[0] = '— ' . __( 'Select', 'instagrate-pro' ) . ' —';
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		foreach ( $taxonomies as $key => $tax ) {
			if ( $tax->hierarchical != 0 || $key == 'post_format' ) {
				continue;
			}
			$options[ $key ] = $tax->labels->name;
		}

		return $options;
	}

	/**
	 * Make sure hashtags have spaces between each other
	 *
	 * @param $caption
	 *
	 * @return mixed
	 */
	public function fix_tags_caption( $caption ) {
		$pattern = '/(\S)(\#)/i';
		$caption = preg_replace( $pattern, '\1 \2', $caption );

		return $caption;
	}

	/**
	 * Initially clean the caption by removing characters WP would do on DB insert
	 *
	 * @param $caption
	 *
	 * @return mixed
	 */
	public function clean_initial_caption( $caption ) {
		$caption = $this->remove_invalid_characters( $caption );
		$caption = $this->fix_tags_caption( $caption );

		return $caption;
	}

	/**
	 * Clean caption
	 *
	 * @param string $caption
	 * @param bool   $remove_line_breaks
	 *
	 * @return mixed|string
	 */
	public function clean_caption( $caption, $remove_line_breaks = true ) {
		if ( $remove_line_breaks ) {
			$caption = sanitize_text_field( $caption );
		} else {
			$caption = $this->sanitize_text_field( $caption );
		}

		$caption = igp_emoji_html_stripped( $caption );
		$caption = $this->remove_invalid_characters( $caption );
		$caption = trim( $caption );

		return $caption;
	}

	/**
	 * Bespoke sanitize method that doesn't remove line breaks
	 *
	 * @param $str
	 *
	 * @return mixed|string
	 */
	function sanitize_text_field( $str ) {
		$filtered = wp_check_invalid_utf8( $str );

		if ( strpos( $filtered, '<' ) !== false ) {
			$filtered = wp_pre_kses_less_than( $filtered );
			// This will strip extra whitespace for us.
			$filtered = wp_strip_all_tags( $filtered, true );
		}

		$found = false;
		while ( preg_match( '/%[a-f0-9]{2}/i', $filtered, $match ) ) {
			$filtered = str_replace( $match[0], '', $filtered );
			$found    = true;
		}

		if ( $found ) {
			// Strip out the whitespace that may now exist after removing the octets.
			$filtered = trim( preg_replace( '/ +/', ' ', $filtered ) );
		}

		return $filtered;
	}

	/**
	 * Clean the hashtag array of emojis etc from each tag
	 *
	 * @param $tags
	 *
	 * @return array
	 */
	function clean_tags( $tags ) {
		$clean_tags = array();
		foreach( $tags as $tag ) {
			$clean_tag = $this->remove_invalid_characters( $tag );
			$clean_tag = sanitize_text_field( $clean_tag );
			$clean_tag = igp_emoji_html_stripped( $clean_tag );

			$clean_tags[] = $clean_tag;
		}

		return $clean_tags;
	}

	/**
	 * Wrapper for removing invalid chars from caption
	 *
	 * @param $caption
	 *
	 * @return mixed
	 */
	function remove_invalid_characters( $caption ) {
		global $wp_version;
		if ( version_compare( $wp_version, '4.2', '<' ) ) {
			// Ignore for < WP 4.2
			return $caption;
		}

		global $wpdb;
		$table = instagrate_pro()->images->get_table_name();
		$charset = $wpdb->get_col_charset( $table, 'caption' );
		$caption = $this->clean_up_caption( $caption, $charset );

		return $caption;
	}

	/**
	 * Clean up any emoji unicode charatacters we haven't stripped
	 *
	 * @param string $caption
	 * @param string $charset
	 *
	 * @return mixed
	 */
	function clean_up_caption( $caption, $charset ) {
		// utf8 can be handled by regex, which is a bunch faster than a DB lookup.
		if ( ( 'utf8' === $charset || 'utf8mb3' === $charset || 'utf8mb4' === $charset ) && function_exists( 'mb_strlen' ) ) {
			$regex = '/
					(
						(?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
						|   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
						|   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
						|   [\xE1-\xEC][\x80-\xBF]{2}
						|   \xED[\x80-\x9F][\x80-\xBF]
						|   [\xEE-\xEF][\x80-\xBF]{2}';

			if ( 'utf8mb4' === $charset ) {
				$regex .= '
						|    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
						|    [\xF1-\xF3][\x80-\xBF]{3}
						|    \xF4[\x80-\x8F][\x80-\xBF]{2}
					';
			}

			$regex .= '){1,40}                          # ...one or more times
					)
					| .                                  # anything else
					/x';
			$caption = preg_replace( $regex, '$1', $caption );
		}

		return $caption;
	}

	/**
	 * Strip tags from a caption
	 *
	 * @param $caption
	 * @param $tags
	 *
	 * @return string
	 */
	public function caption_strip_tags( $caption, $tags ) {
		if ( $tags == '' ) {
			return $caption;
		}
		if ( $tags && is_array( $tags ) ) {
			foreach ( $tags as $key => $tag ) {
				$tag     = '#' . $tag;
				$pattern = '/' . $tag . '(\#|\Z|\s)/i';
				$caption = preg_replace( $pattern, '', $caption );
			}
		}

		return trim( $caption );
	}

	/**
	 * Helper function for building a select element
	 *
	 * @param        $name
	 * @param        $items
	 * @param string $default
	 * @param        $options
	 * @param string $class
	 */
	public static function metabox_select( $name, $items, $default = '', $options, $class = '' ) {
		$selected = Instagrate_Pro_Helper::setting( $name, $default, $options ); ?>
		<select name="_instagrate_pro_settings[<?php echo $name; ?>]" <?php echo ( $class != '' ) ? 'class="' . $class . '"' : ''; ?>>
			<?php
			foreach ( $items as $key => $value ) : ?>
				<option value="<?php echo $key; ?>" <?php selected( $selected, $key ); ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
	<?php
	}

	/**
	 * Remove query string from a url
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function strip_querysting( $url ) {
		if ( strpos( $url, '?' ) !== false ) {
			$url = substr( $url, 0, strpos( $url, '?' ) );
		}

		return $url;
	}

	/**
	 * Adds extra characters to the sanitize function
	 *
	 * @param $special_chars
	 *
	 * @return array
	 */
	public function add_to_sanitize( $special_chars ) {
		$special_chars[] = '%';

		return $special_chars;
	}

	/**
	 * Gets the true time from a timestamp
	 *
	 * @param $timestamp
	 *
	 * @return mixed
	 */
	public function get_instagram_time( $timestamp ) {
		// get datetime object from unix timestamp
		$datetime = new DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

		// set the timezone to the site timezone
		$datetime->setTimezone( new DateTimeZone( $this->wp_get_timezone_string() ) );

		// return the unix timestamp adjusted to reflect the site's timezone
		return $timestamp + $datetime->getOffset();
	}

	/**
	 * Gets true timezone
	 *
	 * @return string
	 */
	private function wp_get_timezone_string() {

		// if site timezone string exists, return it
		if ( $timezone = get_option( 'timezone_string' ) ) {
			return $timezone;
		}

		// get UTC offset, if it isn't set then return UTC
		if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
			return 'UTC';
		}

		// adjust UTC offset from hours to seconds
		$utc_offset *= 3600;

		// attempt to guess the timezone string from the UTC offset
		$timezone = timezone_name_from_abbr( '', $utc_offset );

		// last try, guess timezone string manually
		if ( false === $timezone ) {

			$is_dst = date( 'I' );

			foreach ( timezone_abbreviations_list() as $abbr ) {
				foreach ( $abbr as $city ) {
					if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
						return $city['timezone_id'];
					}
				}
			}
		}

		// fallback to UTC
		return 'UTC';
	}

	/**
	 * Prepare tags array from filter input
	 *
	 * @param $tags
	 *
	 * @return array|mixed
	 */
	function prepare_tags( $tags) {
		if ( $tags != '' ) {
			$tags     = str_replace( ' ', '', $tags );
			$tags     = str_replace( '#', '', $tags );
			$tags     = explode( ',', $tags );
		}

		return $tags;
	}

	/**
	 * Get first tag from tag string used in account hashtag filter
	 *
	 * @param $tags
	 *
	 * @return string
	 */
	public function get_first_tag( $tags ) {
		$tag_name = '';
		if ( $tags != '' ) {
			$tag_name = $tags[0];
		}

		return $tag_name;
	}

	/**
	 * Render the Cron Job text and URL for the settings or an account
	 *
	 * @return string
	 */
	public static function get_cron_job_html() {
		$help_url  = 'https://intagrate.io/docs/setting-cron-job/';
		$cron_link = sprintf( '<a target="_blank" href="%s">%s</a>', $help_url, __( 'UNIX Cron job', 'instagrate-pro' ) );

		global $post;
		$id = '';
		if ( isset( $post->ID ) ) {
			$id = '&account_id=' . $post->ID;
		}
		$cron_url = get_admin_url() . 'admin-ajax.php?action=instagrate' . $id;

		$html = '<p>';
		$html .= sprintf( __( 'You can set up a %s on your server to post images from accounts with the Posting Frequency of Cron Job  using the this url:', 'instagrate-pro' ), $cron_link );
		$html .= '</p><code>' . $cron_url . '</code>';

		return $html;
	}

	/**
	 * Get the URL to view the diagnostic log
	 *
	 * @param string $tab
	 * @param array       $args
	 *
	 * @return string
	 */
	public static function get_setting_url( $tab = 'general', $args = array() ) {
		$defaults = array(
			'post_type'  => INSTAGRATEPRO_POST_TYPE,
			'page'       => 'instagrate-pro-settings',
			'tab'        => $tab,
		);

		$args = array_merge( $defaults, $args );
		$url = admin_url( 'edit.php' );
		$url = add_query_arg( $args, $url );

		return esc_url( $url );
	}
}
 