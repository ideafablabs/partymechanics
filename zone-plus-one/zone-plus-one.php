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

Class ZonePlusOne
{

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
        echo "</br></br>TESTING!</br>";

        $this->test_zone_tokens_table_stuff();
        $this->test_zones_table_stuff();
        $this->test_plus_one_zones_table_stuff();

        echo "</br>" . $this->get_zone_token_ids_by_user_id("3") . "</br>";
        echo "</br>" . $this->get_user_id_from_zone_token_id("1") . "</br>";

        echo "</br>" . $this->add_zone_token_to_zone_tokens_table("1", "3") . "</br>";

        echo "</br>" . $this->add_zone_to_zones_table("Electronics zone") . "</br>";

    }

    public function create_zones_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . ZONES_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              zone_name tinytext NOT NULL,
              PRIMARY KEY  (record_id)
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
              FOREIGN KEY  (user_id) REFERENCES wp_users(ID)
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

    public function does_zones_table_exist_in_database() {
        return self::does_table_exist_in_database(ZONES_TABLE_NAME);
    }

    public function does_zone_tokens_table_exist_in_database() {
        return self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME);
    }

    public function does_plus_ones_zones_table_exist_in_database() {
        return self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public function does_table_exist_in_database($table_name) {
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

    public function is_zones_table_empty() {
        return self::is_table_empty(ZONES_TABLE_NAME);
    }

    public function is_zone_tokens_table_empty() {
        return self::is_table_empty(ZONE_TOKENS_TABLE_NAME);
    }

    public function is_plus_one_zones_table_empty() {
        return self::is_table_empty(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public function is_table_empty($table_name) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
        return $rows[0]->num_rows == 0;
    }

    public function delete_all_zones_from_zones_table() {
        self::delete_all_rows_from_table(ZONES_TABLE_NAME);
    }

    public function delete_all_zone_tokens_from_zone_tokens_table() {
        self::delete_all_rows_from_table(ZONE_TOKENS_TABLE_NAME);
    }

    public function delete_all_plus_one_zones_from_plus_one_zones_table() {
        self::delete_all_rows_from_table(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public function delete_all_rows_from_table($table_name) {
        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE " . $table_name);
    }

    public function drop_zones_table() {
        self::drop_table(ZONES_TABLE_NAME);
    }

    public function drop_zone_tokens_table() {
        self::drop_table(ZONE_TOKENS_TABLE_NAME);
    }

    public function drop_plus_one_zones_table() {
        self::drop_table(PLUS_ONE_ZONES_TABLE_NAME);
    }

    public function drop_table($table_name) {
        global $wpdb;
        $result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
    }

    public function add_zone_token_to_zone_tokens_table($token_id, $user_id) {
        global $wpdb;
        if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
            return "Error - zone tokens table does not exist in database";
        }
        if ($token_id == "") {
            return "Error - empty zone token ID";
        }
        if ($user_id == "") {
            return "Error - empty user ID";
        }

        if (!self::is_user_id_in_database($user_id)) {
            return "Error - user ID " . $user_id . " is not a registered user";
        }

        $result = $wpdb->get_results("SELECT * FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
        if ($wpdb->num_rows != 0) {
            $user_id_already_registered = $result[0]->user_id;
            if ($user_id_already_registered != $user_id) {
                return "Error - zone token ID " . $token_id . " is already registered to a different userID (" . $user_id_already_registered . ")";
            }
            return "Zone token ID " . $token_id . " is already registered to that user ID (" . $user_id . ")";
        }
        $wpdb->insert(
            ZONE_TOKENS_TABLE_NAME,
            array(
                'token_id' => $token_id,
                'user_id' => $user_id,
            )
        );
        $result = $wpdb->get_results("SELECT * FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
        if ($wpdb->num_rows != 0) {
            return "Zone token ID " . $token_id . " and user ID " . $user_id . " successfully added to the zone tokens table";
        } else {
            return "Error adding zone token ID to the tokens table";
        }
    }

    public function add_zone_to_zones_table($zone_name) {
        global $wpdb;
        $zone_name = trim($zone_name);
        if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
            return "Error - zones table does not exist in database";
        }
        if ($zone_name == "") {
            return "Error - empty zone name";
        }

        $result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE zone_name = '" . $zone_name . "'");
        if ($wpdb->num_rows != 0) {
            return "Zone " . $zone_name . " already exists in the zones table";
        }
        $wpdb->insert(
            ZONES_TABLE_NAME,
            array(
                'zone_name' => $zone_name,
            )
        );
        $result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE zone_name = '" . $zone_name . "'");
        if ($wpdb->num_rows != 0) {
            return "Zone " . $zone_name . " successfully added to the zones table";
        } else {
            return "Error adding zone to the zones table";
        }
    }

    public function is_user_id_in_database($user_id) {
        return get_user_by("ID", $user_id) != null;
    }

    public function get_user_id_from_zone_token_id($token_id) {
        // if the token ID is in the tokens table, returns associated user ID as string,
        // otherwise returns an error message
        global $wpdb;
        if (!$this->does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
            return "Zone tokens table does not exist in database";
        }
        $result = $wpdb->get_results("SELECT user_id FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
        if ($wpdb->num_rows == 0) {
            return "Zone token id " . $token_id . " not found in database, you need to register it to a user ID";
        } else {
            return $result[0]->user_id;
        }
    }

    public function get_zone_token_ids_by_user_id($user_id) {
        // if the user ID is in the tokens table, returns associated token ID(s) as ", "-separated string,
        // otherwise returns an error message
        global $wpdb;
        if (!self::is_user_id_in_database($user_id)) {
            return "Error - user ID " . $user_id . " is not a registered user";
        }

        if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
            return "Tokens table does not exist in database";
        }

        $result = $wpdb->get_results("SELECT token_id FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE user_id = '" . $user_id . "'");
        if ($wpdb->num_rows == 0) {
            return "No zone tokens found for user ID " . $user_id;
        } else {
            return join(", ", array_map(function ($token) {
                return $token->token_id;
            }, $result));
        }
    }

    public function list_users_with_ids() {
        $users = get_users("orderby=ID");
        foreach ($users as $key => $user) {

            echo "User ID " . $user->ID . " " . $user->display_name . "<br>";
        }
    }

    public function test_zone_tokens_table_stuff() {
        global $wpdb;
        echo "<br>";
        if ($this->does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
            echo "Zone tokens table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . ZONE_TOKENS_TABLE_NAME);
            echo "Zone tokens table contains " . $rows[0]->num_rows . " records.<br>";
        } else {
            echo "Zone tokens table does not exist, creating zone tokens table<br>";
            $this->create_zone_tokens_table();
        }
    }

    public function test_zones_table_stuff() {
        global $wpdb;
        echo "<br>";
        if ($this->does_table_exist_in_database(ZONES_TABLE_NAME)) {
            echo "Zones table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . ZONES_TABLE_NAME);
            echo "Zones table contains " . $rows[0]->num_rows . " records.<br>";
        } else {
            echo "Zones table does not exist, creating zones table<br>";
            $this->create_zones_table();
        }
    }

    public function test_plus_one_zones_table_stuff() {
        global $wpdb;
        echo "<br>";
        if ($this->does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
            echo "Plus one zones table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . PLUS_ONE_ZONES_TABLE_NAME);
            echo "Plus one zones table contains " . $rows[0]->num_rows . " records.<br>";
        } else {
            echo "Plus one zones table does not exist, creating plus one zones table<br>";
            $this->create_plus_one_zones_table();
        }
    }

}


?>
