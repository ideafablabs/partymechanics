<?php

global $wpdb;

define("EVENTS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "events");
define("EVENTS_DB_VERSION", "1.0");

define("ATTENDANCE_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "attendance");
define("ATTENDANCE_DB_VERSION", "1.0");

define("SPECIAL_GUESTS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "special_guests");
define("SPECIAL_GUESTS_DB_VERSION", "1.0");


Class IFLPMEventsManager {

	static public function test_event_title_stuff() {
        // see https://codex.wordpress.org/Adding_Administration_Menus
        global $hidden_field_name, $wpdb;
        $selected_event_id = get_option('selected_event_id');

        $result = $wpdb->get_results("SELECT * FROM " . EVENTS_TABLE_NAME);
        echo "<table><tr><td style='vertical-align: top;'><h1 style='padding-right: 20px;'>Events</h1></td>";
        echo "<td style='vertical-align: center; padding-top: 10px'><a href='admin.php?page=add_new_event_page' class='button'>Add New Event</a></td></tr></table>";

        echo "<form name='select_event' method='post' action='#'>
        <b>Select event: </b> 
            <select id='selected_event_id' name='selected_event_id'>";
        for ($i = 0; $i < sizeof($result); $i++) {
            $id = strval($result[$i]->event_id);
            $selected = ($id == $selected_event_id) ? "selected" : "";
            echo "<option value='" . strval($result[$i]->event_id) . "' " . $selected . ">" . $result[$i]->title . " - " . date_format(date_create($result[$i]->date),"F j, Y") . "</option>";
        }
        echo "<input type='submit' name='submit' value='Submit Selection Change' /></form>";
    }


	public function add_attendee_to_event_attendance_table($user,$event_id) {
    	/// Checks:
    	/// Bad data in
    	/// User is already attended...
    	
    	global $wpdb;
        $wpdb->insert(
            ATTENDANCE_TABLE_NAME,
            array(
                'user_id' => $user->ID,
                'event_id' => $event_id
            )
        );	

        return true;
    }

    public static function insert_event($title, $date) {
        global $wpdb;
        $wpdb->insert(
            EVENTS_TABLE_NAME,
            array(
                'title' => $title,
                'date' => $date,
            )
        );
    }

    public static function insert_attendee($user, $event) {
        echo $user->ID . " ";
        echo $event->event_id . "<br>";
        global $wpdb;
        $wpdb->insert(
            ATTENDANCE_TABLE_NAME,
            array(
                'user_id' => $user->ID,
                'event_id' => $event->event_id,
            )
        );
    }

    public static function test_insert_some_attendees() {
        global $wpdb;
        $events = $wpdb->get_results("SELECT * FROM " . EVENTS_TABLE_NAME);
        $users = get_users("orderby=display_name");
        for ($i = 0; $i < sizeof($events); $i++) {
            for ($j = 0; $j < sizeof($events); $j++) {
                self::insert_attendee($users[$i * sizeof($events) + $j], $events[$i]);
            }
        }
    }

    public static function list_attendees_for_selected_event() {
        global $wpdb;
        $selected_event_id = get_option('selected_event_id');
        $attendees_for_selected_event = $wpdb->get_results("SELECT * FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = " . $selected_event_id);
        echo "<p><b>People in the attendance table who attended the selected event:</b></p>";
        foreach ($attendees_for_selected_event as $attendee) {
            echo get_user_by("ID", $attendee->user_id)->display_name . "<br>";
        }
    }

    public static function test_event_registration_form_stuff() {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
        $gravityforms = false;
        $gravityformsuserregistration = false;
        foreach ($active_plugins as $plugin) {
            if ($plugin == "gravityforms/gravityforms.php") {
                $gravityforms = true;
            } else if ($plugin == "gravityformsuserregistration/userregistration.php") {
                $gravityformsuserregistration = true;
            }
        }
        if ($gravityforms & $gravityformsuserregistration) {
            echo "<br><br>Gravity Forms and Gravity Forms User Registration are installed and active<br>";
            echo "<b>Active forms:</b><br>";
            $forms = GFAPI::get_forms();
            foreach ($forms as $form) {
                echo "Title: " . $form["title"] . ", ID: " . $form["id"] . "<br>";
            }
        } else {
            echo "<br>Gravity Forms and Gravity Forms User Registration are <b>not</b> installed and active<br>";
        }
    }

    public static function test_user_dropdown() {
        global $wpdb;
        $users = get_users("orderby=display_name");
        foreach ($users as $key => $user) {

            echo $user->display_name . "<br>";
        }
    }


    public static function test_events_table_stuff() {
        global $wpdb;
        echo "<br>";
        if (IFLPMDBManager::does_table_exist_in_database(EVENTS_TABLE_NAME)) {
            echo "Events table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . EVENTS_TABLE_NAME);
            echo "Events table contains " . $rows[0]->num_rows . " records.<br>";
            //$this->insert_event("Doublemint", "2019-03-30");
        } else {
            echo "Events table does not exist, creating Events table<br>";
            self::create_events_table();
        }

    }

    public static function test_attendance_table_stuff() {
        global $wpdb;
        echo "<br>";
        if (IFLPMDBManager::does_table_exist_in_database(ATTENDANCE_TABLE_NAME)) {
            echo "Attendance table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . ATTENDANCE_TABLE_NAME);
            echo "Attendance table contains " . $rows[0]->num_rows . " records.<br>";
        } else {
            echo "Attendance table does not exist, creating Attendance table<br>";
            self::create_attendance_table();
        }
    }

    public static function test_special_guests_table_stuff() {
        global $wpdb;
        echo "<br>";
        if (IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
            echo "Special Guests table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . SPECIAL_GUESTS_TABLE_NAME);
            echo "Special Guests table contains " . $rows[0]->num_rows . " records.<br>";
        } else {
            echo "Special Guests table does not exist, creating Special Guests table<br>";
            self::create_special_guests_table();
        }

    }

    public static function create_events_table() {
        global $wpdb;
        global $events_db_version;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE EVENTS_TABLE_NAME (
              event_id mediumint(9) NOT NULL AUTO_INCREMENT,
              title tinytext NOT NULL,
              date date NOT NULL,
              PRIMARY KEY  (event_id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('events_db_version', $events_db_version);
    }

    public static function create_attendance_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . ATTENDANCE_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              user_id bigint(20) unsigned NOT NULL,
              event_id mediumint(9) NOT NULL,
              PRIMARY KEY  (record_id),
              FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
              FOREIGN KEY  (event_id) REFERENCES wp_events(event_id)
            ) " . $charset_collate . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('attendance_db_version', ATTENDANCE_DB_VERSION);
    }

    public function create_special_guests_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . SPECIAL_GUESTS_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              user_id bigint(20) unsigned NOT NULL,
              event_id mediumint(9) NOT NULL,
              PRIMARY KEY  (record_id),
              FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
              FOREIGN KEY  (event_id) REFERENCES wp_events(event_id)
            ) " . $charset_collate . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('special_guests_db_version', SPECIAL_GUESTS_DB_VERSION);
    }

}

?>




