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
	
	<?php 

	// IFLPMEventsManager::test_event_title_stuff();
	// IFLPMEventsManager::test_event_registration_form_stuff();
	
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
				echo '<li>'.$attendee->display_name.'</li>';
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
		<?php foreach ($guest_list as $key => $user) {
			echo "<li>".$user->display_name." - ".$user->user_email."</li>";	
		} ?>
		</ul>
	
	<?php 
	} catch (Exception $e) {
		echo $e->getMessage();
	}

?>

<?php include 'admin-footer.inc.php'; ?>
