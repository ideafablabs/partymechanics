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

include 'rest-api.php';
include 'movie-quotes.php';

global $wpdb;

define("EVENTS_TABLE_NAME", $wpdb->prefix . "events");
define("EVENTS_DB_VERSION", "1.0");

define("ATTENDANCE_TABLE_NAME", $wpdb->prefix . "attendance");
define("ATTENDANCE_DB_VERSION", "1.0");

define("SPECIAL_GUESTS_TABLE_NAME", $wpdb->prefix . "special_guests");
define("SPECIAL_GUESTS_DB_VERSION", "1.0");

$IFLPartyMechanics = new IFLPartyMechanics;
$IFLPartyMechanics->run();

// See if the user has posted us some information
// If they did, this hidden field will be set to 'Y'
// variables for the field and option names
//$opt_name = 'mt_favorite_color';
//#global $hidden_field_name;
//$hidden_field_name = 'mt_submit_hidden';
//$data_field_name = 'mt_favorite_color';



Class IFLPartyMechanics
{

    // https://www.ibenic.com/creating-wordpress-menu-pages-oop/

    ///TODO Magic Numbers!
    private $form_id = '1';
    private $email_id = '9';
    private $event_field_id = '12';
    private $attendees_list_id = '7';
    private $attended_list_id = '16';


    public $defaultOptions = array(
        'form_id' => '2',
        'email_id' => '9',
        'event_field_id' => '12',
        'attendees_list_id' => '7',
        'attended_list_id' => '16',

        'form_event_title' => 'test',
        'form_price' => '16.00',
        'form_admin_mode' => 'disabled',
        'form_payment_method' => 'Credit Card',


        'slug' => '', // Name of the menu item
        'title' => '', // Title displayed on the top of the admin panel
        'page_title' => '',
        'parent' => null, // id of parent, if blank, then this is a top level menu
        'id' => '', // Unique ID of the menu item
        'capability' => 'manage_options', // User role
        'icon' => 'dashicons-admin-generic', // Menu icon for top level menus only http://melchoyce.github.io/dashicons/
        'position' => null, // Menu position. Can be used for both top and sub level menus
        'desc' => '', // Description displayed below the title
        'function' => ''

    );
    public $menu_options = array();


    /*
     * Action hooks
     */
    public function run($options = array()) {
        global $hidden_field_name;
        // for testing movie quotes functions
        // add_action( 'wp_footer', 'MovieQuotes::test_user_pairings_stuff');
        // add_action( 'wp_footer', 'MovieQuotes::test_movie_quotes_stuff');
        // add_action( 'wp_footer', 'MovieQuotes::test_tokens_stuff');


        $this->menu_options = array_merge($this->defaultOptions, $options);

        // Enqueue plugin styles and scripts
        add_action('wp_enqueue_script', array($this, 'register_iflpm_scripts'));
        add_action('wp_enqueue_script', array($this, 'enqueue_iflpm_scripts'));
        add_action('wp_enqueue_style', array($this, 'enqueue_iflpm_styles'));
        add_action('plugins_loaded', array($this, 'register_rest_api'));

        // Register REST API Controllers
        add_action('rest_api_init', function () {
            $movie_quotes_controller = new Movie_Quotes_Controller();
            $movie_quotes_controller->register_routes();
        });
        add_action('rest_api_init', function () {
            $nfc_registration_controller = new NFC_Registration_Controller();
            $nfc_registration_controller->register_routes();
        });

        // Setup Ajax action hook
        add_action('wp_ajax_ifl_sanity_check', array($this, 'ifl_sanity_check'));
        add_action('wp_ajax_nopriv_ifl_sanity_check', array($this, 'ifl_sanity_check'));
        add_action('wp_ajax_ifl_admit_guest', array($this, 'ifl_admit_guest'));
        add_action('wp_ajax_nopriv_ifl_admit_guest', array($this, 'ifl_admit_guest'));
        add_action('wp_ajax_ifl_admit_all', array($this, 'ifl_admit_all'));
        add_action('wp_ajax_nopriv_ifl_admit_all', array($this, 'ifl_admit_all'));
        add_action('wp_ajax_iflpm_get_token_from_reader', array($this, 'iflpm_get_token_from_reader'));
        add_action('wp_ajax_nopriv_iflpm_get_token', array($this, 'iflpm_get_token_from_reader'));

        add_action('wp_ajax_iflpm_associate_user_with_token_from_reader', array($this, 'iflpm_associate_user_with_token_from_reader'));
        add_action('wp_ajax_nopriv_iflpm_associate_user_with_token_from_reader', array($this, 'iflpm_associate_user_with_token_from_reader'));

        // Menu Page Setup
        add_action('admin_menu', array($this, 'wpdocs_register_my_custom_menu_page'));

        add_shortcode('registrationform', array($this, 'ifl_display_registration_form'));
        add_shortcode('ticketform', array($this, 'ifl_display_purchase_form'));
        add_shortcode('guestlist', array($this, 'ifl_display_guest_list'));
        add_shortcode('entry_processor', array($this, 'ifl_entry_processor'));


//        register_activation_hook( __FILE__, 'iflpm_install' );
//        register_activation_hook( __FILE__, 'iflpm_install_data' );
    }

    /**
     * Register plugin styles and scripts
     */
    public function register_iflpm_scripts() {
        wp_register_script('iflpm-script', plugins_url('js/iflpm.js', __FILE__), array('jquery'), null, true);
        wp_register_style('iflpm-style', plugins_url('css/iflpm.css', __FILE__));
    }

    /**
     * Enqueues plugin-specific scripts.
     */
    public function enqueue_iflpm_scripts() {
        wp_enqueue_script('iflpm-script');
        wp_localize_script('iflpm-script', 'iflpm_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'check_nonce' => wp_create_nonce('iflpm-nonce')));
    }

    /**
     * Enqueues plugin-specific styles.
     */
    public function enqueue_iflpm_styles() {
        wp_enqueue_style('iflpm-style');
    }

    /**
     * Add Menu Page in Wordpress Admin
     */
    public function wpdocs_register_my_custom_menu_page() {

        // Originally this if statement only had the second part of the or condition, with $admin_page_call
        // as an unknown variable, so I got rid of that error as below, but if anything else needs to be done, ???
        if (!isset($admin_page_call) || $admin_page_call == '') {
            $admin_page_call = array($this, 'admin_page_call');
        }

        add_menu_page(
            __(
                'Custom Menu Title', 'textdomain'),        // Page Title
            'Exhibition RSVPs',                         // Menu Title
            'manage_options',                           // Required Capability
            'my_custom_menu_page',                      // Menu Slug
            $admin_page_call,                           // Function
            plugins_url('myplugin/images/icon.png'),  // Icon URL
            6
        );

        add_submenu_page('my_custom_menu_page',
            "Add New Event",
            "Add New Event",
            'manage_options',
            "add_new_event_page",
            array($this, 'add_new_event_page_call'));

    }

    /**
     * Build HTML for admin page.
     */
    public function admin_page_call() {
//        global $hidden_field_name;
////        if (isset($_POST[ $hidden_field_name ])) { //} && $_POST[ $hidden_field_name ] == 'Y') {
//        if (isset($_POST[ "hidden" ]) && $_POST[ "hidden" ] == 'Y') {
//            $opt_val = $_POST[ 'current_event_title' ];
//            update_option('current_event_title', $opt_val);
////// Read their posted value
////    $opt_val = $_POST[$data_field_name];
////
////// Save the posted value in the database
////    update_option($opt_name, $opt_val);
//
//        }

        if (isset($_POST['submit'])) {
            $selected_event_id = $_POST['selected_event_id'];  // Storing Selected Value In Variable
            update_option('selected_event_id', $selected_event_id);
        }

        // Echo the html here...
        echo "XING!</br>";
        $this->test_event_title_stuff();
        $this->test_user_dropdown();
        $this->test_event_registration_form_stuff();
        MovieQuotes::test_user_pairings_stuff();
        MovieQuotes::test_movie_quotes_stuff();
        MovieQuotes::test_tokens_stuff();
        $this->test_events_table_stuff();
        $this->test_attendance_table_stuff();
        $this->test_special_guests_table_stuff();
        $this->test_option_stuff();
    }

    function add_new_event_page_call() {
        if (isset($_POST['submit_new_event'])) {
            $title = trim($_POST['new_event_title']);
            $date = $_POST['new_event_date'];
            if ($title == "") {
                echo "<p style='color: red; font-weight: bold'>Please enter a title for the new event</p>";
            } else if ($date == "") {
                echo "<p style='color: red; font-weight: bold'>Please select the date for the new event</p>";
            } else {
                $this->insert_event($title, $date);
                echo "<script>window.location = 'admin.php?page=my_custom_menu_page'</script>";
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

    /**
     * WPAJAX response to record whether a guest attended the party.
     */
    public function ifl_admit_guest() {

        // check_ajax_referer( 'guestlistadd_nonce', 'security' );

        $iflpm_entry_id = $_POST['entry_id'];
        $iflpm_attendee_id = $_POST['attendee_id'];

        $entry = GFAPI::get_entry($iflpm_entry_id);
        $attended_list = unserialize($entry[$this->menu_options['attended_list_id']]);

        $attended_list[$iflpm_attendee_id] = 1;

        $serialized_attended_list = serialize($attended_list);

        $result = GFAPI::update_entry_field($iflpm_entry_id, $this->menu_options['attended_list_id'], $serialized_attended_list);

        // echo 'Entry: '.$iflpm_entry_id ."\n";
        // echo 'Attendee: '.$iflpm_attendee_id ."\n";
        // echo 'Attended List'."\n";
        // pr($attended_list);
        // echo 'Serialized Attended List'."\n";
        // pr($serialized_attended_list);
        // echo 'Entry: '.$iflpm_entry_id ."\n";
        // echo 'Entry: '.$iflpm_entry_id ."\n";

        // pr($result);

        die();
    }

    /**
     * Hook into wp_ajax_ to save post ids, then display those posts using get_posts() function
     */
    public function ifl_admit_all() {

        // check_ajax_referer( 'iflpm-nonce', 'security' );

        $iflpm_entry_id = $_POST['entry_id'];
        // $iflpm_attended_id = $_POST['attended_id'];
        echo 'Admitting all for ';
        echo 'entry: ' . $iflpm_entry_id . "\n";
        // echo 'Attendee: '.$iflpm_attended_id;

        $entry = GFAPI::get_entry($iflpm_entry_id);

        $attendee_list = unserialize($entry[$this->menu_options['attendees_list_id']]);
        $attended_list = unserialize($entry[$this->menu_options['attended_list_id']]);

        // echo $this->attendees_list_id;
        // pr($attendee_list);

        foreach ($attendee_list as $key => $attendee) {

            // $iflpm_attended_id = $this->menu_options['attended_list_id'].'.'.$key;
            // $entry[$iflpm_attended_id] = 1;

            $attended_list[$key] = 1;

        }

        $serialized_attended_list = serialize($attended_list);

        $result = GFAPI::update_entry_field($iflpm_entry_id, $this->menu_options['attended_list_id'], $serialized_attended_list);

        die();

    }

    /**
     * Hook into wp_ajax_ to save post ids, then display those posts using get_posts() function
     */
    public function ifl_import_members($event_name) {

        global $wpdb;

        $users = $wpdb->get_results("SELECT first_name, last_name FROM mm_user_data WHERE status = 1 OR status = 9 ORDER BY first_name");

        // pr($users);

        // echo count($users);
        $entries = array();

        $active_member_list = array();

        foreach ($users as $key => $user) {

            $active_member_list[0]['First Name'] = $user->first_name;
            $active_member_list[0]['Last Name'] = $user->last_name;

            $entries[$key] = array(
                'form_id' => $this->menu_options['form_id'],
                '9' => 'chico@ideafablabs.com',
                $this->menu_options['event_field_id'] => $event_name,
                // $this->attendees_list_id => $active_member_list
                $this->menu_options['attendees_list_id'] => serialize($active_member_list)
            );

        }
        // pr($entries);

        $result = GFAPI::add_entries($entries);

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
            'event' => $args['$event'],
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

    /**
     * Shortcode wrapper for displaying the guest admissions list
     * Ex: [guestlist form="44" event="Event Title Goes Here" ]
     */
    public function ifl_display_guest_list($atts) {
        $args = shortcode_atts(array(
            'form_id' => $this->menu_options['form_id'],
            'event' => $this->menu_options['form_event_title'],
            'admin' => $this->menu_options['form_admin_mode'],
            // 'method' => $this->menu_options['form_payment_method']
        ), $atts);

        ///TODO:
        //  Include members in listing.
        //  Include entry ID for confirmation.
        //  Sorting list. Either first or during search.
        //  Exclude trashed entries.
        // https://www.sitepoint.com/how-to-use-ajax-in-wordpress-a-real-world-example/

        $nonce = wp_create_nonce("guestlistadd_nonce");

        $email_id = $args['email_id'];
        $event_field_id = $args['event_field_id'];
        $attendees_list_id = $args['attendees_list_id'];
        $attended_list_id = $args['attended_list_id'];

        // This adds an offset so the id number will always be way beyond the other expected form field IDs.
        $attended_id_offset = 100;

        // Get all the entries where the "event" field is the event "title"
        $search_criteria['field_filters'][] = array('key' => $event_field_id, 'value' => $args['event']);

        /// I think we are sorting via JS now...
        // $sorting = array( 'key' => $sort_field, 'direction' => 'ASC', 'is_numeric' => true );

        // $search_criteria = array();
        $sorting = array();
        $paging = array('offset' => 0, 'page_size' => 600);
        $entries = GFAPI::get_entries($args['form_id'], $search_criteria, $sorting, $paging);
        $event = get_option('current_event_title');

        // pr($entries);

        $admit_list_html = '<h2>' . $event . '</h2>';
        $admit_list_html .= '<div class="row">';
        $admit_list_html .= '<div class="member-list ifl-admit-guest small-12 columns">';
        $admit_list_html .= '<h2>' . $event . '</h2>';
        $admit_list_html .= '<div class="member_select_search"><input type="text" name="q" value="" placeholder="Search for a member..." id="q"><button  class="clear-search" onclick="document.getElementById(\'q\').value = \'\'">X</button></div>';
        $admit_list_html .= '<ul class="member_select_list list-group">';

        $attendee_count = 0;
        $admitted_count = 0;

        foreach ($entries as $entry_key => $entry) {
            // echo $entry['3.3']." - ".$entry['3.6']." <br />";
            // pr(unserialize($entry['7']));
            // pr(unserialize($entry['17']));

            // $entry_id = $entry['id'];

            // $result = GFAPI::update_entry_field( $entry_id, '35.1', "value" );
            // pr($result);

            // $attendee_id = '';
            $attendee_names = unserialize($entry[$attendees_list_id]);

            // https://www.sitepoint.com/how-to-use-ajax-in-wordpress-a-real-world-example/

            $admit_list_html .= '<li class="list-group-item" data-sort="' . $attendee_names[0]['First Name'] . '">
                <div class="entry large '
                . $attendee_class . '" ' . $admin_guest_list_flag . '>';
            // <a class="admit-all" data-entry="'.$entry['id'].'">Admit All</a>

            foreach ($attendee_names as $attendee_key => $attendee) {

                $attended_key = $attended_list_id . '.' . $attendee_key;
                if ($attendee_key == 0) $attended_key = $attended_list_id;

                if ($entry[$attended_key] == 1) {
                    $admitted = " admitted";
                    $admitted_count++;
                } else {
                    $admitted = "";
                }

                $admit_list_html .= '<a class="admit-button' . $admitted . '"  data-entry="'
                    . $entry['id'] . '" data-attended="' . $attended_key . '">'
                    . $attendee['First Name'] . ' ' . $attendee['Last Name']
                    . '</a>';
                // pr($attendee);

                $attendee_count++;
            }

            $admit_list_html .= '<span class="entry-email">' . $entry[$email_id] . ' - <span class="entry-id">#' . $entry['id'] . '</span></span>

                </div>
            </li>';
        }

        $admit_list_html .= '</ul></div></div>';
        $admit_list_html .= '<p class="attendee_count">' . $admitted_count . '/' . $attendee_count . '</p>';

        // pr($this->menu_options['form_id']);
        // pr($event_name);

        return $admit_list_html;

    }

    /**
     * Shortcode wrapper for displaying the admission list w NFC.
     * Ex: [entry_processor event="Event Title Goes Here" regform="1" ]
     */
    public function ifl_entry_processor($atts) {
        $args = shortcode_atts(array(
            'event' => $this->menu_options['form_event_title'],
            'regform' => $this->menu_options['form_id'],
            'attendanceform' => $this->menu_options['attendform_id'],
            'event_field_id' => $this->menu_options['event_field_id']
        ), $atts);

        $event = $args['event'];
        $attendanceform = $args['attendanceform'];

        $reader_id = (isset($_REQUEST['reader_id'])) ? $_REQUEST['reader_id'] : '';
        $user_email = (isset($_REQUEST['user_email'])) ? $_REQUEST['user_email'] : '';

        $nfc = (isset($_REQUEST['nfc'])) ? $_REQUEST['nfc'] : '0';
        $submit = (isset($_REQUEST['submit'])) ? $_REQUEST['submit'] : '0';
        echo "SUBMIT = " . $submit . "\n";

        // Begin response html string.
        $response = '';
        $start_over_link = '<ul class="return-links">';

        // Complete with Entry GForm and go back to Entry List or Create New User again.
        if ($submit) {
            // Get user object by email.
            $user = get_user_by('email', $user_email);

            // Get the token from the reader memory slot.
            $token_id = get_option('reader_' . $reader_id);

            // Add pair to the database or get an error.
            $tokenadd = $this->add_token_id_and_user_id_to_tokens_table($token_id, $user->ID);

            //// IF            
            // if (strpos($tokenadd, 'added') !== false || strpos($tokenadd, 'already') !== false) {
            if (!(substr($tokenadd, 0, 5) == "Error")) {
                // Do form for Attendance of this particular event...
                $input_values['input_1'] = $event;
                $input_values['input_2'] = $user->ID;
                $input_values['input_3'] = $user->display_name;

                $result = GFAPI::submit_form($attendanceform, $input_values);

                if (strpos($result['confirmation_message'], 'Thanks') !== false) {

                    $response .= '<p class="success">';
                    $response .= $user->display_name . ' was successfully admitted!';
                    $response .= '</p>';

                    // pr($result);

                    $user_email = "";
                    $create = "";
                } else {

                }
            } else {
                // Output the error message.
                $response .= '<p class="error">';
                $response .= $tokenadd;
                $response .= '</p>';


            }
        }

        // Pick Reader        
        if ($reader_id == '') {
            echo "READER ID == ''\n";
            $available_reader_count = 4;

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
//                $field_values = array(
//                    'event' => $event,
//                    // 'price' => $price,
//                    'reader_id' => $reader_id,
//                    'admin' => $admin
//                );
                $field_values = array(
                    'event' => $event,
                    // 'price' => $price,
                    'reader_id' => $reader_id
                );

                // Pass everything on to Gravity Forms
                $response = '<div class="form-container"><p>';
                $response .= gravity_form($regform, 0, 1, 0, $field_values, 1, 0, 0);
                $response .= '</p></div';

            } else {
                /// Error on form being active.
                $response .= '<p class="error">Form is not active.</p>';
            }

            $start_over_link .= '</ul>';
            $response .= $start_over_link;

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

            $response .= '</ul>';
            return $response;
        } else {
            // We have the reader ID so lets give a link to get back to just after that.
            $start_over_link .= '<li ><button><a class="return-link list-choice" href="./?reader_id=' . $reader_id . '">Back to Member List</a></button></li>';
        }

        // Associate token ID with user...        
        if ($submit == '0') {
            $user = get_user_by('email', $user_email);
            $response .= '<div class="container">';
            $response .= '<h2>' . $user->display_name . '</h2>';
            $response .= '<p>Scan medallion and click here:</p>';
            $response .= '<p><div class="token_id"></div></p>';

            $response .= '<p><div class="token-response"></div></p>';

            $response .= '<p><button data-reader_id="' . $reader_id . '" class="nfc_button" onClick="ajax_get_token_id_from_reader(' . $reader_id . ')"><span class="ifl-svg2></span>Check Medallion</button></p>';
            $response .= '<p><button data-reader_id="' . $reader_id . '" class="nfc_button" onClick="ajax_associate_medallion_with_user(' . $reader_id . ',' . $user->ID . ')">Associate Medallion</button></p>';
            $response .= '<p><a class="nfcsubmit button" href="./?reader_id=' . $reader_id . '&user_email=' . $user_email . '&submit=1&nfc=' . $reader_id . '">Send It!</a></p>';

            $response .= '</div>';
            // if (token_id_exists_in_table($token_id)) {}

            $start_over_link .= '</ul>';
            $response .= $start_over_link;

            return $response;
        } else {
            // We have the user email so lets give a link to get back to just before that.
            $start_over_link .= '<li><a class="return-link list-choice" href="./?reader_id=' . $reader_id . '&user_email=' . $user_email . '">Back to Member Detail</a></li>';
        }

        // There was a problem somewhere along the way...
        $response .= '<div class="error">There was a problem somewhere along the way...</div>';
        $response .= '<ul class="list-group"><li class="list-group-item"><a class="button" href="./?reader_id=' . $reader_id . '">Back to List</a></li>';
        $response .= '<li class="list-group-item"><a class="button" href="./?reader_id=' . $reader_id . '&create">Register New</a></li></ul><div>';
        return $response;
    }

    /**
     * AJAX Get token from reader ID in memory.
     */
    public function iflpm_get_token_from_reader() {
        $reader_id = $_GET['reader_id'];
        // echo $this->populate_fake_token_in_reader_memory($reader_id);
        echo get_option('reader_' . $reader_id);
        die();
    }

    /**
     * Reset all the attendee statuses for an event.
     */
    public function reset_attendees($event_name) {

        $search_criteria['field_filters'][] = array('key' => $this->menu_options['event_field_id'], 'value' => $event_name);
        $sorting = array();
        $paging = array('offset' => 0, 'page_size' => 900);
        $entries = GFAPI::get_entries($this->form_id, $search_criteria, $sorting, $paging);

        foreach ($entries as $key => $entry) {
            $entry[$this->attended_list_id] = "";
        }
        // $result = GFAPI::update_entry_field( $iflpm_entry_id, $iflpm_attended_id, 1 );

        $result = GFAPI::update_entries($entries);

        pr("All entries purged.");
        // die();

    }

    public function register_rest_api() {

        register_meta('user', 'fortune', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ]);

        // add_user_meta(1,'fortune','May The Force Be With You');

        add_action('rest_api_init', function () {

            // register_rest_route( 'mint/v1', '/fortune/(?P<id>\d+)', array(
            register_rest_route('mint/v1', 'fortunes/(?P<id>\d+)', array(
                'methods' => 'GET',
                'callback' => array($this, 'get_fortune')
            ));

            // register_rest_field( 'user', 'fortune', array(
            //        'get_callback' => array( $this, 'get_user_fortune' ),
            //        'update_callback' => array( $this, 'add_user_fortune' ),
            //        'schema' => null
            //    ));

        });


    }

    public function get_fortune($request) {

        $fortune = get_user_meta($request['id'], 'fortune', true);
        if (empty($fortune)) {
            return new WP_Error('empty_meta', 'there is no fortune in this cookie', array('status' => 404));
        }

        $response = new WP_REST_Response($fortune);
        $response->set_status(200);

        return $response;
    }

    public function get_user_fortune($user, $field_name, $request) {
        return get_user_meta($user['id'], $field_name, true);
    }

    public function add_user_fortune($user, $meta_value) {
        $fortune = get_user_meta($user['id'], 'fortune', false);
        if ($fortune) {
            update_user_meta($user['id'], 'fortune', $meta_value);
        } else {
            add_user_meta($user['id'], 'fortune', $meta_value, true);
        }
    }

    public function test_event_title_stuff() {
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
            echo "<option value='" . strval($result[$i]->event_id) . "' " . $selected . ">" . $result[$i]->title . " - " . $result[$i]->date . "</option>";
        }
        echo "<input type='submit' name='submit' value='Submit Selection Change' /></form>";
    }

    public function insert_event($title, $date) {
        global $wpdb;
        $wpdb->insert(
            EVENTS_TABLE_NAME,
            array(
                'title' => $title,
                'date' => $date,
            )
        );
    }

    public function test_event_registration_form_stuff() {
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

    public function test_user_dropdown() {
        global $wpdb;
        $users = get_users("orderby=display_name");
//        foreach ($users as $key => $user) {
//
//            echo $user->display_name . "<br>";
//        }
    }


    public function test_events_table_stuff() {
        global $wpdb;
        echo "<br>";
        if ($this->does_table_exist_in_database(EVENTS_TABLE_NAME)) {
            echo "Events table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . EVENTS_TABLE_NAME);
            echo "Events table contains " . $rows[0]->num_rows . " records.<br>";
            //$this->insert_event("Doublemint", "2019-03-30");
        } else {
            echo "Events table does not exist, creating Events table<br>";
            $this->create_events_table();
        }

    }

    public function test_attendance_table_stuff() {
        global $wpdb;
        echo "<br>";
        if ($this->does_table_exist_in_database(ATTENDANCE_TABLE_NAME)) {
            echo "Attendance table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . ATTENDANCE_TABLE_NAME);
            echo "Attendance table contains " . $rows[0]->num_rows . " records.<br>";
        } else {
            echo "Attendance table does not exist, creating Attendance table<br>";
            $this->create_attendance_table();
        }

    }

    public function test_special_guests_table_stuff() {
        global $wpdb;
        echo "<br>";
        if ($this->does_table_exist_in_database(SPECIAL_GUESTS_TABLE_NAME)) {
            echo "Special Guests table exists<br>";
            $rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . SPECIAL_GUESTS_TABLE_NAME);
            echo "Special Guests table contains " . $rows[0]->num_rows . " records.<br>";
        } else {
            echo "Special Guests table does not exist, creating Special Guests table<br>";
            $this->create_special_guests_table();
        }

    }

    public function test_option_stuff() {
        echo "<br><b>Reader NFC IDs in options table:</b><br>";
        for ($i = 1; $i < 5; $i++) {
            $rstr = strval($i);
            $reader_value = get_option('reader_' . "1");
            echo "Reader " . $rstr . ": " . get_option('reader_' . $rstr) . "<br>";
        }
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

    public function create_events_table() {
        global $wpdb;
        global $events_db_version;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE EVENTS_TABLE_NAME (
              event_id mediumint(9) NOT NULL AUTO_INCREMENT,
              title tinytext NOT NULL,
              date date NOT NULL,
              PRIMARY KEY  (event_id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('events_db_version', $events_db_version);
    }

    public function create_attendance_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . ATTENDANCE_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              user_id bigint(20) unsigned NOT NULL,
              event_id mediumint(9) NOT NULL,
              PRIMARY KEY  (record_id),
              FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
              FOREIGN KEY  (event_id) REFERENCES wp_events(event_id)
            ) " . $charset_collate . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('attendance_db_version', ATTENDANCE_DB_VERSION);
    }

    public function create_special_guests_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE " . SPECIAL_GUESTS_TABLE_NAME . " (
              record_id mediumint(9) NOT NULL AUTO_INCREMENT,
              user_id bigint(20) unsigned NOT NULL,
              event_id mediumint(9) NOT NULL,
              PRIMARY KEY  (record_id),
              FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
              FOREIGN KEY  (event_id) REFERENCES wp_events(event_id)
            ) " . $charset_collate . ";";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('special_guests_db_version', SPECIAL_GUESTS_DB_VERSION);
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
        return MovieQuotes::get_user_id_from_token_id($token_id);
    }

    // rest-api.php calls this
    public function get_token_ids_by_user_id($user_id) {
        // if the user ID is in the tokens table, returns associated token ID(s) as ", "-separated string,
        // otherwise returns an error message
        return MovieQuotes::get_token_ids_by_user_id($user_id);
    }

    public function populate_fake_token_in_reader_memory($reader_id) {
        $faketoken = rand(10000, 20000);
        update_option('reader_' . $reader_id, $faketoken);
        return $faketoken;
    }

    // AJAX call.
    public function iflpm_associate_user_with_token_from_reader() {
        $user_id = $_GET['user_id'];
        $reader_id = $_GET['reader_id'];

        $token_id = get_option('reader_' . $reader_id);
        $response = $this->add_token_id_and_user_id_to_tokens_table($token_id, $user_id);
        echo $response;
        die();
    }

    // rest-api.php calls this
    public function add_token_id_and_user_id_to_tokens_table($token_id, $user_id) {
        return MovieQuotes::add_token_id_and_user_id_to_tokens_table($token_id, $user_id);
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