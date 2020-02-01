<?php

global $wpdb;

Class IFLPMDBManager {

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

	public static function is_table_empty($table_name) {
		global $wpdb;
		$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
		return $rows[0]->num_rows == 0;
	}

	public static function create_table($table_name,$sql,$table_version) {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option($table_name.'_db_version', $table_version);
	}

	public static function delete_all_rows_from_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("TRUNCATE TABLE " . $table_name);
	}

	public static function drop_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
	}
}

?>