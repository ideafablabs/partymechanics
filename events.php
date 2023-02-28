<?php

global $wpdb;

define("EVENTS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "events");
define("EVENTS_DB_VERSION", "1.0");

define("ATTENDANCE_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "attendance");
define("ATTENDANCE_DB_VERSION", "1.2");

define("SPECIAL_GUESTS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "special_guests");
define("SPECIAL_GUESTS_DB_VERSION", "1.1");


Class IFLPMEventsManager
{

	public static function add_attendee_to_event_attendance_table($user, $event_id) {
		/// Checks:
		/// Bad data in

		// User is already in attendance...
		if (self::user_attended_event($user, $event_id)) return false;

		// TODO get local time instead of forcing California time
		date_default_timezone_set("America/Los_Angeles");
		$check_in_time = date("Y-m-d H:i:s");

		global $wpdb;
		$wpdb->insert(
			ATTENDANCE_TABLE_NAME,
			array(
				'user_id' => $user->ID,
				'event_id' => $event_id,
				'check_in_time' => $check_in_time
			)
		);

		return true;
	}

	// if the user ID is in the events table, returns true otherwise returns an error message.
	public static function user_attended_event($user, $event_id) {

		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(ATTENDANCE_TABLE_NAME)) {
			throw new Exception("Attendance table does not exist in database", 1);
			// return false;
		}

		$result = $wpdb->get_results("SELECT * FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = '" . $event_id . "' AND user_id = '" . $user->ID . "'");

		if ($wpdb->num_rows == 0) {
			return false;
		} else {
			return true;
		}
	}

	public static function add_guest_to_special_guests_table($event_id, $guest_email, $guest_first_name, $guest_last_name) {

		global $wpdb;

		$guest_email = strtolower($guest_email);

		/// event_id is in table?

		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
			throw new Exception("Special Guest Table does not exist in database", 1);
			// return false;
		}

		// User is already in added to table...
		if (self::user_is_on_guest_list($guest_email, $event_id)) {
			throw new Exception("User is already on guest list.", 1);
		}

		// Insert record
		$records = $wpdb->insert(
			SPECIAL_GUESTS_TABLE_NAME,
			array(
				'event_id' => $event_id,
				'guest_email' => $guest_email,
				'guest_first_name' => $guest_first_name,
				'guest_last_name' => $guest_last_name,
			)
		);

		if (!$records) {
			throw new Exception("Unknown Error: Inserting Guest Failed", 1);
		}

		$result = $wpdb->get_results("SELECT * FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE guest_email = '" . $guest_email . "' AND event_id = '" . $event_id . "'");

		if ($wpdb->num_rows == 0) {
			throw new Exception("Unknown Error: Couldnt retrieve after insertion.", 1);
		} else {
			return true;
		}
	}


	public static function remove_guest_from_special_guests_table($guest_email, $event_id) {

		global $wpdb;

		$guest_email = strtolower($guest_email);

		/// event_id is in table?

		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
			throw new Exception("Special Guest Table does not exist in database", 1);
			// return false;
		}

		// User is already in added to table...
		if (!self::user_is_on_guest_list($guest_email, $event_id)) {
			throw new Exception("User was not on guest list.", 1);
		}

		// Insert record
		$records = $wpdb->delete(
			SPECIAL_GUESTS_TABLE_NAME,
			array(
				'guest_email' => $guest_email,
				'event_id' => $event_id,
			)
		);

		if (!$records) {
			throw new Exception("Unknown Error: Deleting Guest Failed", 1);
		}

		$result = $wpdb->get_results("SELECT * FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE guest_email = '" . $guest_email . "' AND event_id = '" . $event_id . "'");

		if ($wpdb->num_rows > 0) {
			throw new Exception("Unknown Error: Guest still in table after delete call.", 1);
		} else {
			return true;
		}
	}

	public static function user_is_on_guest_list($guest_email, $event_id) {

		global $wpdb;

		$guest_email = strtolower($guest_email);

		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
			return false;
		}

		$result = $wpdb->get_results("SELECT record_id FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE guest_email = '" . $guest_email . "' AND event_id = '" . $event_id . "'");

		return $wpdb->num_rows != 0;
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
		// TODO get local time instead of forcing California time
		date_default_timezone_set("America/Los_Angeles");
		$check_in_time = date("Y-m-d H:i:s");

		global $wpdb;
		$wpdb->insert(
			ATTENDANCE_TABLE_NAME,
			array(
				'user_id' => $user->ID,
				'event_id' => $event->event_id,
				'check_in_time' => $check_in_time
			)
		);
	}


	public static function list_attendees_for_selected_event() {
		global $wpdb;
		$selected_event_id = get_option('selected_event_id');

		$attendees_for_selected_event = $wpdb->get_results("SELECT * FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = '" . $selected_event_id . "'");

		echo "<p><b>People in the attendance table who attended the selected event:</b></p>";
		pr($attendees_for_selected_event);
		foreach ($attendees_for_selected_event as $attendee) {
			echo get_user_by("ID", $attendee->user_id)->display_name . "<br>";
		}
	}

	public static function get_attendees_for_event($event_id) {
		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(ATTENDANCE_TABLE_NAME)) {
			throw new Exception("Attendance Table does not exist in database", 1);
		}

		$attendees = array();
		$result = $wpdb->get_results("SELECT user_id FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = '" . $event_id . "'");

		foreach ($result as $attendee) {
			$attendees[] = get_user_by("ID", $attendee->user_id);
		}

		return $attendees;
	}

	public static function get_attendee_count_for_event($event_id) {
		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(ATTENDANCE_TABLE_NAME)) {
			return -1;
			// throw new Exception("Attendance Table does not exist in database", 1);
		}
		
		$result = $wpdb->get_var("SELECT COUNT(user_id) FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = '" . $event_id . "'");

		// pr($result);

		return $result;
	}


	public static function get_event_title_by_id($event_id) {

		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(EVENTS_TABLE_NAME)) {
			throw new Exception("Events table does not exist.", 1);
		}

		$result = $wpdb->get_results("SELECT title FROM " . EVENTS_TABLE_NAME . " WHERE event_id = '" . $event_id . "'");

		if ($wpdb->num_rows == 0) {
			throw new Exception("Event not found in Events Table.", 1);
		}

		return $result[0]->title;
	}

	/// incomplete here. we hacked in the dashboard view.
	public static function import_members_from_json($api_endpoint) {
		/// sanitize
		$json = file_get_contents($api_endpoint);
		$members = json_decode($json);
		
		
		pr($this->menu_options['form_id']);
		pr($json);
		// pr($obj);
	
		foreach ($members as $key => $member) {
				
			$active_member_list[0]['First Name'] = $member->first_name;
			$active_member_list[0]['Last Name'] = $member->last_name;
	
			$entries[$key] = array(
				'form_id' => $this->->menu_options['ticketform_id'], 
				'9' => 'mint@ideafablabs.com',
				$this->->menu_options['event_field_id'] => $event_name,
				// $this->attendees_list_id => $active_member_list
				$this->->menu_options['attendees_list_id'] => serialize($active_member_list)
			);
			
		}	 
	}

	public static function get_list_of_special_guests_by_event($event_id) {
		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
			throw new Exception("Special Events Table does not exist.", 1);
		}

		return $wpdb->get_results("SELECT guest_email, guest_first_name, guest_last_name FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE event_id = '" . $event_id . "'");
	}

	public static function create_events_table() {
		global $wpdb;
		global $events_db_version;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . EVENTS_TABLE_NAME . " (
			  event_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  title tinytext NOT NULL,
			  date date NOT NULL,
			  PRIMARY KEY  (event_id)
			) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);

		add_option('events_db_version', $events_db_version);
	}

	public static function create_attendance_table() {
		global $wpdb;
		global $events_db_version;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . ATTENDANCE_TABLE_NAME . " (
			  record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  user_id bigint(20) unsigned NOT NULL,
			  event_id mediumint(9) NOT NULL,
			  check_in_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			  PRIMARY KEY  (record_id),
			  FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
			  FOREIGN KEY  (event_id) REFERENCES " . EVENTS_TABLE_NAME . "(event_id)
			) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);

		add_option('attendance_db_version', ATTENDANCE_DB_VERSION);
	}

	public static function update_attendance_table_version($oldVersion) {
		global $wpdb;

		if ($oldVersion == "1.0" || $oldVersion == "1.1") {
			// Drop the old never-even-used table and recreate it with the currently-desired fields and correct events table foreign key
			$wpdb->query("DROP TABLE IF EXISTS " . ATTENDANCE_TABLE_NAME);
			self::create_attendance_table();
		}

		update_option('attendance_db_version', ATTENDANCE_DB_VERSION);
	}

	public static function create_special_guests_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . SPECIAL_GUESTS_TABLE_NAME . " (
			record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			guest_email tinytext NOT NULL,
			guest_first_name tinytext,
			guest_last_name tinytext,
			event_id mediumint(9) NOT NULL,
			PRIMARY KEY  (record_id),
			FOREIGN KEY  (event_id) REFERENCES " . EVENTS_TABLE_NAME . "(event_id)
			) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);

		add_option('special_guests_db_version', SPECIAL_GUESTS_DB_VERSION);
	}

	public static function update_special_guests_table_version($oldVersion) {
		global $wpdb;

		if ($oldVersion == "1.0") {
			// Drop the old never-even-used table and recreate it with the currently-desired fields and correct events table foreign key
			$wpdb->query("DROP TABLE IF EXISTS " . SPECIAL_GUESTS_TABLE_NAME);
			self::create_special_guests_table();
		}

		update_option('special_guests_db_version', SPECIAL_GUESTS_DB_VERSION);
	}

}

?>