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

        add_submenu_page('my_custom_plus_one_zones_menu_page',
            "Manage Zone Names",
            "Manage Zone Names",
            'manage_options',
            "manage_zone_names_page",
            array($this, 'manage_zone_names_page_call'));


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

    public function manage_zone_names_page_call() {
        $emptyNameEntered = false;
        $newZoneAdded = false;
        if (isset($_POST['submit_new_zone_name'])) {
            // If we're adding a new zone
            $newZoneName = trim($_POST['new_zone_name']);
            if ($newZoneName == "") {
                $emptyNameEntered = true;
            } else {
                $this->add_zone_to_zones_table($newZoneName);
                echo "<p style='color:Blue'><b><i>Your new zone '" . $newZoneName . "' was added</i></b></p>";
            }
        } else if (isset($_POST['submit_edited_zone_name'])) {
            // Or if we're changing the name of an existing zone
            $selectedZoneId = trim($_POST['selected_zone_id']);
            $editedZoneName = trim($_POST['edited_zone_name']);
            if ($editedZoneName == "") {
                echo "<p style='color:Blue'><b><i>Error - a zone name can't be blank</i></b></p>";
            } else {
                $result = $this->edit_zone_name_in_zones_table($selectedZoneId, $editedZoneName);
                echo "<p style='color:Blue'><b><i>" . $result . "</i></b></p>";
            }
        }

        global $wpdb;
        $result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME);

        echo "<script>
            function updateTextBox(selection) {
                document.getElementById('edited_zone_name').value=selection.options[selection.selectedIndex].text;
            }</script>";
        echo "<h1>Manage Idea Fab Labs zone names</h1>";
        echo "<br><h2>To add a new zone, enter its name below, and click 'Add Zone'</h2><form name='form1' method='post' action=''>";
        if ($emptyNameEntered) {
            echo "<p style='color: red; font-weight: bold'>Please enter the name for the new zone</p>";
        }
        echo "<input type='hidden' name='hidden' value='Y'>
        <input type='text' name='new_zone_name'/>
        <input type='submit' name='submit_new_zone_name' value='Add Zone'/>
        <br><br><br><h2>To change the name of an existing zone, select it in the dropdown below, edit its name in the textbox, and click 'Save Name Change'</h2><form name='form1' method='post' action=''>
            <select id='selected_zone_id' name='selected_zone_id' onchange='updateTextBox(this)'>";
        for ($i = 0; $i < sizeof($result); $i++) {
            $id = strval($result[$i]->record_id);
            echo "<option value='" . strval($result[$i]->record_id) . "'>" . $result[$i]->zone_name . "</option>";
        }
        echo "<input type='text' name='edited_zone_name' id='edited_zone_name' value='" . $result[0]->zone_name . "'/>
        <input type='submit' name='submit_edited_zone_name' value='Save Name Change'/>
        </form><br>";

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
              date date NOT NULL,
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
        $token_id = trim($token_id);
        if ($token_id == "") {
            return "Error - empty zone token ID";
        }
        $user_id = trim($user_id);
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
        // This function is used by the Manage Zone Names submenu page, and should probably not be used by an API
        global $wpdb;
        $zone_name = trim($zone_name);
        if ($zone_name == "") {
            return "Error - empty zone name";
        }
        // TODO figure out a good way to handle not letting people enter multiple versions of existing zone names --
        if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
            return "Error - zones table does not exist in database";
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

    public function add_plus_one_to_plus_one_zones_table($zone_id, $token_id) {
        global $wpdb;
        if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
            return "Error - zones table does not exist in database";
        }

        if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
            return "Error - zone tokens table does not exist in database";
        }

        if (!self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
            return "Error - plus one zones table does not exist in database";
        }

        $result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE record_id = '" . $zone_id . "'");
        if ($wpdb->num_rows == 0) {
            return "Error - zone ID " . $zone_id . " does not exist in the zones table";
        }

        $user_id = $this->get_user_id_from_zone_token_id($token_id);
        if (substr($user_id, 0, 5) === "Error") {
            return $user_id;
        }

        if ($this->user_already_plus_oned_this_zone_today($user_id, $zone_id)) {
            return "Error - user " . $this->get_user_name_from_user_id($user_id) . " already plus-one'd the " . $this->get_zone_name_from_zone_id($zone_id) . " today";
        }

        // TODO get local time instead of forcing California time
        date_default_timezone_set("America/Los_Angeles");
        $date = date("Y-m-d H:i:s");
        // Or enter a date like this for testing with total vs current month
        // $date = "2019-11-02";

        $wpdb->insert(
            PLUS_ONE_ZONES_TABLE_NAME,
            array(
                'user_id' => $user_id,
                'zone_id' => $zone_id,
                'date' => $date,
            )
        );
        $result = $wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE user_id = '" . $user_id . "' AND zone_id = '" . $zone_id . "'");
        if ($wpdb->num_rows != 0) {
            return $this->get_zone_name_from_zone_id($zone_id) . " plus one for user " . $this->get_user_name_from_user_id($user_id) . " successfully added to the plus one zones table";
        } else {
            return "Error adding plus one to the plus one zones table";
        }
    }

    public function edit_zone_name_in_zones_table($zone_id, $edited_zone_name) {
        // This function is used by the Manage Zone Names submenu page, and should probably not be used by an API
        global $wpdb;
        $edited_zone_name = trim($edited_zone_name);
        if ($edited_zone_name == "") {
            return "Error - empty zone name";
        }
        // TODO figure out a good way to handle not letting people enter multiple versions of existing zone names --
        if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
            return "Error - zones table does not exist in database";
        }
        $result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE zone_name = '" . $edited_zone_name . "' COLLATE utf8mb4_bin");
        if ($wpdb->num_rows != 0) {
            return "Error - a zone with the name " . $edited_zone_name . " already exists in the zones table";
        }
        $result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE record_id = '" . $zone_id . "'");
        if ($wpdb->num_rows == 0) {
            return "Error - zone ID " . $zone_id . " does not exist in the zones table";
        }
        $result = $wpdb->update(
            ZONES_TABLE_NAME,
            array(
                'zone_name' => $edited_zone_name
            ),
            array('record_id' => $zone_id)
        );
        if ($result === false) {
            return "Error updating zone name";
        } else {
            return "Zone name successfully updated to " . $edited_zone_name;
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
            return "Error - zone tokens table does not exist in database";
        }
        $result = $wpdb->get_results("SELECT user_id FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
        if ($wpdb->num_rows == 0) {
            return "Error - zone token id " . $token_id . " not found in database, you need to register it to a user ID";
        } else {
            return $result[0]->user_id;
        }
    }

    public function get_user_name_from_user_id($user_id) {
        global $wpdb;
        if (!self::is_user_id_in_database($user_id)) {
            return "Error - user ID " . $user_id . " is not a registered user";
        }
        $user = get_userdata($user_id);
        return $user->display_name;
    }

    public function get_zone_name_from_zone_id($zone_id) {
        global $wpdb;
        $result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE record_id = '" . $zone_id . "'");
        if ($wpdb->num_rows == 0) {
            return "Error - zone id " . $zone_id . " doesn't exist in the zones table";
        }
        return $result[0]->zone_name;
    }

    public function get_zone_token_ids_by_user_id($user_id) {
        // if the user ID is in the tokens table, returns associated token ID(s) as ", "-separated string,
        // otherwise returns an error message
        global $wpdb;
        if (!self::is_user_id_in_database($user_id)) {
            return "Error - user ID " . $user_id . " is not a registered user";
        }

        if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
            return "Error - tokens table does not exist in database";
        }

        $result = $wpdb->get_results("SELECT token_id FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE user_id = '" . $user_id . "'");
        if ($wpdb->num_rows == 0) {
            return "Error - no zone tokens found for user ID " . $user_id;
        } else {
            return join(", ", array_map(function ($token) {
                return $token->token_id;
            }, $result));
        }
    }

//    public function list_users_with_ids() {
//        $users = get_users("orderby=ID");
//        foreach ($users as $key => $user) {
//
//            echo "User ID " . $user->ID . " " . $user->display_name . "<br>";
//        }
//    }

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
        // $this->drop_plus_one_zones_table();
        if ($this->does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
            echo "Plus one zones table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . PLUS_ONE_ZONES_TABLE_NAME);
            echo "Plus one zones table contains " . $rows[0]->num_rows . " records.<br>";
            echo "Plus one total for zone 1: " . $this->get_total_plus_one_count_by_zone_id(1) . "<br>";
            echo "Plus one total for zone 8: " . $this->get_total_plus_one_count_by_zone_id(8) . "<br>";
            $zpo_array = $this->get_zone_plus_ones_array_for_dashboard();
            echo "<br>Zones plus one dashboard array length " . sizeof($zpo_array) . "<br>";
            foreach ($zpo_array as $entry) {
                echo $entry["zone_name"] . " total plus-ones count: " . $entry["total_plus_one_count"] . ", and plus-ones count for this month: " . $entry["this_month_plus_one_count"] . "<br>";
            }
            echo "Or for the JSON version, " . json_encode($zpo_array) . "<br>";
        } else {
            echo "Plus one zones table does not exist, creating plus one zones table<br>";
            $this->create_plus_one_zones_table();
        }
        //Add a plus-one
        echo $this->add_plus_one_to_plus_one_zones_table(3, 1) . "<br>";
    }

    public function get_total_plus_one_count_by_zone_id($zone_id) {
        global $wpdb;
        $wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE zone_id = '" . $zone_id . "'");
        return $wpdb->num_rows;
    }

    public function get_this_months_plus_one_count_by_zone_id($zone_id) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE zone_id = '" . $zone_id . "' AND date >=  DATE_FORMAT(NOW() ,'%Y-%m-01')");
        return $wpdb->num_rows;
    }

    public function get_zone_plus_ones_array_for_dashboard() {
        // This returna an array for the dashboard to use -- a row for each zone, ordered by zone name,
        // with the fields "zone_name", "total_plus_one_count", and "this_month_plus_one_count".
        // The API function calling this should use json_encode() to send the array as a JSOB string
        $zones_plus_one_array = array();
        global $wpdb;
        if (self::does_table_exist_in_database(ZONES_TABLE_NAME) && self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
            $zone_names_result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " ORDER BY zone_name");
            foreach ($zone_names_result as $key => $zone) {
                $zone_row = array();
                $zone_row["zone_name"] = $zone->zone_name;
                $id = $zone->record_id;
                $zone_row["total_plus_one_count"] = $this->get_total_plus_one_count_by_zone_id($id);
                $zone_row["this_month_plus_one_count"] = $this->get_this_months_plus_one_count_by_zone_id($id);
                array_push($zones_plus_one_array, $zone_row);
            }
        }
        return $zones_plus_one_array;

    }

    public function user_already_plus_oned_this_zone_today($user_id, $zone_id) {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE zone_id = '" . $zone_id. "' AND user_id = '" . $user_id . "' AND date =  DATE_FORMAT(NOW() ,'%Y-%m-%d')");
        return $wpdb->num_rows != 0;
    }

}


?>
