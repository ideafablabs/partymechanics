<?php

global $wpdb;

define("EVENTS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "events");
define("EVENTS_DB_VERSION", "1.0");

define("ATTENDANCE_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "attendance");
define("ATTENDANCE_DB_VERSION", "1.0");

define("SPECIAL_GUESTS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "special_guests");
define("SPECIAL_GUESTS_DB_VERSION", "1.0");


Class IFLPMEventsManager {

	public static function add_attendee_to_event_attendance_table($user,$event_id) {
		/// Checks:
		/// Bad data in
		
		// User is already in attendance...
		if (self::user_attended_event($user,$event_id)) return false;

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

	// if the user ID is in the events table, returns true otherwise returns an error message.
	public static function user_attended_event($user,$event_id) {
		
		global $wpdb;
		
		if (!IFLPMDBManager::does_table_exist_in_database(ATTENDANCE_TABLE_NAME)) {
			throw new Exception("Attendance table does not exist in database", 1);			
			// return false;
		}
		
		$result = $wpdb->get_results("SELECT * FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = '" . $event_id . "' AND user_id = '".$user->ID."'");		
		
		if ($wpdb->num_rows == 0) {
			return false;
		} else {
			return true;
		}
	}

	public static function add_guest_to_special_guests_table($user,$event_id) {
		
		global $wpdb;
		
		if (!is_a($user,'WP_User')) {
			throw new Exception("Bad user data.");
		}
		
		/// event_id is in table? 

		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
			throw new Exception("Special Guest Table does not exist in database", 1);			
			// return false;
		}

		// User is already in added to table...
		if (self::user_is_on_guest_list($user,$event_id)) {
			throw new Exception("User is already on guest list.", 1);			
		}

		// Insert record
		$records = $wpdb->insert(
			SPECIAL_GUESTS_TABLE_NAME,
			array(
				'user_id' => $user->ID,
				'event_id' => $event_id,
			)
		);

		if (!$records) {
			throw new Exception("Unknown Error: Inserting Guest Failed", 1);
		}
		
		$result = $wpdb->get_results("SELECT * FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE user_id = '" . $user->ID . "' AND event_id = '".$event_id."'");		

		if ($wpdb->num_rows == 0) {
			throw new Exception("Unknown Error: Couldnt retrieve after insertion.", 1);
		} else {			
			return true;
		}
	}


	public static function remove_guest_from_special_guests_table($user,$event_id) {
		
		global $wpdb;
		
		if (!is_a($user,'WP_User')) {
			throw new Exception("Bad user data.");
		}
		
		/// event_id is in table? 

		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
			throw new Exception("Special Guest Table does not exist in database", 1);			
			// return false;
		}

		// User is already in added to table...
		if (!self::user_is_on_guest_list($user,$event_id)) {
			throw new Exception("User was not on guest list.", 1);			
		}

		// Insert record
		$records = $wpdb->delete(
			SPECIAL_GUESTS_TABLE_NAME,
			array(
				'user_id' => $user->ID,
				'event_id' => $event_id,
			)
		);

		if (!$records) {
			throw new Exception("Unknown Error: Deleting Guest Failed", 1);
		}
		
		$result = $wpdb->get_results("SELECT * FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE user_id = '" . $user->ID . "' AND event_id = '".$event_id."'");		

		if ($wpdb->num_rows > 0) {
			throw new Exception("Unknown Error: Guest still in table after delete call.", 1);
		} else {			
			return true;
		}
	}

	public static function user_is_on_guest_list($user,$event_id) {

		global $wpdb;
		
		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {			
			return false;
		}
		
		$result = $wpdb->get_results("SELECT record_id FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE user_id = '" . $user->ID . "' AND event_id = '".$event_id."'");

		if ($wpdb->num_rows == 0) {
			return false;
		} else {			
			return true;
		}
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

	
	public static function list_attendees_for_selected_event() {
		global $wpdb;
		$selected_event_id = get_option('selected_event_id');
		
		$attendees_for_selected_event = $wpdb->get_results("SELECT * FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = " . $selected_event_id);
		
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
		$result = $wpdb->get_results("SELECT user_id FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = ".$event_id);
				
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
				
		$result = $wpdb->get_results("SELECT COUNT(user_id) FROM " . ATTENDANCE_TABLE_NAME . " WHERE event_id = ".$event_id);

		return $result->num_rows;	
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
	

	public static function get_list_of_special_guests_by_event($event_id) {
		global $wpdb;
		
		if (!IFLPMDBManager::does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {			
			throw new Exception("Special Events Table does not exist.", 1);
		}
		
		$result = $wpdb->get_results("SELECT user_id FROM " . SPECIAL_GUESTS_TABLE_NAME . " WHERE event_id = '" . $event_id . "'");
		
		$users = array();
		foreach ($result as $key => $record) {
			$users[$key] = get_user_by('id',$record->user_id);
		}
		
		return $users;
	}
	
	public static function create_events_table() {
		global $wpdb;
		global $events_db_version;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE ".EVENTS_TABLE_NAME." (
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

		$sql = "CREATE TABLE ".ATTENDANCE_TABLE_NAME." (
			  record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  user_id bigint(20) unsigned NOT NULL,
			  event_id mediumint(9) NOT NULL,
			  PRIMARY KEY  (record_id) ". //,
			  // FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
			  // FOREIGN KEY  (event_id) REFERENCES wp_events(event_id)
			") $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);

		add_option('attendance_db_version', ATTENDANCE_DB_VERSION);
	}

	public static function create_special_guests_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . SPECIAL_GUESTS_TABLE_NAME . " (
			  record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  user_id bigint(20) unsigned NOT NULL,
			  event_id mediumint(9) NOT NULL,
			  PRIMARY KEY  (record_id)".
			  // FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
			  // FOREIGN KEY  (event_id) REFERENCES wp_events(event_id)
			") $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);
		
		add_option('special_guests_db_version', SPECIAL_GUESTS_DB_VERSION);
	}

}

?>