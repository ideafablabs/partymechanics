<?php
/**
 * Register a book post type, with REST API support
 *
 * Based on example at: https://codex.wordpress.org/Function_Reference/register_post_type
 */
// add_action( 'init', 'my_book_cpt' );
// function my_book_cpt() {
//     $args = array(
//       'public'       => true,
//       'show_in_rest' => true,
//       'label'        => 'Books'
//     );
//     register_post_type( 'book', $args );
// }

//add_action('rest_api_init', function () {
//    $movie_quotes_controller = new Movie_Quotes_Controller();
//    $movie_quotes_controller->register_routes();
//});

// https://codex.wordpress.org/Creating_Tables_with_Plugins
// https://www.wpeka.com/make-custom-endpoints-wordpress-rest-api.html

class Movie_Quotes_Controller extends WP_REST_Controller {
  	public function register_routes() {
    	$namespace = 'mint/v1';
    	$path = 'quote_pair';

    	register_rest_route( $namespace, '/' . $path, [
      	array(
        	'methods'             => 'GET',
        	'callback'            => array( $this, 'get_item' ),
        	'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'post_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),

        ]);     
    }
    public function get_item($request) {

//		$args = array(
//	        'quote' => $request['quote_id']
//	    );
//
//	    // $quotes = get_posts($args);
//
//
//	    if (empty($posts)) {
//
//	            return new WP_Error( 'empty_category', 'there is no post in this category', array( 'status' => 404 ) );
//	    }
//        return new WP_REST_Response($posts, 200);
        return new WP_REST_Response("Testing API response", 200);
	}

    public function post_item($request) {
  	    $nfc1 = $request->get_param( 'NFC1' );
  	    $nfc2 = $request->get_param( 'NFC2' );
  	    if (!empty($nfc1) && !empty($nfc2) ) {
            return new WP_REST_Response("NFC1: " . $nfc1 . ", NFC2: " . $nfc2, 200);
        } else {
            return new WP_REST_Response("NFC parameters not found", 200);
        }

    }

    public function get_items_permissions_check($request) {
    	return true;
  	}

    public function createMovieQuoteTable() {

        // clear the previous table
        $table = $wp_prefix."movie-qutoes";


        
        // get the file
        $csv = "/";

        // insert into table
        // WP_Query

    }

    public function my_plugin_create_db() {

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'movie_qutoes';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,    
            views smallint(5) NOT NULL,
            clicks smallint(5) NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        }
}

class NFC_Registration_Controller extends WP_REST_Controller {
    public function register_routes() {
        $namespace = 'mint/v1';
        $path = 'readers/(?P<reader_id>\d+)';

        register_rest_route( $namespace, '/' . $path, [
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' )
                ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'post_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),

        ]);     
    }
    public function get_item($request) {

        $reader_id = $request['reader_id'];

        $reader_value = get_option('reader_'.$reader_id);

        if ($reader_value === false) {
            return new WP_Error( 'no_value', 'Retrieving reader value failed.', array( 'status' => 404 ) );
        }
        if ($reader_value == 0) {
            return new WP_Error( 'no_id', 'No new ID available.', array( 'status' => 200 ) );
        }
        
        // Clear the reader value.
        // update_option('reader_'.$reader_id,0);

        return new WP_REST_Response($reader_value, 200);
    }

    public function post_item($request) {
        $reader_id = $request->get_param( 'reader_id' );
        $reader_value = $request->get_param( 'reader_value' );
        
        if (!empty($reader_value) && !empty($reader_id) ) {

            update_option('reader_'.$reader_id,$reader_value);

            return new WP_REST_Response("Reader Value Updated", 200);
        } else {
            return new WP_REST_Response("NFC parameters not found", 200);
        }

    }

    public function get_items_permissions_check($request) {
        return true;
    }
}   



?>