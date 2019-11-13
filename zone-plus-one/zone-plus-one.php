<?php

/**
 * Plugin Name: Zone Plus One
 * Plugin URI:
 * Description: This plugin manages plus ones of zones with tokens.
 * Version: 1.0.0
 * Author: Idea Fab Labs Teams
 * Author URI: https://github.com/ideafablabs/
 * License: GPL3
 */

include 'rest-api.php';

global $wpdb;

define("ZONE_TOKENS_TABLE_NAME", $wpdb->prefix . "zone_tokens");
define("ZONE_TOKENS_DB_VERSION", "1.0");

define("ZONES_TABLE_NAME", $wpdb->prefix . "zones");
define("ZONES_DB_VERSION", "1.0");

define("PLUS_ONE_ZONES_TABLE_NAME", $wpdb->prefix . "plus_one_zones");
define("PLUS_ONE_ZONES_DB_VERSION", "1.0");

$ZonePlusOne = new ZonePlusOne;
$ZonePlusOne->run();

Class ZonePlusOne {

    public function run($options = array()) {
        add_action('admin_menu', array($this, 'wpdocs_register_my_custom_menu_page'));
    }

    public function wpdocs_register_my_custom_menu_page() {
        // Originally this if statement only had the second part of the or condition, with $admin_page_call
        // as an unknown variable, so I got rid of that error as below, but if anything else needs to be done, ???
        if (!isset($admin_page_call) || $admin_page_call == '') {
            $admin_page_call = array($this, 'admin_page_call');
        }

        add_menu_page(
            __(
                'Custom Menu Title', 'textdomain'),        // Page Title
            'Plus One Zones',                         // Menu Title
            'manage_options',                           // Required Capability
            'my_custom_plus_one_zones_menu_page',                      // Menu Slug
            $admin_page_call,                           // Function
            plugins_url('myplugin/images/icon.png'),  // Icon URL
            6
        );
    }

    public function admin_page_call() {
        // Echo the html here...
        echo "TESTING!</br>";
    }

    public function create_zones_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . ZONES_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              zone_name tinytext NOT NULL,
              PRIMARY KEY  (record_id),
            ) " . $charset_collate . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('zones_db_version', ZONES_DB_VERSION);
    }

    public function create_zone_tokens_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . ZONE_TOKENS_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              user_id bigint(20) unsigned NOT NULL,
              token_id tinytext NOT NULL,
              PRIMARY KEY  (record_id),
              FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
            ) " . $charset_collate . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('zone_tokens_db_version', ZONE_TOKENS_DB_VERSION);
    }

    public function create_plus_one_zones_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . PLUS_ONE_ZONES_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              user_id bigint(20) unsigned NOT NULL,
              zone_id mediumint(9) NOT NULL,
              PRIMARY KEY  (record_id),
              FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
              FOREIGN KEY  (zone_id) REFERENCES " . ZONES_TABLE_NAME . "(record_id)
            ) " . $charset_collate . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('plus_one_zones_db_version', PLUS_ONE_ZONES_DB_VERSION);
    }

    public static function does_zones_table_exist_in_database() {
        return self::does_table_exist_in_database(ZONES_TABLE_NAME);
    }

    public static function does_zone_tokens_table_exist_in_database() {
        return self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME);
    }

    public static function does_plus_ones_zones_table_exist_in_database() {
        return self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public static function does_table_exist_in_database($table_name) {
        global $wpdb;
        $mytables = $wpdb->get_results("SHOW TABLES");
        foreach ($mytables as $mytable) {
            foreach ($mytable as $t) {
                //echo "table name: " . $t . "<br>";
                if ($t == $table_name) {
                    return true;
                }
            }
        }
        return false;
    }

    public static function is_zones_table_empty() {
        return self::is_table_empty(ZONES_TABLE_NAME);
    }

    public static function is_zone_tokens_table_empty() {
        return self::is_table_empty(ZONE_TOKENS_TABLE_NAME);
    }

    public static function is_plus_one_zones_table_empty() {
        return self::is_table_empty(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public static function is_table_empty($table_name) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
        return $rows[0]->num_rows == 0;
    }

    public static function delete_all_zones_from_zones_table() {
        self::delete_all_rows_from_table(ZONES_TABLE_NAME);
    }

    public static function delete_all_zone_tokens_from_zone_tokens_table() {
        self::delete_all_rows_from_table(ZONE_TOKENS_TABLE_NAME);
    }

    public static function delete_all_plus_one_zones_from_plus_one_zones_table() {
        self::delete_all_rows_from_table(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public static function delete_all_rows_from_table($table_name) {
        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE " . $table_name);
    }

    public static function drop_zones_table() {
        self::drop_table(ZONES_TABLE_NAME);
    }

    public static function drop_zone_tokens_table() {
        self::drop_table(ZONE_TOKENS_TABLE_NAME);
    }

    public static function drop_plus_one_zones_table() {
        self::drop_table(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public static function drop_table($table_name) {
        global $wpdb;
        $result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
    }


}


?>
