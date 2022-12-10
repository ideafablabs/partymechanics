<?php 	

global $wpdb;

	$page_slug = 'reader_manager_page';
	
    // $selected_event_id = get_option('iflpm_selected_event_id');		
	// $user_id = (isset($_GET['user_id'])) ? $_GET['user_id'] : "";
	$reader_id = (isset($_GET['reader_id'])) ? $_GET['reader_id'] : "";

	if (isset($_POST['submit'])) {

	} ?>

	<?php include 'admin-header.inc.php'  ?>

	<?php 

	if (empty($reader_id)) {

		// Get the users from the DB...
		// $readers = get_users(array('orderby' => 'display_name', 'fields' => 'all_with_meta'));	
	?>		

		<!-- // Build links for each member... -->
		<?php //foreach ($readers as $key => $reader): ?>

		<?php //endforeach; ?>
		
		

	<?php } else {            

		//  We have the user ID so let's show the page where we associate the NFC Token
		// $user = get_user_by('ID', $user_id);
		$token_id = get_option('reader_'.$reader_id);
        if(UserTokens::token_id_exists_in_table($token_id)) {  
            $user = get_user_by('id',UserTokens::get_user_id_from_token_id($token_id));
			
            
            pr($user);
            // echo '<p>'.$user
            ?>
            


        <?php };
        
        // $reader_address = get_option('reader_address_'.$reader_id);
        // update_option('reader_'.$reader_id,$token_id);
		// $associate_link = '';

		// $response .= '<h2>'.$user->display_name.'</h2>';
		// $response .= '<p>Current Token ID:'.$token_id.'</p>';
		// $response .= '<p><a href="'.$associate_link.'" title="Associate">Associate this Token with '.$user->display_name.'</a></p>';

        // echo $response; /// this response biz was copied from elsewhere so lets fix that soon.
	}

	?>

	<?php include 'admin-footer.inc.php'; ?>
