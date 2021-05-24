<?php

/*
Plugin Name: Titans24 - system plugins
Description: This plugins helps you to maintenance your application.
Author: lukaszdabrzalski
Version: 1.0.2
Author URI: titans24.com
*/


require WPMU_PLUGIN_DIR . '/version-st24/version-st24.php';
require WPMU_PLUGIN_DIR . '/aryo-activity-log/aryo-activity-log.php';
require WPMU_PLUGIN_DIR . '/updraftplus/updraftplus.php';


// plugin WP Offload Media - disable GZIP compression for SVG files
function cst_gzip_mime_types( $mime_types, $media_library ) {
	// Don't GZip any offloads, keep them pristine.
	$mime_types = array();

	// Add SVG (already is by default).
	//$mime_types['svg'] = 'image/svg+xml';

	// Remove SVG.
	unset( $mime_types['svg'] );

	return $mime_types;
}
add_filter( 'as3cf_gzip_mime_types', 'cst_gzip_mime_types', 10, 2 );


function set_blog_public() {
    update_option( 'blog_public', getenv( 'BLOG_PUBLIC' ) === '1' ? '1' : '0' );
}
add_action( 'init', 'set_blog_public' );

function disable_plugin_deactivation( $actions, $plugin_file, $plugin_data, $context ) {
    if ( array_key_exists( 'deactivate', $actions ) && in_array( $plugin_file, array(
        'worker/init.php',
        'wp-mail-smtp/wp_mail_smtp.php',
        'updraftplus/updraftplus.php',
    ))) {
        unset( $actions['deactivate'] );
    }

    return $actions;
}
add_filter( 'plugin_action_links', 'disable_plugin_deactivation', 10, 4 );

function plugins_maintenance() {
    global $wpdb;

    // aryo-activity-log - create db table
    $table_name   = $wpdb->prefix . "aryo_activity_log";
    $query_result = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

    if( $table_name !== $query_result ) {
        require_once(ABSPATH . 'wp-content/mu-plugins/aryo-activity-log/classes/class-aal-maintenance.php');
        $aryo_maintenance = new AAL_Maintenance();
        $aryo_maintenance::activate(false);
    }
}
add_action('admin_init', 'plugins_maintenance');


function cst_menu_order( $menu_order ) {
    global $menu;

    /* https://whiteleydesigns.com/editing-wordpress-admin-menus */

    $key = array_search( 'edit.php?post_type=acf-field-group', $menu_order, true );
    if ( $menu_order[ $key ] ) {
        unset( $key );
        $menu_order[] = 'edit.php?post_type=acf-field-group';    // move to the end of the menu
    }

    $key = array_search( 'activity_log_page', $menu_order, true );
    if ( $menu_order[ $key ] ) {
        unset( $key );
        $menu_order[] = 'activity_log_page';                   	// move to the end of the menu
    }

    foreach ( $menu as $key => $item ) {
        if ( 'activity_log_page' === $item[2] ) {
            if ( false === in_array( wp_get_current_user()->user_login, [ 'support@titans24.com', 'admin@titans24.com', 'admin@25wat.com' ], true ) ) {
                unset( $menu[$key] );
            }
            break;
        }
    }

    return $menu_order;
}
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', 'cst_menu_order', 1 );

