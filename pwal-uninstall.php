<?php
/**
 *	Uninstalls plugin (deletes options and post metas)
 */
function pwal_uninstall() {
	delete_option( 'pwal_options' );
	
	global $wpdb;
	$wpdb->query("DELETE FROM " . $wpdb->postmeta . " WHERE meta_key='pwal_method' OR meta_key='pwal_enable' OR meta_key='pwal_excerpt' " );
}