#!/bin/bash

wp eval '
  global $wpdb;
  $table = $wpdb->prefix . "gf_form_view";
  $exists = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s",
      $table,
      "idx_form_id_count"
  ));
  if ( ! $exists ) {
      $wpdb->query( "ALTER TABLE `{$table}` ADD INDEX `idx_form_id_count` (`form_id`, `count`)" );
      echo "Index added to {$table}\n";
  } else {
      echo "Index already exists on {$table}, skipping\n";
  }
  ' --allow-root
