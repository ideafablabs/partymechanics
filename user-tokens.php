<?php

global $wpdb;

define("USER_TOKENS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "user_tokens");
define("USER_TOKENS_DB_VERSION", "1.0");

Class UserTokens {

	public static function test_tokens_stuff() {
		// for testing tokens functions
		// self::drop_tokens_table();

		global $wpdb;
		echo "<br>";
		if (self::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) {
			echo "Tokens table exists<br>";
		} else {
			echo "Tokens table does not exist, creating Tokens table<br>";
			self::create_tokens_table();
		}

		//self::delete_all_tokens_from_tokens_table();

		if (self::is_table_empty(USER_TOKENS_TABLE_NAME)) {
			echo "Tokens table is empty<br>";
		} else {
			//echo "Tokens table is not empty<br>";
			$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . USER_TOKENS_TABLE_NAME);
			echo "Tokens table contains " . $rows[0]->num_rows . " records.<br>";
		}

		echo self::get_token_ids_by_user_id("0") . "<br>";
		echo self::get_user_id_from_token_id("5") . "<br>";
		echo self::add_token_id_and_user_id_to_tokens_table("7", "0") . "<br>";
	}

	public static function does_user_tokens_table_exist_in_database() {
		return self::does_table_exist_in_database(USER_TOKENS_TABLE_NAME);
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

	public static function is_tokens_table_empty() {
		return self::is_table_empty(USER_TOKENS_TABLE_NAME);
	}

	public static function is_table_empty($table_name) {
		global $wpdb;
		$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
		return $rows[0]->num_rows == 0;
	}

	public static function create_tokens_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		/// I had wanted to use token_id as the primary key but the table was not getting created,
		/// even when I switched from tinytext to varchar(10)
		$sql = "CREATE TABLE " . USER_TOKENS_TABLE_NAME . "(
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  token_id tinytext NOT NULL,
			  user_id tinytext NOT NULL,
			  PRIMARY KEY  (id)
			) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('tokens_db_version', TOKENS_DB_VERSION);
	}

	public static function delete_all_tokens_from_tokens_table() {
		self::delete_all_rows_from_table(USER_TOKENS_TABLE_NAME);
	}

	public static function delete_all_rows_from_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("TRUNCATE TABLE " . $table_name);
	}

	public static function drop_tokens_table() {
		self::drop_table(USER_TOKENS_TABLE_NAME);
	}

	public static function drop_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
	}

	public static function token_id_exists_in_table($token_id) {
		// if the token ID is in the tokens table, returns associated user ID as string,
		// otherwise returns an error message
		global $wpdb;
		if (!self::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) {
			return false;
		}
		$result = $wpdb->get_results("SELECT user_id FROM " . USER_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows == 0) {
			return false;
		} else {
			return true;
		}
	}

	public static function get_user_id_from_token_id($token_id) {
		// if the token ID is in the tokens table, returns associated user ID as string,
		// otherwise returns an error message
		global $wpdb;
		if (!self::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) {
			return "Tokens table does not exist in database";
		}
		$result = $wpdb->get_results("SELECT user_id FROM " . USER_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows == 0) {
			return "Token id " . $token_id . " not found in database, you need to register it with a user ID";
		} else {
			return $result[0]->user_id;
		}
	}

	public static function get_token_ids_by_user_id($user_id) {
		// if the user ID is in the tokens table, returns associated token ID(s) as ", "-separated string,
		// otherwise returns an error message
		global $wpdb;
		if (!self::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) {
			return "Tokens table does not exist in database";
		}
		$result = $wpdb->get_results("SELECT token_id FROM " . USER_TOKENS_TABLE_NAME . " WHERE user_id = '" . $user_id . "'");
		if ($wpdb->num_rows == 0) {
			return "No tokens found for user ID " . $user_id;
		} else {
			return join(", ", array_map(function ($token) {
				return $token->token_id;
			}, $result));
		}
	}

	public static function add_token_id_and_user_id_to_tokens_table($token_id, $user_id) {
		global $wpdb;
		if (!self::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) {
			return "Error - tokens table does not exist in database";
		}
		if ($token_id == "") {
			return "Error - empty token ID";
		}
		if ($user_id == "") {
			return "Error - empty user ID";
		}
		$result = $wpdb->get_results("SELECT * FROM " . USER_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows != 0) {
			$user_id_already_registered = $result[0]->user_id;
			if ($user_id_already_registered != $user_id) {
				return "Error - token ID " . $token_id . " is already registered to a different userID (" . $user_id_already_registered . ")";
			}
			return "Token ID " . $token_id . " is already registered to that user ID (" . $user_id . ")";
		}
		$wpdb->insert(
			USER_TOKENS_TABLE_NAME,
			array(
				'token_id' => $token_id,
				'user_id' => $user_id,
			)
		);
		$result = $wpdb->get_results("SELECT * FROM " . USER_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows != 0) {
			return "Token ID " . $token_id . " and user ID " . $user_id . " pairing added to the tokens table";
		} else {
			return "Error adding token ID and user ID to the tokens table";
		}
	}

}

?>
