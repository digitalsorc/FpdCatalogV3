<?php
require_once('wp-load.php');
global $wpdb;
$tables = $wpdb->get_results("SHOW TABLES LIKE '%fpd%'");
print_r($tables);
