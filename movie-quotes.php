<?php

global $wpdb;
define("MOVIE_QUOTES_TABLE_NAME", $wpdb->prefix . "movie_quotes");
define("MOVIE_QUOTES_DB_VERSION", "1.0");
// get rid of the "../" if you're not running it from an admin page
define("QUOTES_CSV_FILE", "../wp-content/plugins/ifl-party-mechanics/quotes.csv");
define("USER_PAIRINGS_TABLE_NAME", $wpdb->prefix . "user_pairings");
define("USER_PAIRINGS_DB_VERSION", "1.0");
define("TOKENS_TABLE_NAME", $wpdb->prefix . "rf_tokens");
define("TOKENS_DB_VERSION", "1.0");

Class MovieQuotes {
    //public $movie_quotes_table_name;

    public static function test_movie_quotes_stuff() {
        // for testing movie quotes functions

        //self::drop_movie_quotes_table();

        global $wpdb;
        echo "<br>";
        if (self::does_movie_quotes_table_exist_in_database()) {
            echo "Movie quotes table exists<br>";
        } else {
            echo "Movie quotes table does not exist, creating movie quotes table<br>";
            self::create_movie_quotes_table();
        }

        //self::delete_all_quotes_from_movie_quotes_table();

        if (self::is_table_empty(MOVIE_QUOTES_TABLE_NAME)) {
            echo "Movie quotes table is empty<br>";
            if (self::does_quotes_csv_file_exist()) {
                self::import_movie_quotes_to_database();
            }
        } else {
            //echo "Movie quotes table is not empty<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . MOVIE_QUOTES_TABLE_NAME);
            echo "Movie quotes table contains " . $rows[0]->num_rows . " records.<br>";
        }

        echo "Movie quote #1: " . self::get_movie_quote_by_id(1) . "<BR>";
    }

    public static function test_user_pairings_stuff() {
        // for testing user pairings functions
        global $wpdb;
        //self::drop_user_pairings_table();

        echo "<br>";
        if (self::does_table_exist_in_database(USER_PAIRINGS_TABLE_NAME)) {
            echo "User pairings table exists<br>";
        } else {
            echo "User pairings table does not exist, creating user pairings table<br>";
            self::create_user_pairings_table();
        }

        //self::delete_all_pairings_from_user_pairings_table();

        if (self::is_table_empty(USER_PAIRINGS_TABLE_NAME)) {
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
        //self::drop_tokens_table();

        global $wpdb;
        echo "<br>";
        if (self::does_table_exist_in_database(TOKENS_TABLE_NAME)) {
            echo "Tokens table exists<br>";
        } else {
            echo "Tokens table does not exist, creating Tokens table<br>";
            self::create_tokens_table();
        }

        //self::delete_all_tokens_from_tokens_table();

        if (self::is_table_empty(TOKENS_TABLE_NAME)) {
            echo "Tokens table is empty<br>";
        } else {
            //echo "Tokens table is not empty<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . TOKENS_TABLE_NAME);
            echo "Tokens table contains " . $rows[0]->num_rows . " records.<br>";
        }

        echo self::get_token_ids_by_user_id("0") . "<br>";
        echo self::get_user_id_from_token_id("5") . "<br>";
        echo self::add_token_id_and_user_id_to_tokens_table("7", "0") . "<br>";
    }

    public static function does_movie_quotes_table_exist_in_database() {
        return self::does_table_exist_in_database(MOVIE_QUOTES_TABLE_NAME);
    }

    public static function does_user_pairings_table_exist_in_database() {
        return self::does_table_exist_in_database(USER_PAIRINGS_TABLE_NAME);
    }

    public static function does_tokens_table_exist_in_database() {
        return self::does_table_exist_in_database(TOKENS_TABLE_NAME);
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

    public static function is_movie_quotes_table_empty() {
        return self::is_table_empty(MOVIE_QUOTES_TABLE_NAME);
    }

    public static function is_user_pairings_table_empty() {
        return self::is_table_empty(USER_PAIRINGS_TABLE_NAME);
    }

    public static function is_tokens_table_empty() {
        return self::is_table_empty(TOKENS_TABLE_NAME);
    }

    public static function is_table_empty($table_name) {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
        return $rows[0]->num_rows == 0;
    }

    public static function create_movie_quotes_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . MOVIE_QUOTES_TABLE_NAME . "(
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              quote tinytext NOT NULL,
              movie_name tinytext NOT NULL,
              PRIMARY KEY  (id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('movie_quotes_db_version', MOVIE_QUOTES_DB_VERSION);
    }

    public static function create_user_pairings_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . USER_PAIRINGS_TABLE_NAME . "(
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              pairing tinytext NOT NULL,
              PRIMARY KEY  (id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('user_pairings_db_version', USER_PAIRINGS_DB_VERSION);
    }

    public static function create_tokens_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // I had wanted to use token_id as the primary key but the table was not getting created,
        // even when I switched from tinytext to varchar(10)
        $sql = "CREATE TABLE " . TOKENS_TABLE_NAME . "(
              id mediumint(9) NOT NULL AUTO_INCREMENT,
              token_id tinytext NOT NULL,
              user_id tinytext NOT NULL,
              PRIMARY KEY  (id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('tokens_db_version', TOKENS_DB_VERSION);
    }

    public static function delete_all_quotes_from_movie_quotes_table() {
        self::delete_all_rows_from_table(MOVIE_QUOTES_TABLE_NAME);
    }

    public static function delete_all_pairings_from_user_pairings_table() {
        self::delete_all_rows_from_table(USER_PAIRINGS_TABLE_NAME);
    }

    public static function delete_all_tokens_from_tokens_table() {
        self::delete_all_rows_from_table(TOKENS_TABLE_NAME);
    }

    public static function delete_all_rows_from_table($table_name) {
        global $wpdb;
        $result = $wpdb->query("TRUNCATE TABLE " . $table_name);
    }

    public static function drop_movie_quotes_table() {
        self::drop_table(MOVIE_QUOTES_TABLE_NAME);
    }

    public static function drop_user_pairings_table() {
        self::drop_table(USER_PAIRINGS_TABLE_NAME);
    }

    public static function drop_tokens_table() {
        self::drop_table(TOKENS_TABLE_NAME);
    }

    public static function drop_table($table_name) {
        global $wpdb;
        $result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
    }

    public static function does_quotes_csv_file_exist() {
        return file_exists(QUOTES_CSV_FILE);
    }

    public static function import_movie_quotes_to_database() {
        global $wpdb;
        $file = fopen(QUOTES_CSV_FILE, "r");
        $quotes_array = [];
        // read all quotes into an array
        while (!feof($file)) {
            $entry = fgetcsv($file);
            if ($entry[0] != "") {
                array_push($quotes_array, $entry);
            }
        }
        fclose($file);

        // I commented shuffle out because
        // (1) My Python script had shuffled them in the first place, so they're already not grouped by movie
        // (2) If you have to read the quotes file in again, you don't want to change which quote is assigned to which pairing
        // shuffle($quotes_array);

        // write them to the quotes database
        foreach ($quotes_array as $entry) {
            $quote = $entry[0];
            $movie_name = $entry[1];
            //echo $quote . " - " . $movie_name . "<br>";
            $wpdb->insert(
                MOVIE_QUOTES_TABLE_NAME,
                array(
                    'quote' => $quote,
                    'movie_name' => $movie_name,
                )
            );
        }

    }

    public static function get_movie_quote_by_id($id) {
        global $wpdb;
        if (!self::does_table_exist_in_database(MOVIE_QUOTES_TABLE_NAME) || self::is_movie_quotes_table_empty()) {
            return null;
        } else {
            $result = $wpdb->get_results("SELECT * FROM " . MOVIE_QUOTES_TABLE_NAME . " WHERE id = " . $id);
            if ($wpdb->num_rows == 0) {
                return null;
            }
            $quote = $result[0]->quote;
            $movie_name = $result[0]->movie_name;
            return $quote . " - " . $movie_name;
        }
    }

    public static function get_movie_quote_by_pairing($user_id_1, $user_id_2) {
        // special case by request of John for a display loop between users
        if (($user_id_1 == 0 && $user_id_2 == 0) || ($user_id_1 == "00000000" && $user_id_2 == "00000000")) {
            return " Find a Friend and Get Your Movie Fortune                ";
        }
        $users = [$user_id_1, $user_id_2];
        sort($users);
        $pairing_string = "{$users[0]}-{$users[1]}";
        if (!self::does_table_exist_in_database(USER_PAIRINGS_TABLE_NAME)) {
            return null;
        }
        $id = self::get_id_by_pairing($pairing_string);
        return self::get_movie_quote_by_id($id);
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

    public static function add_pairing_to_user_pairings_table($pairing_string) {
        global $wpdb;
        $wpdb->insert(
            USER_PAIRINGS_TABLE_NAME,
            array(
                'pairing' => $pairing_string,
            )
        );
        $result = $wpdb->get_results("SELECT * FROM " . USER_PAIRINGS_TABLE_NAME . " WHERE pairing = '" . $pairing_string . "'");
        return $result[0]->id;
    }

    public static function token_id_exists_in_table($token_id) {
        // if the token ID is in the tokens table, returns associated user ID as string,
        // otherwise returns an error message
        global $wpdb;
        if (!self::does_table_exist_in_database(TOKENS_TABLE_NAME)) {
            return false;
        }
        $result = $wpdb->get_results("SELECT user_id FROM " . TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
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
        if (!self::does_table_exist_in_database(TOKENS_TABLE_NAME)) {
            return "Tokens table does not exist in database";
        }
        $result = $wpdb->get_results("SELECT user_id FROM " . TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
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
        if (!self::does_table_exist_in_database(TOKENS_TABLE_NAME)) {
            return "Tokens table does not exist in database";
        }
        $result = $wpdb->get_results("SELECT token_id FROM " . TOKENS_TABLE_NAME . " WHERE user_id = '" . $user_id . "'");
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
        if (!self::does_table_exist_in_database(TOKENS_TABLE_NAME)) {
            return "Error - tokens table does not exist in database";
        }
        if ($token_id == "") {
            return "Error - empty token ID";
        }
        if ($user_id == "") {
            return "Error - empty user ID";
        }
        $result = $wpdb->get_results("SELECT * FROM " . TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
        if ($wpdb->num_rows != 0) {
            $user_id_already_registered = $result[0]->user_id;
            if ($user_id_already_registered != $user_id) {
                return "Error - token ID " . $token_id . " is already registered to a different userID (" . $user_id_already_registered . ")";
            }
            return "Token ID " . $token_id . " is already registered to that user ID (" . $user_id . ")";
        }
        $wpdb->insert(
            TOKENS_TABLE_NAME,
            array(
                'token_id' => $token_id,
                'user_id' => $user_id,
            )
        );
        $result = $wpdb->get_results("SELECT * FROM " . TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
        if ($wpdb->num_rows != 0) {
            return "Token ID " . $token_id . " and user ID " . $user_id . " pairing added to the tokens table";
        } else {
            return "Error adding token ID and user ID to the tokens table";
        }
    }

}

?>
