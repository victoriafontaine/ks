<?php

global $wpsfigp_settings;

// General Settings section
$wpsfigp_settings[] = array(
    'section_id' => 'general',
    'section_title' => 'Global Settings',
    'section_order' => 1,
    'fields' => array(
        array(
            'id' => 'default-title',
            'title' => __( 'Default Title', 'instagrate-pro' ),
            'desc' => __( 'Enter a title for posts where the Instagram image has no title.', 'instagrate-pro' ),
            'type' => 'text',
            'std' => 'Instagram Image'
        ),
        array(
            'id' => 'title-limit-type',
            'title' => __( 'Title Length Type', 'instagrate-pro' ),
            'desc' => __( 'Set type of length limit.', 'instagrate-pro' ),
            'type' => 'select',
            'choices' => array('characters' => 'Characters', 'words' => 'Words'),
            'std' => 'characters'
        ),
        array(
            'id' => 'title-limit',
            'title' => __( 'Title Length Limit', 'instagrate-pro' ),
            'desc' => __( 'Enter a number of characters/words to limit the title length. Leave blank for no limit.', 'instagrate-pro' ),
            'type' => 'text',
            'std' => ''
        ),
		array(
            'id' => 'bypass-home',
            'title' => __( 'Bypass is_home()', 'instagrate-pro' ),
            'desc' => __( 'Bypass is_home() check on posting. This should only be used if really necessary as it will make the plugin run on every page load.', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 0
        ),
		array(
            'id' => 'allow-duplicates',
            'title' => __( 'Allow Duplicate Images', 'instagrate-pro' ),
            'desc' => __( 'Allow posting of same image by different accounts', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 0
        ),
        array(
            'id' => 'high-res-images',
            'title' => __( 'High Resolution Images', 'instagrate-pro' ),
            'desc' => __( 'The Instagram API exposes images at 640x640px, but images are available at 1080x1080. Turning this on will use the higer res images but images may break as this is a bleeding edge feature.', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 0
        ),
        array(
            'id' => 'location-distance',
            'title' => __( 'Instagram Location Distance', 'instagrate-pro' ),
            'desc' => __( 'Set the distance in metres of the location searching of Instagram locations.', 'instagrate-pro' ),
            'type' => 'select',
            'choices' => array('' => 'Use Default', '500' => '500', '1000' => '1000', '2000' => '2000', '3000' => '3000', '4000' => '4000', '5000' => '5000'),
            'std' => ''
        ),
        array(
            'id' => 'admin-images',
            'title' => __( 'Images in Account Admin', 'instagrate-pro' ),
            'desc' => __( 'Set amount of images to show when the account is edited. This helps performance for accounts with a large number of images.', 'instagrate-pro' ),
            'type' => 'select',
            'choices' => array('' => 'All', '20' => '20', '40' => '40', '60' => '60', '80' => '80', '100' => '100'),
            'std' => ''
        ),
        array(
            'id' => 'image-save-name',
            'title' => __( 'Image Name as Instagram ID', 'instagrate-pro' ),
            'desc' => __( 'When saving the images to the media library the filename will be the caption. However, by checking this box it will save the images with the Instagram image ID as the filename e.g. c6077c14fe0a11e2b55e22000a9f09fb_7.jpg. This avoids any issues with non standard characters in the filename which can lead to the images not being displayed.', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 0
        ),
        array(
            'id' => 'image-caption',
            'title' => __( 'Add Caption to Saved Image', 'instagrate-pro' ),
            'desc' => __( 'When saving images to the media library this will add the "Caption" based on the above text allowing tags (eg. %%caption%% or %%caption-no-tags%%). Also runs through a filter "igp_image_caption". Leave blank for no caption.', 'instagrate-pro' ),
            'type' => 'text',
            'std' => '%%caption-no-tags%%'
        ),
         array(
            'id' => 'lightbox-rel',
            'title' => __( 'Lightbox Custom Rel', 'instagrate-pro' ),
            'desc' => __( 'Enter a custom rel attribute for thumbnails on multi maps to tie in with your lightbox plugin of choice.', 'instagrate-pro' ),
            'type' => 'text',
            'std' => 'lightbox'
        ),
        array(
            'id' => 'hide-meta',
            'title' => __( 'Hide Meta Boxes', 'instagrate-pro' ),
            'desc' => __( 'Hide meta boxes on the edit account page.', 'instagrate-pro' ),
            'type' => 'checkboxes',
            'std' => 0,
            'choices' => array( 'template' => 'Template Tags',
            					'custom' => 'Custom Meta',
            					'featured' => 'Custom Featured Image',
            					'tags' => 'Default Tags',
            					'map' => 'Map Settings',
            					'links' => 'Useful Links',
            				)
        ),
        array(
            'id' => 'credit-link',
            'title' => __( 'Link Love', 'instagrate-pro' ),
            'desc' => __( 'Check this to enable a credit link to the plugin page after images posted.', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 0
        ),
        array(
            'id' => 'cron-job',
            'title' => '<strong>'. __( 'Cron Job', 'instagrate-pro' ) .'</strong>',
            'desc' => '',
            'type' => 'custom',
            'std' => Instagrate_Pro_Helper::get_cron_job_html(),
        ),
    )
);

// Comments Settings section
$wpsfigp_settings[] = array(
    'section_id' => 'comments',
    'section_title' => 'Comments &amp; Likes',
    'section_order' => 1,
    'fields' => array(
		array(
            'id' => 'enable-comments',
            'title' => __( 'Enable Comments', 'instagrate-pro' ),
            'desc' => __( 'Enables Instagram comments imported as WordPress comments. <br><em>Only works if posts are created for each image, ie. the Multiple Images setting is set to "Post Per Image"</em>', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 0
        ),
		array(
            'id' => 'auto-approve',
            'title' => __( 'Automatically Approve Comments', 'instagrate-pro' ),
            'desc' => __( 'Enables the comments to be automatically approved in WordPress when imported from Instagram.', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 0
        ),
        array(
            'id' => 'avatar',
            'title' => __( 'Instagram Avatar', 'instagrate-pro' ),
            'desc' => __( 'Enables the use of the Instagram user\'s profile image as the comment author avatar image.', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 1
        ),
        array(
            'id' => 'mentions',
            'title' => __( 'Username Mentions', 'instagrate-pro' ),
            'desc' => __( 'Makes all mentions of an Instagram username in a comment into a link to their profile page on Instagram.', 'instagrate-pro' ),
            'type' => 'checkbox',
            'std' => 1
        ),
        array(
            'id' => 'likes',
            'title' =>  __( 'Likes', 'instagrate-pro' ),
            'desc' => '',
            'type' => 'custom',
            'std' => __( 'You can use the template tag %%likes%%, the shortcode [igp-likes], or the custom post meta \'ig_likes\' to display the Like count from Instagram.', 'instagrate-pro' )
        ),
        array(
            'id' => 'cron-sync',
            'title' => '<strong>'. __( 'Syncing', 'instagrate-pro' ) .'</strong>',
            'desc' => '',
            'type' => 'custom',
            'std' => __( 'You can synchronise the comments and likes from Instagram for a specific account by clicking on the buttons on the ', 'instagrate-pro' ) . '<a href="'.  get_admin_url() . 'edit.php?post_type=instagrate_pro">Instagrate Accounts page.</a>' .'
					<p>'. __( 'You can also set up a UNIX Cron job on your server to synchronise all comments and likes at a certain time interval, using the this url:', 'instagrate-pro' ) .'</p><code>'.  get_admin_url() . 'admin-ajax.php?action=instagram_sync</code>'
        ),
	)
);

// Comments Settings section
$wpsfigp_settings[] = array(
    'section_id' => 'api',
    'section_title' => 'API',
    'section_order' => 2,
    'fields' => array(
		array(
            'id' => 'enable-custom-client',
            'title' => __( 'Use Custom Instagram API Client', 'instagrate-pro' ),
            'desc' => __( 'Connect the plugin with your own Instagram API client. Register it here', 'instagrate-pro' ) .' <a target="_blank" href="http://instagram.com/developer/register/">here</a>',
            'type' => 'checkbox',
            'std' => 0
        ),
		array(
            'id' => 'custom-client-id',
            'title' => __( 'Client ID', 'instagrate-pro' ),
            'desc' => __( 'Enter the Client ID once you have registered your client', 'instagrate-pro' ),
            'type' => 'text',
            'std' => ''
        ),
        array(
            'id' => 'custom-client-secret',
            'title' => __( 'Client Secret', 'instagrate-pro' ),
            'desc' => __( 'Enter the Client Secret once you have registered your client', 'instagrate-pro' ),
            'type' => 'text',
            'std' => ''
        ),
        array(
            'id' => 'custom-redirect-uri',
            'title' => __( 'Redirect URI', 'instagrate-pro' ),
            'desc' => '',
            'type' => 'custom',
            'std' => __( 'When registering your Instagram API client use this as your Redirect URI:<br>', 'instagrate-pro' ) . '<code>'.  get_admin_url() . '</code>'
        ),
	)
);


// Support Settings section
$wpsfigp_settings[] = array(
    'section_id' => 'support',
    'section_title' => 'License &amp; Support',
    'section_order' => 3,
    'fields' => array(
         array(
            'id' => 'license',
            'title' => __( 'Intagrate License', 'instagrate-pro' ),
            'type' => 'license',
            'std' => ''
        ),
        array(
            'id' => 'debug-mode',
            'title' => __( 'Debug Mode', 'instagrate-pro' ),
            'desc' => __( 'Check this to enable debug mode for troubleshooting the plugin. The file debug.txt will be created in the plugin folder', 'instagrate-pro' ) .' - <a href="'.  plugin_dir_url( INSTAGRATEPRO_PLUGIN_FILE ) . 'debug.txt">debug.txt</a>',
            'type' => 'checkbox',
            'std' => 0
        ),
		array(
            'id' => 'send-data',
            'title' => __( 'Download Install Data', 'instagrate-pro' ),
            'desc' => '',
            'type' => 'custom',
            'std' => __( 'If you have raised an issue with us please download and attach the install data (and <a href="'.  plugin_dir_url( INSTAGRATEPRO_PLUGIN_FILE ) . 'debug.txt">debug.txt</a> if it exists) in your support email', 'instagrate-pro' ) .' -
					<p><a href="' . Instagrate_Pro_Helper::get_setting_url( 'suuport', array('nonce' => wp_create_nonce( 'install-data' ), 'download' => 'data' ) ) .'" class="button">'. __( 'Download Data', 'instagrate-pro' ) .'</a></p>'
        ),
		array(
            'id' => 'useful-links',
            'title' => __( 'Useful Links', 'instagrate-pro' ),
            'desc' => '',
            'type' => 'custom',
            'std' => 'Website: <a href="https://intagrate.io">Intagrate</a><br />
            Support: <a href="https://intagrate.io/support">Support</a><br />
            Changelog: <a href="https://intagrate.io/category/release/">Changelog</a><br/><br/>
			<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://intagrate.io" data-text="I\'m using the Intagrate WordPress plugin" data-via="intagrate">Tweet</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>'
        )
    )
);
