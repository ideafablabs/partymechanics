<?php

global $wpdb;

define("SPIRIT_KNOBS_TABLE_NAME", $wpdb->prefix . IFLPM_TABLE_PREFIX . "spirit_knobs");
define("SPIRIT_KNOBS_DB_VERSION", "1.0");

Class SpiritKnobs {

	public static function does_spirit_knobs_table_exist_in_database() {
		return IFLPMDBManager::does_table_exist_in_database(SPIRIT_KNOBS_TABLE_NAME);
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

	public static function is_spirit_knobs_table_empty() {
		return self::is_table_empty(SPIRIT_KNOBS_TABLE_NAME);
	}

	public static function is_table_empty($table_name) {
		global $wpdb;
		$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
		return $rows[0]->num_rows == 0;
	}

	public static function create_spirit_knobs_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . SPIRIT_KNOBS_TABLE_NAME . " (
			  record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  user_id bigint(20) unsigned NOT NULL,
			  spirits tinytext NOT NULL,
			  PRIMARY KEY  (record_id),
			  FOREIGN KEY  (user_id) REFERENCES wp_users(ID)			   
			) " . $charset_collate . ";";	

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('spirit_knobs_db_version', SPIRIT_KNOBS_DB_VERSION);
	}

	public static function delete_all_spirits_from_spirit_knobs_table() {
		self::delete_all_rows_from_table(SPIRIT_KNOBS_TABLE_NAME);
	}

	public static function delete_all_rows_from_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("TRUNCATE TABLE " . $table_name);
	}

	public static function drop_spirit_knobs_table() {
		self::drop_table(SPIRIT_KNOBS_TABLE_NAME);
	}

	public static function drop_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
	}
	
	public static function update_user_spirits_by_id($user, $spirits) {
		// echo $user;
		// echo $spirits;
		if (self::is_user_in_spirit_table($user)) {
			// echo "updating";
			self::update_spirits($user, $spirits);	
		} else {
			// echo "inserting";
			self::insert_spirits($user, $spirits);	
		}

		return true;
	}

	///TODO
	// Get spirits by user_ID
	// 
	public static function update_spirits($user, $spirits) {
		// echo $user->ID . " ";
		// echo $event->event_id . "<br>";
		/// Needs checks		
		$data = array('spirits' => $spirits);
		$where = array('user_id' => $user->ID);

		global $wpdb;
		$wpdb->update(
			SPIRIT_KNOBS_TABLE_NAME,
			$data,
			$where
		);
	}

	public static function insert_spirits($user, $spirits) {
		// echo $user->ID . " ";
		// echo $event->event_id . "<br>";

		global $wpdb;
		$result = $wpdb->insert(
			SPIRIT_KNOBS_TABLE_NAME,
			array(
				'user_id' => $user->ID,
				'spirits' => $spirits				
			)
		);
	}

	public static function get_user_spirits_by_id($id) {
		global $wpdb;
		if (!IFLPMDBManager::does_table_exist_in_database(SPIRIT_KNOBS_TABLE_NAME) || self::is_spirit_knobs_table_empty()) {
			return null;
		} else {
			$result = $wpdb->get_results("SELECT * FROM " . SPIRIT_KNOBS_TABLE_NAME . " WHERE id = " . $id);
			if ($wpdb->num_rows == 0) {
				return null;
			}

			$spirits = $result[0]->spirits;
			return $spirits;
		}
	}
	public static function is_user_in_spirit_table($id) {
		
		global $wpdb;
		if (!IFLPMDBManager::does_table_exist_in_database(SPIRIT_KNOBS_TABLE_NAME) || self::is_spirit_knobs_table_empty()) {
			return false;
		} else {
			$result = $wpdb->get_results("SELECT * FROM " . SPIRIT_KNOBS_TABLE_NAME . " WHERE id = " . $id);
			
			if ($wpdb->num_rows == 0) {
				return false;
			}
			
			return true;
		}
	}


	public static function get_id_by_pairing($pairing_string) {
		global $wpdb;
		$result = $wpdb->get_results("SELECT * FROM " . USER_PAIRINGS_TABLE_NAME . " WHERE pairing = '" . $pairing_string . "'");
		if ($wpdb->num_rows == 0) {
			$id = self::add_pairing_to_user_pairings_table($pairing_string);
		} else {
			$id = $result[0]->id;
		}
		return $id;
	}

}

?>