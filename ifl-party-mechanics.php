<?php

/**
 * Plugin Name: IFL Party Mechanics
 * Plugin URI:
 * Description: This plugin manages our party antics.
 * Version: 2.0.0
 * Author: Idea Fab Labs Teams
 * Author URI: https://github.com/ideafablabs/
 * License: GPL3
 */

define("IFLPM_TABLE_PREFIX", "iflpm_");

/// Some day we might need some filesize management here.
define("IFLPM_LOGFOLDER", plugin_dir_path(__FILE__) . "logs/");
define("IFLPM_LOGFILE", IFLPM_LOGFOLDER . 'log.csv');

include 'dbmanager.php';
include 'rest-api.php';
include 'user-tokens.php';
include 'events.php';
include 'movie-quotes.php';

global $wpdb;

$IFLPartyMechanics = new IFLPartyMechanics;
$IFLPartyMechanics->run();

Class IFLPartyMechanics {    
	
	///TODO Magic Numbers!
	private $form_id = '1';
	private $email_id = '9';
	private $event_field_id = '12';
	private $attendees_list_id = '7';
	private $attended_list_id = '16';

	public $menu_options = array();

	// Default Options Array
	public $defaultOptions = array(
		
		// Basic form vars.
		'registrationform_id' => '2',
		'attendanceform_id' => '1',
		'email_id' => '9',
		'event_field_id' => '12',
		'attendees_list_id' => '7',
		'attended_list_id' => '16',

		// Ticket form vars.
		'form_event_title' => 'test',
		'form_price' => '16.00',
		'form_admin_mode' => 'disabled',
		'form_payment_method' => 'Credit Card'
	 );

	/*
	 * Action hooks
	 */
	public function run($options = array()) {
		
		global $hidden_field_name;
		
		$this->menu_options = array_merge($this->defaultOptions, $options);

		// Enqueue frontend plugin styles and scripts
		add_action('wp_enqueue_scripts', array($this, 'register_iflpm_scripts'));
		
		// Enqueue admin plugin styles and scripts
		add_action('admin_enqueue_scripts', array($this, 'register_iflpm_admin_scripts'));
		
		// Register REST API Controllers
		if (class_exists("Movie_Quotes_Controller")) {
			add_action('rest_api_init', function () {
				$movie_quotes_controller = new Movie_Quotes_Controller();
				$movie_quotes_controller->register_routes();
			});
		}
		if (class_exists("NFC_Registration_Controller")) {
			add_action('rest_api_init', function () {
				$nfc_registration_controller = new NFC_Registration_Controller();
				$nfc_registration_controller->register_routes();
			});
		}

		// Setup Ajax action hook
		add_action('wp_ajax_iflpm_async_controller', array($this, 'iflpm_async_controller'));
		add_action('wp_ajax_nopriv_iflpm_async_controller', array($this, 'iflpm_async_controller'));

		// Menu Page Setup
		add_action('admin_menu', array($this, 'admin_menu_pages'));

		add_shortcode('entry_processor', array($this, 'ifl_entry_processor'));
		add_shortcode('registrationform', array($this, 'ifl_display_registration_form'));
		add_shortcode('ticketform', array($this, 'ifl_display_purchase_form'));        
		
		// register_activation_hook( __FILE__, 'iflpm_install' );
		// register_activation_hook( __FILE__, 'iflpm_install_data' );
	 }

	/**
	 * Shortcode wrapper for displaying the admission list w NFC.
	 * Ex: [entry_processor event="Event Title Goes Here" regform="1" ]
	 */
	public function ifl_entry_processor($atts) {
		$args = shortcode_atts(array(
			'event' => $this->menu_options['form_event_title'],
			'regform' => $this->menu_options['registrationform_id'],
			'attendanceform' => $this->menu_options['attendanceform_id'],
			'event_field_id' => $this->menu_options['event_field_id']
		), $atts);

		$event = $args['event'];
		$attendanceform = $args['attendanceform'];

		$reader_id = (isset($_REQUEST['reader_id'])) ? $_REQUEST['reader_id'] : '';
		$user_email = (isset($_REQUEST['user_email'])) ? $_REQUEST['user_email'] : '';
		$token_id = (isset($_REQUEST['token_id'])) ? $_REQUEST['token_id'] : '0';
		
		$submit = (isset($_REQUEST['submit'])) ? $_REQUEST['submit'] : '0';

		$member_class = "";/// 
		$admin_guest_list_flag = "";
		$attendee_count = "";
		$attendance_count = "";
		
		// echo "SUBMIT = " . $submit . "\n"; ///

		// Begin response html string.
		$response = '<div class="iflpm-entry-processor"> <div class="ajax-message"></div>';
		$start_over_link = '<ul class="return-links">';

		// Complete with Entry GForm and go back to Entry List or Create New User again.
		if ($submit) {
				
			// Get user object by email.
			$user = get_user_by('email', $user_email);

			// Get the token from the reader memory slot.
			$token_id = get_option('reader_' . $reader_id);

			// Add pair to the database or get an error.
			$tokenadd = $this->add_token_id_and_user_id_to_tokens_table($token_id, $user->ID);
			$this->log_action($tokenadd);

			//// This probably should become if WP_error
			// if (strpos($tokenadd, 'added') !== false || strpos($tokenadd, 'already') !== false) {
			
			if (!(substr($tokenadd, 0, 5) == "Error")) {
				 
				$event_id = get_option('selected_event_id');
				
				try  {

					if (IFLPMEventsManager::add_attendee_to_event_attendance_table($user,$event_id)) {

						$response .= '<p class="success">';
						  $response .= $user->display_name . ' was successfully admitted!';
						  $response .= '</p>';

						  // Reset and send us to attendee list.
						  $user_email = "";
						  $create = "";	
					} else {

						// event add failed...?
						// Output the error message.
						$response .= '<p class="error">';
						$response .= "User has already attended.";
						$response .= '</p>';
					}

					
				} catch (Exception $e) {
						
					
					// Output the error message.
					$response .= '<p class="error">';
					$response .= $e->getMessage();
					$response .= '</p>';

				}

			} else {
				 // Adding token failed
				 
				 // Output the error message.
				 $response .= '<p class="error">';
				 $response .= $tokenadd;
				 $response .= '</p>';
			}

			$submit = 0;
		}

		// Pick Reader        
		if ($reader_id == '') {
			// echo "READER ID == ''\n"; ///
			$available_reader_count = 4; /// magic numbers

			$response .= '<ul class="reader_list list-group">';
			for ($i = 1; $i <= $available_reader_count; $i++) {
				 $response .= '<li class="list-group-item"><span class="icon-ifl-svg"></span><a class="reader_choice_button" href="./?reader_id=' . $i . '">READER ' . $i . '</a></li>';
			}
			$response .= '</ul>';
			return $response;
		} else {
			// We have the reader ID so lets give a link to get back to just before that.
			// $start_over_link .= '<li class="list-group-item"><a class="return-link reader-choice" href="./">Back to Reader Choice</a></li>';
		}

		// Create new User
		if (isset($_REQUEST['create'])) {

			// Do GF create user
			if (is_plugin_active('gravityforms/gravityforms.php')) {

				$field_values = array(
					'event' => $event,
					// 'price' => $price,
					'reader_id' => $reader_id
				);

				// Pass everything on to Gravity Forms
				$response = '<div class="form-container"><p>';
				$response .= gravity_form($args['regform'], 0, 1, 0, $field_values, 1, 0, 0);
				$response .= '</p></div';                

			} else {
				/// Error on form being active.
				$response .= '<p class="error">Form is not active.</p>';
			}

			$start_over_link .= '</ul>';
			$response .= $start_over_link.'</div>';

			return $response;
		}

		// See Entry List
		if ($user_email == '') {

			// Get the users from the DB...
			$users = get_users(array('orderby' => 'display_name', 'fields' => 'all_with_meta'));

			/// Later on we will have a switch for form entries instead of members.

			$response .= '<button class="btn-info register_button_wrap"><a class="new_registration_button" href="./?reader_id=' . $reader_id . '&create=1">Add New Member</a></button><button class="btn-info"><a class="return-link reader-choice" href="./">Back to Reader Choice</a></button>';

			$start_over_link .= '</ul>';
			$response .= $start_over_link;

			// Build search HTML.
			$response .= '<div class="member_select_search"><span class="glyphicon glyphicon-user"></span><input type="text" name="q" value="" placeholder="Search for a member..." id="q"><button  class="clear-search" onclick="document.getElementById(\'q\').value = \'\';$(\'.member_select_search #q\').focus();">X</button></div>';

			// Build list HTML
			$response .= '<ul class="member_select_list list-group">';

			// Build links for each member...
			foreach ($users as $key => $user) {

				 $formlink = './?user_email=' . $user->user_email . '&membername=' . urlencode($user->display_name) . '&reader_id=' . $reader_id;

				 $response .= '<li class="list-group-item list-group-item-action" data-sort="' . $user->display_name . '">
				 <span class="glyphicon glyphicon-user"></span>
				 <a id="' . $user->ID . '" class=" ' . $member_class . '" href="' . $formlink . '" ' . $admin_guest_list_flag . '>
				 <span class="member-displayname">' . $user->display_name . '</span>' .
					  '<span class="attendance_count alignright">' . $attendance_count . '</span>'

					  . '
				 <br /><span class="member-email">' . $user->user_email . '</span></a>
				 </li>';
			}

			$response .= '</ul></div>';
			return $response;
		} else {
			// We have the reader ID so lets give a link to get back to just after that.
			$start_over_link .= '<li ><button><a class="return-link list-choice" href="./?reader_id=' . $reader_id . '">Back to Member List</a></button></li>';
		}

		// Associate token ID with user...        
		if ($submit == '0') {
			$user = get_user_by('email', $user_email);
			/// Do a check here to see if the currently stored NFC is unassigned.

			// Setup JS to regularly check if token is assigned, If not, bring it up to assign.

			$response .= '<div class="container">';
			$response .= '<h2>' . $user->display_name . '</h2>';
			$response .= '<p>Scan medallion and click here:</p>';
			$response .= '<p><div class="token_id"></div></p>';

			$response .= '<p><div class="token-response"></div></p>';

			$response .= '<p><button data-reader_id="' . $reader_id . '" class="nfc_button"><span class="ifl-svg2"></span>Check Medallion</button></p>';
			// $response .= '<p><button data-reader_id="' . $reader_id . '" class="nfc_button">Associate Medallion</button></p>';
			$response .= '<p><a class="submit-button button" href="./?reader_id=' . $reader_id . '&user_email=' . $user_email . '&submit=1"><button>Admit Guest</button></a></p>';

			$response .= '</div>';
			// if (token_id_exists_in_table($token_id)) {}

			$start_over_link .= '</ul>';
			$response .= $start_over_link.'</div>';

			return $response;
		} else {
			// We have the user email so lets give a link to get back to just before that.
			$start_over_link .= '<li><a class="return-link list-choice" href="./?reader_id=' . $reader_id . '&user_email=' . $user_email . '">Back to Member Detail</a></li>';
		}

		// There was a problem somewhere along the way...
		$response .= '<div class="error">There was a problem somewhere along the way...</div>';
		$response .= '<ul class="list-group"><li class="list-group-item"><a class="button" href="./?reader_id=' . $reader_id . '">Back to List</a></li>';
		$response .= '<li class="list-group-item"><a class="button" href="./?reader_id=' . $reader_id . '&create">Register New</a></li></ul></div>';
		return $response;
	}

	// Async Controller
	// Easiest Overview of AJAX Setup for WP: https://stackoverflow.com/questions/17982078/returning-json-data-with-ajax-in-wordpress
	public function iflpm_async_controller() { 

		// Switch on 'request' post var
		$request = (!empty($_POST['request'])) ? $_POST['request'] : false;
		$package = (!empty($_POST['package'])) ? $_POST['package'] : false;
		$return['success'] = false;

		// echo $_POST['package'];
		// echo json_encode($_POST);
		// print_r($package['uid']);
		// wp_die();
		
		switch ($request) {
			case 'get_token':
				
				// Get the User ID from the AJAX request.
				$reader_id = (!empty($package['reader_id'])) ? $package['reader_id'] : false;
				
				// If not there, fail.
				if ($reader_id === false) {
					  $return['message'] = "No Reader Found";
					  break;
				} 

				//// remove me when ready
				$this->populate_fake_token_in_reader_memory($reader_id);///

				// Get the latest stored token from WP options table..
				$token_id = get_option('reader_'.$reader_id);
				if (!$token_id) {
					  $return['message'] = "No token ID found.";
					  break;
				}

				if (UserTokens::token_id_exists_in_table($token_id)) {
					$return['token_id'] = 0;
					$return['message'] = "Token already in system.";

				} else {
					  $return['success'] = true;
					  $return['token_id'] = $token_id;
					  $return['message'] = "New token found.";
				}
				
				break;
			case 'add_token':
				
				// Get the User ID from the AJAX request.
				$user_id = (!empty($package['uid'])) ? $package['uid'] : false;
				$reader_id = (!empty($package['reader_id'])) ? $package['reader_id'] : false;
				
				// If not there, fail.
				if ($user_id === false) {
					  $return['message'] = "No user ID Found";
					  break;
				} 

				// Get the latest stored token from WP options table..
				$token_id = get_option('token_id_'.$reader_id);
				// $token_id = rand(10000,90000); ///

				// Try and add to token table.
				/// this should be a try{}...
				$response = UserTokens::add_zone_token_to_zone_tokens_table($token_id,$user_id);
				// $response = "zing";

				// Did we fail?
				if (is_wp_error($response)) {
					  $return['message'] = $response->get_error_message();
				} else {
					  $return['success'] = true;
					  $return['token_id'] = $token_id;
					  $return['message'] = $response;
				}

				break;
			case 'remove_token':

				break;
			default:
				// err out                
				$return['message'] = "Bad request object";                
				break;
		}

		// Wrap our return response. 
		echo json_encode($return);

		// Always wp_die().
		wp_die();
	}

	/**
	 * Add Menu Page in Wordpress Admin
	 * https://www.ibenic.com/creating-wordpress-menu-pages-oop/
	 */
	public function admin_menu_pages() {

		// Originally this if statement only had the second part of the or condition, with $admin_page_call
		// as an unknown variable, so I got rid of that error as below, but if anything else needs to be done, ???
		if (!isset($admin_page_call) || $admin_page_call == '') {
			$admin_page_call = array($this, 'admin_page_call');
		}

		add_menu_page(
			__(
				 'Custom Menu Title', 'textdomain'),     // Page Title
			'Party Mechanics',                          // Menu Title
			'manage_options',                           // Required Capability
			'iflpm_main_menu',                      // Menu Slug
			$admin_page_call,                           // Function
			'dashicons-groups',  // Icon URL
			6
		);

		add_submenu_page('iflpm_main_menu',
			"Add New Event",
			"Add New Event",
			'manage_options',
			"add_new_event_page",
			array($this, 'add_new_event_page_call'));

		add_submenu_page('iflpm_main_menu',
			"Manage User Tokens",
			"Manage User Tokens",
			'manage_options',
			"iflpm_manage_user_tokens_page",
			array($this, 'manage_user_tokens_page_call')
		);

	 }

	/**
	 * Build HTML for admin page.
	 */
	public function admin_page_call() {
		if (isset($_POST['submit'])) {
			$selected_event_id = $_POST['selected_event_id'];  // Storing Selected Value In Variable
			update_option('selected_event_id', $selected_event_id);
		}

		// Echo the html here...
		
		IFLPMEventsManager::test_event_title_stuff();
		IFLPMEventsManager::test_event_registration_form_stuff();
		
		/// We need to setup install function... 
		MovieQuotes::test_user_pairings_stuff();
		MovieQuotes::test_movie_quotes_stuff();
		UserTokens::test_tokens_stuff();
		IFLPMEventsManager::test_events_table_stuff();
		IFLPMEventsManager::test_attendance_table_stuff();
		IFLPMEventsManager::test_special_guests_table_stuff();
		$this->test_option_stuff();
		//IFLPMEventsManager::test_user_dropdown();
		//IFLPMEventsManager::test_insert_some_attendees();
		IFLPMEventsManager::list_attendees_for_selected_event();
	}

	function add_new_event_page_call() {
		if (isset($_POST['submit_new_event'])) {
			$title = trim($_POST['new_event_title']);
			$date = $_POST['new_event_date'];
			if ($title == "") {
				echo "<p style='color: red; font-weight: bold'>Please enter the title for the new event</p>";
			} else if ($date == "") {
				echo "<p style='color: red; font-weight: bold'>Please select the date for the new event</p>";
			} else {
				IFLPMEventsManager::insert_event($title, $date);
				echo "<script>window.location = 'admin.php?page=iflpm_main_menu'</script>";
			}
		}

		echo "<h1>Add New Event</h1><form name='form1' method='post' action=''>
		<input type='hidden' name='hidden' value='Y'>
		<label for='new_event_title'><br><b>New event title:</b></label>
		<input type='text' name='new_event_title'/><br>
		<label for='new_event_date'><br><b>New event date:</b></label>
		<input type='date' name='new_event_date'/><br><br>
		<input type='submit' name='submit_new_event' value='Submit New Event'/>
		</form><br>";
	}

	public function manage_user_tokens_page_call() { 
		$page_name = 'manage_user_tokens_page';

		$response = ""; // Begin output.
		/// We really should switch to templates.
		
		$member_class = ""; /// ?
		
		$user_id = (isset($_GET['user_id'])) ? $_GET['user_id'] : "";
		$reader_id = (isset($_GET['reader_id'])) ? $_GET['reader_id'] : "";

		if (empty($user_id)) {

			// Get the users from the DB...
			$users = get_users(array('orderby' => 'display_name', 'fields' => 'all_with_meta'));

			// Build search HTML.
			$response .= '<div class="member_select_search"><span class="glyphicon glyphicon-user"></span><input type="text" name="q" value="" placeholder="Search for a member..." id="q"><button  class="clear-search" onclick="document.getElementById(\'q\').value = \'\';$(\'.member_select_search #q\').focus();">Clear</button></div>';

			$response .= '<div class="ajax-message"></div>';

			// Build list HTML
			// $response .= '<ul class="member_select_list list-group">';
			$response .= '<table border="0" class="iflpm-user-tokens list-group member_select_list">';
			$response .= '<tr class="member_select_list_head">
								 <th>Name</th>
								 <th>Email</th>
								 <th>Tokens</th>
								 <th>Add</th>';

			// Build links for each member...
			foreach ($users as $key => $user) {

				// $formlink = '/wp-admin/admin.php?page='.$page_name.'&user_id=' . $user->ID . '&reader_id=' . $reader_id;

				$query = array(
					'user_id' => $user->ID,                     
				);				
				$formlink = esc_url( add_query_arg( $query ) );
 
				///DUMMY ADD TOKENS
				// $res = $this->add_zone_token_to_zone_tokens_table(rand(20,10000),$user->ID);

				// Get users tokens array.
				$tokens = UserTokens::get_token_ids_by_user_id($user->ID);
				
				$response .= '<tr  class="user-'.$user->ID.'" data-user-id="'.$user->ID.'" data-sort="' . $user->display_name . '">
					<td class="user-displayname">'.$user->display_name.'</td>
					<td class="user-email">'. $user->user_email.'</td>
					<td class="user-tokens">'; 
					
				if (is_array($tokens)) {
					$response .= '<ul>';
					foreach ($tokens as $key => $token_id) {
						$token_class = UserTokens::get_token_color_class($token_id);
						$response .= '<li class="'.$token_class.'">'.$token_id.' <a class="remove-token icon" data-tid="'.$token_id.'">x</a></li>';
					}
					$response .= '</ul>';
				} else {
					$response.= '<span>'.$tokens.'</span>';   
				}
				
				$response .= '
					</td>
						<td><a class="add-token" data-uid="'.$user->ID.'">Get New Token</a><span class="new-token"></span></td>
					</tr>';
			}
			$response .= '</table>';

		} else {            

			//  We have the user ID so let's show the page where we associate the NFC Token
			$user = get_user_by('ID', $user_id);
			$token_id = get_option('token_id');
			$associate_link = '';

			$response .= '<h2>'.$user->display_name.'</h2>';
			$response .= '<p>Current Token ID:'.$token_id.'</p>';
			$response .= '<p><a href="'.$associate_link.'" title="Associate">Associate this Token with '.$user->display_name.'</a></p>';

		}
		echo $response;
	}


	/**
	 * Shortcode wrapper for displaying the ticket purchase gravity form.
	 * Ex: [ticketform form="44" event="Event Title Goes Here" price="16.00"]
	 */
//    public function ifl_display_purchase_form($atts) {
//        $args = shortcode_atts(array(
//            'form' => $this->menu_options['form_id'],
//            'event' => $this->menu_options['form_event_title'],
//            'price' => $this->menu_options['form_price'],
//            'admin' => $this->menu_options['form_admin_mode'],
//            'method' => $this->menu_options['form_payment_method']
//        ), $atts);
//
//        $field_values = array(
//            'event' => $args['event'],
//            'price' => $args['price'],
//            'method' => $args['method'],
//            'admin' => $args['admin']
//        );
//
//        $content = "<p>";
//        // Pass everything on to Gravity Forms
//        $content .= gravity_form($args['form'], 0, 1, 0, $field_values, 1, 0, 0);
//        $content .= "</p>";
//
//        return $content;
//    }

	/**
	 * Shortcode wrapper for displaying the registration gravity form.
	 * Ex: [registrationform form="1" event="Event Title Goes Here" price="16.00"]
	 */
	public function ifl_display_registration_form($atts) {
		$args = shortcode_atts(array(
			'form' => $this->menu_options['form_id'],
			'event' => $this->menu_options['form_event_title'],
			'admin' => $this->menu_options['form_admin_mode']
		), $atts);

		$field_values = array(
			'event' => $args['event'],
			// 'price' => $args['price'],
			// 'reader_id' => $args['reader_id'],
			'admin' => $args['admin']
		);

		$content = "<p>";
		// Pass everything on to Gravity Forms
		$content .= gravity_form($args['form'], 0, 1, 0, $field_values, 1, 0, 0);
		$content .= "</p>";

		// $content .= '<div class="nfc-wrapper"><button class="btn-block"><span class="ifl-svg2></span>Get NFC</button></div>';

		return $content;
	}

	 
	 

	public function test_option_stuff() {
		echo "<br><b>Reader NFC IDs in options table:</b><br>";
		for ($i = 1; $i < 5; $i++) {
			$rstr = strval($i);
			$reader_value = get_option('reader_' . "1");
			echo "Reader " . $rstr . ": " . get_option('reader_' . $rstr) . "<br>";
		}
	}

	
	public function does_table_exist_in_database($table_name) {
		global $wpdb;
		$mytables = $wpdb->get_results("SHOW TABLES");
		foreach ($mytables as $mytable) {
			foreach ($mytable as $t) {
				// echo $t . "<br>";
				if ($t == $table_name) {
					  return true;
				}
			}
		}
		return false;
	 }

	public function is_table_empty($table_name) {
		global $wpdb;
		$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
		return $rows[0]->num_rows == 0;
	}

	 

	public function delete_all_rows_from_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("TRUNCATE TABLE " . $table_name);
	}

	public function drop_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
	}

	// rest-api.php calls this
	public function get_movie_quote_by_pairing($user_id_1, $user_id_2) {
		return MovieQuotes::get_movie_quote_by_pairing($user_id_1, $user_id_2);
	}

	// rest-api.php calls this
	public function get_user_id_from_token_id($token_id) {
		// if the token ID is in the tokens table, returns associated user ID as string,
		// otherwise returns an error message
		return UserTokens::get_user_id_from_token_id($token_id);
	}

	// rest-api.php calls this
	public function get_token_ids_by_user_id($user_id) {
		// if the user ID is in the tokens table, returns associated token ID(s) as ", "-separated string,
		// otherwise returns an error message
		return UserTokens::get_token_ids_by_user_id($user_id);
	}

	public function populate_fake_token_in_reader_memory($reader_id) {
		$faketoken = rand(10000, 20000);
		update_option('reader_' . $reader_id, $faketoken);
		return $faketoken;
	}

	// rest-api.php calls this
	public function add_token_id_and_user_id_to_tokens_table($token_id, $user_id) {
		return UserTokens::add_token_id_and_user_id_to_tokens_table($token_id, $user_id);
	}

	/**
	 * Register and enqueue frontend plugin styles and scripts 
	 */
	public function register_iflpm_scripts() {        
		wp_register_script('iflpm-script', plugins_url('js/iflpm.js', __FILE__), array('jquery'), null, true);
		wp_register_style('iflpm-style', plugins_url('css/iflpm.css', __FILE__));

		wp_enqueue_style('iflpm-style');		
		wp_enqueue_script('iflpm-script');        
		wp_localize_script('iflpm-script', 'iflpm_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'check_nonce' => wp_create_nonce('iflpm-nonce')));
	}

	/**
	 * Register and enqueue frontend plugin styles and scripts 
	 */
	public function register_iflpm_admin_scripts() {        
		/// We still need to seperate admin and front end scripts.
		wp_register_script('iflpm-script', plugins_url('js/iflpm.js', __FILE__), array('jquery'), null, true);
		wp_register_style('iflpm-admin-style', plugins_url('css/iflpm-admin.css', __FILE__));

		wp_enqueue_style('iflpm-admin-style');
		wp_enqueue_script('iflpm-script');        
		wp_localize_script('iflpm-script', 'iflpm_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'check_nonce' => wp_create_nonce('iflpm-nonce')));
	}

	public function is_plugin_active($plugin_path) {
		$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

		$case = false;

		foreach ($active_plugins as $plugin) {
			if ($plugin == $plugin_path) {
				 $case = true;
			}
		}

		return $case;
	}

	public function log_action($item, $echo = 0) {
		echo "In the log function\n";
		if (!self::check_log_file_exists()) return false;

		date_default_timezone_set("America/Los_Angeles");
		$date = date("Y-m-d H:i:s");

		if (is_array($item)) {
			if (is_wp_error($item)) {
				$message = $item->get_error_message();
			} else {
				$message = implode(", ", $item);
			}
		} else {
			$message = $item;
		}

		error_log($date . ", " . $message, 3, IFLPM_LOGFILE);
		if ($echo) echo $message;
	}

	public function check_log_file_exists() {

		// Permissions?
		if (!file_exists(IFLPM_LOGFOLDER)) {
			try {
				mkdir(IFLPM_LOGFOLDER);
			} catch (Exception $e) {
				error_log($e->getMessage(), "\n");
				return false;
			}
		}
		if (!file_exists(IFLPM_LOGFILE)) {
			try {
				file_put_contents(IFLPM_LOGFILE, '');
			} catch (Exception $e) {
				error_log($e->getMessage(), "\n");
				return false;
			}
		}
		return true;
	}
	/*
	function iflpm_install() {
		global $wpdb;
		global $iflpm_db_version;
		$iflpm_db_version = '1.0';

		$table_name = $wpdb->prefix . 'movie_qutoes';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			 id mediumint(9) NOT NULL AUTO_INCREMENT,
			 time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			 name tinytext NOT NULL,
			 text text NOT NULL,
			 url varchar(55) DEFAULT '' NOT NULL,
			 UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'iflpm_db_version', $iflpm_db_version );
	}

	function iflpm_install_data() {
		global $wpdb;

		$welcome_name = 'Mr. WordPres';
		$welcome_text = 'Congratulations, you just completed the installation!';

		$table_name = $wpdb->prefix . 'liveshoutbox';

		$wpdb->insert(
			 $table_name,
			 array(
				  'time' => current_time( 'mysql' ),
				  'name' => $welcome_name,
				  'text' => $welcome_text,
			 )
		);
	}*/
}

?>
