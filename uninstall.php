<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

if ( !is_multisite() ) {

	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'bbpmd_markdown' ) );

} else {

	$blog_ids = $wpdb->get_col( "select blog_id from {$wpdb->blogs}" );
    $current_blog_id = get_current_blog_id();

    foreach ( $blog_ids as $bid )
    {
        switch_to_blog( $bid );
        $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'bbpmd_markdown' ) );
    }

    switch_to_blog( $current_blog_id );
}

?>
