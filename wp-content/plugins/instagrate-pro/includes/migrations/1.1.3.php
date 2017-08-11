<?php
/**
 * UTF8 Changes to images table
 */

global $wpdb;
$table = instagrate_pro()->images->get_table_name();
$wpdb->query( "	alter table $table change tags tags BLOB;" );
$wpdb->query( "alter table $table change tags tags text CHARACTER SET utf8;" );
$wpdb->query( "alter table $table change caption caption BLOB;" );
$wpdb->query( "alter table $table change caption caption text CHARACTER SET utf8;" );
$wpdb->query( "alter table $table change caption_clean caption_clean BLOB;" );
$wpdb->query( "alter table $table change caption_clean caption_clean text CHARACTER SET utf8;" );
$wpdb->query( "alter table $table change caption_clean_no_tags caption_clean_no_tags BLOB;" );
$wpdb->query( "alter table $table change caption_clean_no_tags caption_clean_no_tags text CHARACTER SET utf8;" );
$wpdb->query( "alter table $table change username username BLOB;" );
$wpdb->query( "alter table $table change username username text CHARACTER SET utf8; " );
$wpdb->query( "alter table $table CHARACTER SET utf8;" );