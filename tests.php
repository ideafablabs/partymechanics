<?php 

Class IFLPMTest {

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

	public static function test_insert_some_attendees() {
		global $wpdb;
		$events = $wpdb->get_results("SELECT * FROM " . EVENTS_TABLE_NAME);
		$users = get_users("orderby=display_name");
		for ($i = 0; $i < sizeof($events); $i++) {
			for ($j = 0; $j < sizeof($events); $j++) {
				IFLPMEventsManager::insert_attendee($users[$i * sizeof($events) + $j], $events[$i]);
			}
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
			IFLPMEventsManager::create_attendance_table();
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
			IFLPMEventsManager::create_special_guests_table();
		}

		echo "<h2>Special Guest List</h2>";
		$selected_event_id = get_option('selected_event_id');
		
		$guest_list = IFLPMEventsManager::get_list_of_special_guests_by_event($selected_event_id);

		echo "<ul>";
		foreach ($guest_list as $key => $user) {
			echo "<li>".$user->display_name." - ".$user->user_email."</li>";	
		}
	}

	public static function test_movie_quotes_stuff() {
		// for testing movie quotes functions
		global $wpdb;
		echo "<br>";
		if (MovieQuotes::does_movie_quotes_table_exist_in_database()) {
			echo "Movie quotes table exists<br>";
		} else {
			echo "Movie quotes table does not exist, creating movie quotes table<br>";
			MovieQuotes::create_movie_quotes_table();
		}

		//self::delete_all_quotes_from_movie_quotes_table();

		if (MovieQuotes::is_table_empty(MOVIE_QUOTES_TABLE_NAME)) {
			echo "Movie quotes table is empty<br>";
			if (MovieQuotes::does_quotes_csv_file_exist()) {
				MovieQuotes::import_movie_quotes_to_database();
			}
		} else {
			//echo "Movie quotes table is not empty<br>";
			$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . MOVIE_QUOTES_TABLE_NAME);
			echo "Movie quotes table contains " . $rows[0]->num_rows . " records.<br>";
		}

		echo "Movie quote #1: " . MovieQuotes::get_movie_quote_by_id(1) . "<BR>";
	}

	public static function test_user_pairings_stuff() {
		// for testing user pairings functions
		global $wpdb;
		//self::drop_user_pairings_table();

		echo "<br>";
		if (IFLPMDBManager::does_table_exist_in_database(USER_PAIRINGS_TABLE_NAME)) {
			echo "User pairings table exists<br>";
		} else {
			echo "User pairings table does not exist, creating user pairings table<br>";
			MovieQuotes::create_user_pairings_table();
		}

		//self::delete_all_pairings_from_user_pairings_table();

		if (IFLPMDBManager::is_table_empty(USER_PAIRINGS_TABLE_NAME)) {
			echo "User pairings table is empty<br>";
		} else {
//            echo "User pairings table is not empty<br>";
			$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . USER_PAIRINGS_TABLE_NAME);
			echo "User pairings table contains " . $rows[0]->num_rows . " records.<br>";
		}

		echo "Movie quote for pairing of users 1 and 3: " . self::get_movie_quote_by_pairing(1, 3) . "<br>";
	}

	public static function test_tokens_stuff() {
		// for testing tokens functions
		// self::drop_tokens_table();

		global $wpdb;
		echo "<br>";
		if (IFLPMDBManager::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) {
			echo "Tokens table exists<br>";
		} else {
			echo "Tokens table does not exist, creating Tokens table<br>";
			UserTokens::create_tokens_table();
		}

		//self::delete_all_tokens_from_tokens_table();

		if (IFLPMDBManager::is_table_empty(USER_TOKENS_TABLE_NAME)) {
			echo "Tokens table is empty<br>";
		} else {
			//echo "Tokens table is not empty<br>";
			$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . USER_TOKENS_TABLE_NAME);
			echo "Tokens table contains " . $rows[0]->num_rows . " records.<br>";
		}

		pr(UserTokens::get_token_ids_by_user_id("0"));
		echo UserTokens::get_user_id_from_token_id("5") . "<br>";
		echo UserTokens::add_token_id_and_user_id_to_tokens_table("7", "0") . "<br>";
	}
}
 ?>