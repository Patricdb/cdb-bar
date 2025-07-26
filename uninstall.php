<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

global $wpdb;
$table = $wpdb->prefix . 'cdb_experiencia_revision';
$wpdb->query( "DROP TABLE IF EXISTS $table" );
