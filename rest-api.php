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

        $fortune = get_option('fortune');
        //update_option('fortune',"");

        return new WP_REST_Response($fortune, 200);
	}

    public function post_item($request) {
  	    
        global $IFLPartyMechanics;

//        if ($request->get_param( 'DONE' )) {
//            update_option('fortune',"");
//
//            return new WP_REST_Response("FORTUNE BUFFER CLEARED",200);
//        }

        $nfc1 = $request->get_param( 'NFC1' );
  	    $nfc2 = $request->get_param( 'NFC2' );
  	    if (!empty($nfc1) && !empty($nfc2) ) {


            $user1 = get_users(array('meta_key' => 'nfcid1', 'meta_value' => $nfc1));

            $user2 = get_users(array('meta_key' => 'nfcid1', 'meta_value' => $nfc2));


            $uid1 = $user1[0]->ID;
            $uid2 = $user2[0]->ID;

            // // get user ids.
            // $uid1 = get_user_meta()

            // get pairing.
            //$fortune = $IFLPartyMechanics->get_movie_quote_by_pairing($uid1,$uid2);
            $fortune = $IFLPartyMechanics->get_movie_quote_by_pairing($nfc1,$nfc2);

            // put the fortune into options memory.

            // $fortune = "Kakow!";

            // $fortune = $users[0]->['id'];
            update_option('fortune',$fortune);

            return new WP_REST_Response($fortune,200);

            // return new WP_REST_Response($users[0]->ID);
            
            // return new WP_REST_Response("NFC1: " . $nfc1 . ", NFC2: " . $nfc2, 200);
        } else {
            return new WP_REST_Response("NFC parameters not found", 200);
        }

    }

    public function get_items_permissions_check($request) {
    	return true;
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