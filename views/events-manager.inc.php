<?php 	
	
if (isset($_POST['submit_new_event'])) {

	if ( ! isset( $_POST['event-submit-nonce'] ) || ! wp_verify_nonce( $_POST['event-submit-nonce'], 'event-submit' ) ) {
		
		$errors['nonce-error'] = "Nonce check failed.";
		self::log_action($errors['nonce-error']);		
	} else {
		
		if (isset($_POST['submit_new_event'])) {
			$title = trim($_POST['new_event_title']);
			$date = $_POST['new_event_date'];
			
			if ($title == "") {
				$errors['event-title-error'] = "Please enter the title for the new event.";
				// echo "<p style='color: red; font-weight: bold'>Please enter the title for the new event</p>";
			} else if ($date == "") {
				$errors['event-title-error'] = "Please select the date for the new event.";
				// echo "<p style='color: red; font-weight: bold'>Please select the date for the new event</p>";
			} else {
				IFLPMEventsManager::insert_event($title, $date);
				self::log_action("Event ".$title." created successfully");

				echo "<script>window.location = 'admin.php?page=iflpm_dashboard'</script>";

			}	

		}
		
	}
}	

?>

<?php include 'admin-header.inc.php'  ?>

<?php pr($errors); ?>

<form name="form1" method="post" action="">
	<?php wp_nonce_field('event-submit','event-submit-nonce'); ?>
	<input type="hidden" name="hidden" value="Y">
	<label for="new_event_title"><br /><b>New event title:</b></label>
	<input type="text" name="new_event_title"/><br>
	<label for="new_event_date"><br /><b>New event date:</b></label>
	<input type="date" name="new_event_date"/><br><br>
	<input type="submit" name="submit_new_event" value="Submit New Event"/>
</form>

<?php include 'admin-footer.inc.php'; ?>
