<?php
/**
 * Register a book post type, with REST API support
 *
 * Based on example at: https://codex.wordpress.org/Function_Reference/register_post_type
 */

// https://codex.wordpress.org/Creating_Tables_with_Plugins
// https://www.wpeka.com/make-custom-endpoints-wordpress-rest-api.html
class Users_Tokens_Controller extends WP_REST_Controller {
	public function register_routes() {
		$namespace = 'mint/v1';
		$path = 'users';

		register_rest_route( $namespace, '/' . $path, [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
				)
		]);
	}
	public function get_item($request) {
		// $user = get_users(array('meta_key' => 'created_at', 'meta_value' => $nfc1));
		// $users = get_users(array('orderby' => 'display_name', 'fields' => 'all_with_meta'));
		$response = UserTokens::get_all_user_tokens();
	
		$created_after = $request['created_after'];
		if (empty($response)) return new WP_REST_Response("No New Users Updated.", 404);

		return new WP_REST_Response($response, 200);
	}

	public function get_items_permissions_check($request) {
		return true;
	}
}


class Movie_Quotes_Controller extends WP_REST_Controller {
	public function register_routes() {
		$namespace = 'mint/v1';
		
		$quote_pair_path = 'quote_pair';
		$get_user_id_from_token_id_path = 'get_user_id_from_token_id';
		$get_token_ids_from_user_id_path = 'get_token_ids_from_user_id';
		$add_token_id_and_user_id_to_tokens_table_path = 'add_token_id_and_user_id_to_tokens_table';

		register_rest_route( $namespace, '/' . $quote_pair_path, [
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

		register_rest_route( $namespace, '/' . $get_user_id_from_token_id_path, [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'post_token_id' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),

		]);

		register_rest_route( $namespace, '/' . $get_token_ids_from_user_id_path, [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'post_user_id' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),

		]);

		register_rest_route( $namespace, '/' . $add_token_id_and_user_id_to_tokens_table_path, [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'post_token_id_and_user_id' ),
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
	public function post_token_id($request) {
		global $IFLPartyMechanics;
		$token_id = $request->get_param( 'TOKEN_ID' );
		$user_id = $IFLPartyMechanics->get_user_id_from_token_id($token_id);

		// put the user id into options memory.
		update_option('user_id',$user_id);

		return new WP_REST_Response($user_id,200);
	}

	public function post_user_id($request) {
		global $IFLPartyMechanics;
		$user_id = $request->get_param( 'USER_ID' );
		$token_ids = $IFLPartyMechanics->get_token_ids_by_user_id($user_id);

		// put the user's token id(s) into options memory.
		update_option('token_ids',$token_ids);

		return new WP_REST_Response($token_ids,200);
	}

	public function post_token_id_and_user_id($request) {
		global $IFLPartyMechanics;
		$token_id = $request->get_param( 'TOKEN_ID' );
		$user_id = $request->get_param( 'USER_ID' );
		$result = $IFLPartyMechanics->add_token_id_and_user_id_to_tokens_table($token_id, $user_id);

		// put the result into options memory.
		update_option('result',$result);

		return new WP_REST_Response($result,200);
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
		$token_id = $request->get_param( 'token_id' );

		if (empty($token_id))
			return new WP_REST_Response("Token ID not found.", 404);

		if (empty($reader_id))
			return new WP_REST_Response("Reader ID not found.", 404);

		// Check if token_id is already in table.
		if(UserTokens::token_id_exists_in_table($token_id)) {};
			/// We just want to flash an indicator color for existing user.
			// return new WP_REST_Response("Token ID already in table.", 404);

		update_option('reader_'.$reader_id,$token_id);

		// return new WP_REST_Response("Reader ".$reader_id." Updated: ".$token_id, 200);
		return new WP_REST_Response($token_id, 200);
	}

	public function get_items_permissions_check($request) {
		return true;
	}
}


class Spirit_Knobs_Controller extends WP_REST_Controller {

	public function register_routes() {
		$namespace = 'mint/v1';
		$path = 'spirits/(?P<token_id>\d+)';

		register_rest_route( $namespace, '/' . $path, [
			// array(
			// 	'methods'             => 'GET',
			// 	'callback'            => array( $this, 'get_spirits' ),
			// 	'permission_callback' => array( $this, 'get_items_permissions_check' )
			// 	),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_spirits' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),

		]);

		/// This belongs elsewhere but we don't have time now.
		$path = 'vortex/';
		register_rest_route( $namespace, '/' . $path, [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_vortex' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'send_vortex' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),

		]);
	}

	public function send_spirits($request) {

		$token_id = $request->get_param( 'token_id' );
		$spirits = $request->get_param( 'spirits' );
		
		if (empty($token_id) ) {
			return new WP_REST_Response("Token ID not found", 404);
		}

		$user_id = UserTokens::get_user_id_from_token_id($token_id);		
		$user = get_user_by("ID",$user_id);
		if (empty($user->ID)) {
			return new WP_REST_Response("User ID not found", 404);
		}

		SpiritKnobs::update_spirits_by_user($user,$spirits);

		return new WP_REST_Response("YES",200);

	}
	public function get_vortex($request) {

		$vortex = get_option('vortexcannon');

		if ($vortex == 1) {
			update_option('vortexcannon',0);

			IFLPartyMechanics::log_action("VORTEXSENT!");
			return new WP_REST_Response("GO", 200);
		}

		return new WP_REST_Response("NO", 404);

		
	}

	public function send_vortex($request) {		

		update_option('vortexcannon',1);

		return new WP_REST_Response("SAVED", 200);
	}

	public function get_items_permissions_check($request) {
		return true;
	}
}

?>