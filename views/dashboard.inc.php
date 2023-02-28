<?php 	
	
	if (isset($_POST['update_event_id'])) {		
		
		if ( ! isset( $_POST['event-select-nonce'] ) || ! wp_verify_nonce( $_POST['event-select-nonce'], 'event-select' ) ) {
   		
   		$errors['nonce-error'] = "Nonce check failed.";
   		self::log_action($errors['nonce-error']);
		} else {
   		
			$update_selected_event_id = $_POST['update_event_id'];  
			update_option('iflpm_selected_event_id', $update_selected_event_id);
			
			self::log_action("Selected event is now event_id: ".$update_selected_event_id);
		}

	} else if (isset($_POST['submit'])) {
		///Needs Nonce
	}	

	$selected_event_id = get_option('iflpm_selected_event_id');

	/// write and use 'GET event function' instead.
	$events = $wpdb->get_results("SELECT * FROM " . EVENTS_TABLE_NAME);		

	?>
	
	<?php include 'admin-header.inc.php'  ?>

	<a href="admin.php?page=iflpm_events&amp;new" class="page-title-action">Add New Event</a>

	<?php ///self::get_it_all(); ?>
		
	<!-- <div class="loading">a</div> -->

	<form name="select_event" method="post" action="#">
		<?php wp_nonce_field('event-select','event-select-nonce'); ?>
		<select id="selected_event_id" name="update_event_id" onchange="this.form.submit()">
			<?php 

				foreach ($events as $key => $event) {				
					$pretty_date = date_format(date_create($event->date),'F j, Y');
					$selected = ($event->event_id == $selected_event_id) ? ' selected' : '';

					echo '<option value="' . strval($event->event_id) . '"' . $selected . '>' . $event->title . ' - ' . $pretty_date . '</option>';

				} ?>
		
		</select>
		<!-- <input class="button" type="submit" name="submit" value="Submit Selection Change" /> -->
	</form>
	

	<!-- <h2>active members</h2> -->
	<?php 
	// $mem = IFLPartyMechanics::import_members('https://santacruz.ideafablabs.com/wp-json/members/v1/active');
	/*
	$json = file_get_contents('https://santacruz.ideafablabs.com/wp-json/members/v1/active');
	$members = json_decode($json);
	
	// pr($this->menu_options['form_id']);
	pr($json);
	// pr($obj);

	// $event_name = 

	// Set the form ID and field ID to be updated
	$form_id = 2;
	$field_id = 12;

	$search_criteria = array( 'status' => 'active' );
	$paging = array( 'offset' => 0, 'page_size' => 300 );

	// Get all entries for the form
	// $entries = GFAPI::get_entries($form_id, array('status' => 'active', 'page_size' => 300)); // filter by status and limit to 200 entries
	$entries = GFAPI::get_entries( $form_id, $search_criteria, null, $paging );

	// pr(count($entries)); die();

	foreach ($entries as $entry) {
		$entry_id = $entry['id'];
		$value = "VillainJACK's Arcade"; // Set the new value for the field
	
		// Check if the field value matches the condition
		if ($entry[$field_id] == "VillainJACK\'s Arcade") {
			pr($entry);
			// Update the specified field for the entry
			$entry[$field_id] = $value;
			// GFAPI::update_entry($entry);
			// pr($entry);
			
		}
	}

	echo "Field updated for " . count($entries) . " entries.";			

	//die();

	foreach ($members as $key => $member) {
	    	
		$active_member_list[0]['First Name'] = $member->first_name;
		$active_member_list[0]['Last Name'] = $member->last_name;

		$entries[$key] = array(
			// 'form_id' => $this->->menu_options['ticketform_id'], 
			'form_id' => 2, 
			'9' => 'mint@ideafablabs.com',
			// $this->menu_options['event_field_id'] => $event_name,
			'12' => $event->title,
			// $this->->menu_options['attendees_list_id'] => serialize($active_member_list)
			'7' => serialize($active_member_list)
		);

		// update_entry_field( $entry_id, $input_id, $value, $item_index = '' );
		
	}	    
	pr($entries);	   
*/
	// $result = GFAPI::add_entries( $entries );


	// IFLPMEventsManager::test_event_title_stuff();
	// IFLPMEventsManager::test_event_registration_form_stuff();
	
	// SpiritKnobs::update_user_spirits_by_id();
	
	// MovieQuotes::test_user_pairings_stuff();
	// MovieQuotes::test_movie_quotes_stuff();
	// UserTokens::test_tokens_stuff();
	// IFLPMEventsManager::test_events_table_stuff();
	// IFLPMEventsManager::test_attendance_table_stuff();
	// IFLPMEventsManager::test_special_guests_table_stuff();
	// self::test_option_stuff();
	//IFLPMEventsManager::test_user_dropdown();
	//IFLPMEventsManager::test_insert_some_attendees();

	?>	
	
	<?php 
	try{
		$attendees = IFLPMEventsManager::get_attendees_for_event($selected_event_id);
		$attendance_total = count($attendees);
		?>
		
		<h2>Event Attendees</h2>
		<p class="attendance-total">Total: <?php echo $attendance_total; ?></p>
		<ul>
		
		<?php
			foreach ($attendees as $key => $attendee) {
				// pr($attendee);
				echo '<li>'.$attendee->display_name.' ['.$attendee->user_email.']</li>';
			}
		
		echo '</ul>';
	} catch (Exception $e) {
		echo $e->getMessage();
	}
?>

<?php 
	try{
		
		$guest_list = IFLPMEventsManager::get_list_of_special_guests_by_event($selected_event_id);
		$special_guest_total = count($guest_list);
		?>
		
		<h2>Special Guest List</h2>
		<p class="special-guest-total">Total: <?php echo $special_guest_total; ?></p>
		<ul class="special-guest-list member-list">
		<?php foreach ($guest_list as $guest) {
			echo '<li>' . $guest->guest_first_name . ' ' . $guest->guest_last_name . ' ['.$guest->guest_email.']</li>';
		} ?>
		</ul>
	
	<?php
	} catch (Exception $e) {
		echo $e->getMessage();
	}

?>

<?php include 'admin-footer.inc.php'; ?>
