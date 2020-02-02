<?php

global $wpdb;

define("USER_TOKENS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "user_tokens");
define("USER_TOKENS_DB_VERSION", "1.0");

Class UserTokens {

	// Token Color Hex Values
	public static $token_colors = array(
		0x00FF00, 
		0xFFFF00, 
		0xFF0000, 
		0x0000FF, 
		0xFF00FF, 
		0xFFFFFF
	);
	
	// Token Color Classes
	// Should be G Y R B (& secret purple)
	public static $token_color_classes = array(
		'greentoken', 
		'yellowtoken', 
		'redtoken', 
		'bluetoken', 
		'purpletoken', 
		'whitetoken'
	);

	public static function get_token_color_class($token_id) {
		// we think idcode is always even... this is mostly because we read the little-endian id as if it were big-endian and are getting kinda lucky. But this /2 mod5 thing works so ok for now. dvb 2019.	
		
		$token_id /= 2;		
		return self::$token_color_classes[$token_id % 5];
	}


	// if the token ID is in the tokens table, returns associated user ID as string, otherwise returns false.
	public static function token_id_exists_in_table($token_id) {		
		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) return false;

		$result = $wpdb->get_results("SELECT user_id FROM " . USER_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		
		if ($wpdb->num_rows == 0) return false;

		return $result[0];
	}

	// if the token ID is in the tokens table, returns associated user ID as string, otherwise returns an error message.
	public static function get_user_id_from_token_id($token_id) {
		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) 
			return "Tokens table does not exist in database";
		
		$result = $wpdb->get_results("SELECT user_id FROM " . USER_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		
		if ($wpdb->num_rows == 0) return "Token id " . $token_id . " not found in database, you need to register it with a user ID";
		
		return $result[0]->user_id;		
	}

	// if the user ID is in the tokens table, returns associated token ID(s) as array, otherwise returns an error message
	public static function get_token_ids_by_user_id($user_id) {
		global $wpdb;

		if (!IFLPMDBManager::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) return false;

		$result = $wpdb->get_results("SELECT token_id FROM " . USER_TOKENS_TABLE_NAME . " WHERE user_id = " . $user_id);
		
		if ($wpdb->num_rows == 0) return false;

		return array_map(function ($token) {return $token->token_id;}, $result);
	}

	public static function add_token_id_and_user_id_to_tokens_table($token_id, $user_id) {
		global $wpdb;
		if (!IFLPMDBManager::does_table_exist_in_database(USER_TOKENS_TABLE_NAME)) {
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

	// === DB Methods ===

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

	public static function does_user_tokens_table_exist_in_database() {
		return IFLPMDBManager::does_table_exist_in_database(USER_TOKENS_TABLE_NAME);
	}

	public static function is_tokens_table_empty() {
		return IFLPMDBManager::is_table_empty(USER_TOKENS_TABLE_NAME);
	}

	public static function delete_all_tokens_from_tokens_table() {
		IFLPMDBManager::delete_all_rows_from_table(USER_TOKENS_TABLE_NAME);
	}

	public static function drop_tokens_table() {
		IFLPMDBManager::drop_table(USER_TOKENS_TABLE_NAME);
	}

}

?>