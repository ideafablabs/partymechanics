<?php 

/**
 * Plugin Name: IFL Admit Guests
 * Plugin URI: 
 * Description: This plugin allow you check in guests who've signed up through Gravity Forms
 * Version: 2.0.0
 * Author: Jordan Layman
 * Author URI: https://github.com/factor8/
 * License: GPL3

*/

include 'rest-api.php';

$IFLPartyMechanics = new IFLPartyMechanics;
$IFLPartyMechanics->run();

Class IFLPartyMechanics {  

    // https://www.ibenic.com/creating-wordpress-menu-pages-oop/

    ///TODO Magic Numbers!
    private $form_id = '1';
    private $email_id = '9';
    private $event_field_id = '12';
    private $attendees_list_id = '7';    
    private $attended_list_id = '16'; 


    public $defaultOptions = array(
        'form_id' => '1',
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

        add_action( 'wp_footer', array( $this, 'show_table_names' )  );


        $this->menu_options = array_merge( $this->defaultOptions, $options );

        // Enqueue plugin styles and scripts
        add_action( 'plugins_loaded', array( $this, 'register_iflpm_scripts' ) );
        add_action( 'plugins_loaded', array( $this, 'enqueue_iflpm_scripts' ) );
        add_action( 'plugins_loaded', array( $this, 'enqueue_iflpm_styles' ) );      
        add_action( 'plugins_loaded', array( $this, 'register_rest_api' ) );      

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
        add_action( 'wp_ajax_ifl_sanity_check', array( $this, 'ifl_sanity_check' ) );
        add_action( 'wp_ajax_nopriv_ifl_sanity_check', array( $this, 'ifl_sanity_check' ) );
        add_action( 'wp_ajax_ifl_admit_guest', array( $this, 'ifl_admit_guest' ) );
        add_action( 'wp_ajax_nopriv_ifl_admit_guest', array( $this, 'ifl_admit_guest' ) );
        add_action( 'wp_ajax_ifl_admit_all', array( $this, 'ifl_admit_all' ) );
        add_action( 'wp_ajax_nopriv_ifl_admit_all', array( $this, 'ifl_admit_all' ) );

        // Menu Page Setup
        add_action( 'admin_menu', array( $this, 'wpdocs_register_my_custom_menu_page' ) );

        add_shortcode( 'registrationform', array( $this,'ifl_display_registration_form') );
        add_shortcode( 'ticketform', array( $this,'ifl_display_purchase_form') );
        add_shortcode( 'guestlist', array( $this,'ifl_display_guest_list') );

        register_activation_hook( __FILE__, 'iflpm_install' );
        register_activation_hook( __FILE__, 'iflpm_install_data' );
    }   

    /**
     * Register plugin styles and scripts
     */
    public function register_iflpm_scripts() {
        wp_register_script( 'iflpm-script', plugins_url( 'js/iflpm.js', __FILE__ ), array('jquery'), null, true );
        wp_register_style( 'iflpm-style', plugins_url( 'css/iflpm.css', __FILE__ ) );
    }   
    /**
     * Enqueues plugin-specific scripts.
     */
    public function enqueue_iflpm_scripts() {        
        wp_enqueue_script( 'iflpm-script' );

        wp_localize_script( 'iflpm-script', 'iflpm_ajax', array( 'ajax_url' => admin_url('admin-ajax.php'), 'check_nonce' => wp_create_nonce('iflpm-nonce') ) ); 
    }   
    /**
     * Enqueues plugin-specific styles.
     */
    public function enqueue_iflpm_styles() {         
        wp_enqueue_style( 'iflpm-style' ); 
    }

    /**
    * Add Menu Page in Wordpress Admin
    */
    public function wpdocs_register_my_custom_menu_page(){
        
        if( $admin_page_call == '' ) {
            $admin_page_call = array( $this, 'admin_page_call' );
        }

        add_menu_page( 
            __( 
            'Custom Menu Title', 'textdomain' ),        // Page Title
            'Exhibition RSVPs',                         // Menu Title   
            'manage_options',                           // Required Capability
            'my_custom_menu_page',                      // Menu Slug
            $admin_page_call,                           // Function
            plugins_url( 'myplugin/images/icon.png' ),  // Icon URL
            6
        ); 
    }    

    /**
    * Build HTML for admin page.
    */
    public function admin_page_call() {
         // Echo the html here...
        echo "XING!";
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

        $result = GFAPI::update_entry_field( $iflpm_entry_id, $this->menu_options['attended_list_id'], $serialized_attended_list );
        
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
        echo 'entry: '.$iflpm_entry_id ."\n";
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

        $result = GFAPI::update_entry_field( $iflpm_entry_id, $this->menu_options['attended_list_id'], $serialized_attended_list );
        
        die();

    }

    /**
    * Hook into wp_ajax_ to save post ids, then display those posts using get_posts() function
    */
    public function ifl_import_members($event_name) {

        global $wpdb;
        
        $users = $wpdb->get_results( "SELECT first_name, last_name FROM mm_user_data WHERE status = 1 OR status = 9 ORDER BY first_name" );
        
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

        $result = GFAPI::add_entries( $entries );

    }

    /**
    * Shortcode wrapper for displaying the ticket purchase gravity form.
    * Ex: [ticketform form="44" event="Event Title Goes Here" price="16.00"]
    */
    public function ifl_display_purchase_form( $atts ) {
        extract( shortcode_atts( array( 
            'form' => $this->menu_options['form_id'],
            'event' => $this->menu_options['form_event_title'],
            'price' => $this->menu_options['form_price'],
            'admin' => $this->menu_options['form_admin_mode'],
            'method' => $this->menu_options['form_payment_method']
        ), $atts ) );

        $field_values = array( 
            'event' => $event,
            'price' => $price,
            'method' => $method,
            'admin' => $admin
        );
        // echo '<p>[gravityform id="'.$atts['form'].'" title="false" description="true" ajax="true" field_values=\'price=16.00&event='.$atts['event'].'\']</p>';

        $content = "<p>";
        // Pass everything on to Gravity Forms

        // gravity_form( $id_or_title, $display_title = true, $display_description = true, $display_inactive = false, $field_values = null, $ajax = false, $tabindex, $echo = true );
        $content .= gravity_form($form,0,1,0,$field_values,1,0,0);
        // echo do_shortcode('[gravityform id="'.$atts['form'].'" title="false" description="true" ajax="true" field_values=\'price=16.00&event='.$atts['event'].'\']' );
        $content .= "</p>";

        // pr($this->menu_options);

        return $content;
    }

    /**
    * Shortcode wrapper for displaying the registration gravity form.
    * Ex: [registrationform form="1" event="Event Title Goes Here" price="16.00"]
    */
    public function ifl_display_registration_form( $atts ) {
        extract( shortcode_atts( array( 
            'form' => $this->menu_options['form_id'],
            // 'reader_id' => $this->menu_options['reader_id'],
            // 'event' => $this->menu_options['form_event_title'],
            // 'price' => $this->menu_options['form_price'],
            'admin' => $this->menu_options['form_admin_mode'],
            // 'method' => $this->menu_options['form_payment_method']
        ), $atts ) );

        $field_values = array( 
            // 'event' => $event,
            // 'price' => $price,
            // 'reader_id' => $reader_id,
            'admin' => $admin
        );
        // echo '<p>[gravityform id="'.$atts['form'].'" title="false" description="true" ajax="true" field_values=\'price=16.00&event='.$atts['event'].'\']</p>';

        $reader_id = $_GET['reader_id'];

        $content = '<div class="nfc-wrapper reader-'.$reader_id.'"><button>Get NFC</button></div>';

        $content .= "<p>";
        // Pass everything on to Gravity Forms

        // gravity_form( $id_or_title, $display_title = true, $display_description = true, $display_inactive = false, $field_values = null, $ajax = false, $tabindex, $echo = true );
        $content .= gravity_form($form,0,1,0,$field_values,1,0,0);
        // echo do_shortcode('[gravityform id="'.$atts['form'].'" title="false" description="true" ajax="true" field_values=\'price=16.00&event='.$atts['event'].'\']' );
        $content .= "</p>";

        // pr($this->menu_options);

        return $content;
    }

    /**
    * Shortcode wrapper for displaying the guest admissions list
    * Ex: [guestlist form="44" event="Event Title Goes Here" ]
    */
    public function ifl_display_guest_list( $atts ) {
        extract( shortcode_atts( array( 
            'form_id' => $this->menu_options['form_id'],
            'event' => $this->menu_options['form_event_title']
            // 'admin' => $this->menu_options['form_admin_mode'],
            // 'method' => $this->menu_options['form_payment_method']
        ), $atts ) );

        // echo '<p>[gravityform id="'.$atts['form'].'" title="false" description="true" ajax="true" field_values=\'price=16.00&event='.$atts['event'].'\']</p>';
        
        ///TODO: 
        //  Include members in listing.
        //  Include entry ID for confirmation.
        //  Sorting list. Either first or during search.
        //  Exclude trashed entries.
        // https://www.sitepoint.com/how-to-use-ajax-in-wordpress-a-real-world-example/

        $nonce = wp_create_nonce("guestlistadd_nonce");
                
        $email_id = $this->menu_options['email_id'];
        $event_field_id = $this->menu_options['event_field_id'];
        $attendees_list_id = $this->menu_options['attendees_list_id'];    
        $attended_list_id = $this->menu_options['attended_list_id'];

        // This adds an offset so the id number will always be way beyond the other expected form field IDs.
        $attended_id_offset = 100;

        // Get all the entries where the "event" field is the event "title"
        $search_criteria['field_filters'][] = array( 'key' => $event_field_id, 'value' => $event );

        /// I think we are sorting via JS now...
        // $sorting = array( 'key' => $sort_field, 'direction' => 'ASC', 'is_numeric' => true );

        // $search_criteria = array();
        $sorting = array();
        $paging = array( 'offset' => 0, 'page_size' => 600 );
        $entries = GFAPI::get_entries( $form_id, $search_criteria, $sorting, $paging);

        // pr($entries);

        $admit_list_html .=  '<h2>'.$event_name.'</h2>';
        $admit_list_html .= '<div class="row">';        
        $admit_list_html .= '<div class="member-list ifl-admit-guest small-12 columns">';       
        $admit_list_html .= '<h2>'.$event.'</h2>';
        $admit_list_html .= '<div class="member_select_search"><input type="text" name="q" value="" placeholder="Search for a member..." id="q"><button  class="clear-search" onclick="document.getElementById(\'q\').value = \'\'">X</button></div>';
        $admit_list_html .= '<ul class="member_select_list">';
          
         // pr($entries[3]);

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

            // $formlink = 'http://www.ideafablabs.com/confirm-admit?attendee_id='.$attendee_id.'&membername='.urlencode($attendee_name);     
            // pr($entry);

            $admit_list_html .= '<li data-sort="'.$attendee_names[0]['First Name'].'">
                <div class="entry large '
                    .$attendee_class.'" '.$admin_guest_list_flag.'>';
                    // <a class="admit-all" data-entry="'.$entry['id'].'">Admit All</a>

                    foreach ($attendee_names as $attendee_key => $attendee) {
                        
                        $attended_key = $attended_list_id.'.'.$attendee_key;
                        if ($attendee_key == 0) $attended_key = $attended_list_id;
                        
                        if ($entry[$attended_key] == 1) {
                            $admitted = " admitted";
                            $admitted_count++;
                        } else {
                            $admitted = "";
                        }               

                        $admit_list_html .= '<a class="admit-button'.$admitted.'"  data-entry="'
                        .$entry['id'].'" data-attended="'.$attended_key.'">'
                        .$attendee['First Name'].' '.$attendee['Last Name']
                        .'</a>';
                        // pr($attendee);

                        $attendee_count++;
                    }                
                
                $admit_list_html .= '<span class="entry-email">'.$entry[$email_id].' - <span class="entry-id">#'.$entry['id'].'</span></span>

                </div>
            </li>';
        }

        $admit_list_html .=  '</ul></div></div>';
        $admit_list_html .=  '<p class="attendee_count">'.$admitted_count.'/'.$attendee_count.'</p>';

        // pr($this->menu_options['form_id']);
        // pr($event_name);
        
        return $admit_list_html;

    }


    /*

    Array
    (
        [0] => Array
            (
                [First Name] => Santa Cruz
                [Last Name] => Santa Cruz
            )

        [1] => Array
            (
                [First Name] => Or Such
                [Last Name] => Or Such
            )

    )

    /*


    /**
    * Reset all the attendee statuses for an event. 
    */
    public function reset_attendees($event_name) {
              
        $search_criteria['field_filters'][] = array( 'key' => $event_field_id, 'value' => $event_name );
        $sorting = array();
        $paging = array( 'offset' => 0, 'page_size' => 900 );
        $entries = GFAPI::get_entries( $this->form_id, $search_criteria, $sorting, $paging);

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

        add_action( 'rest_api_init', function () {

            // register_rest_route( 'mint/v1', '/fortune/(?P<id>\d+)', array(
            register_rest_route( 'mint/v1', 'fortunes/(?P<id>\d+)',array(
                'methods'  => 'GET',
                'callback' => array($this,'get_fortune')
            ));
            
            // register_rest_field( 'user', 'fortune', array(
         //        'get_callback' => array( $this, 'get_user_fortune' ),
         //        'update_callback' => array( $this, 'add_user_fortune' ),
         //        'schema' => null
      //    ));        
            
        } );

            
    }

    public function get_fortune($request) {

        $fortune = get_user_meta( $request[ 'id' ], 'fortune', true );
        if (empty($fortune)) {
            return new WP_Error( 'empty_meta', 'there is no fortune in this cookie', array('status' => 404) );
        }      

        $response = new WP_REST_Response($fortune);
        $response->set_status(200);

        return $response;
    }

    public function get_user_fortune( $user, $field_name, $request ) { 
        return get_user_meta( $user[ 'id' ], $field_name, true );
    }

    public function add_user_fortune( $user, $meta_value ) { 
        $fortune = get_user_meta( $user[ 'id' ], 'fortune', false );
        if( $fortune ) {
            update_user_meta( $user[ 'id' ], 'fortune', $meta_value );
        } else {
            add_user_meta( $user[ 'id' ], 'fortune', $meta_value, true );
        }
    }

    public function show_table_names() {
        global $wpdb;
        $mytables=$wpdb->get_results("SHOW TABLES");
        foreach ($mytables as $mytable)
        {
            foreach ($mytable as $t)
            {
                echo $t . "<br>";
            }
        }
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
