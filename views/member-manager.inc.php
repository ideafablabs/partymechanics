<?php 	
	
	/// Could implement WP_List_Table eventually: 
	/// https://premium.wpmudev.org/blog/wordpress-admin-tables/ 
	
	global $wpdb;

	$page_slug = 'manage_user_tokens_page';
	$selected_event_id = get_option('iflpm_selected_event_id');		
	$user_id = (isset($_GET['user_id'])) ? $_GET['user_id'] : "";
	$reader_id = (isset($_GET['reader_id'])) ? $_GET['reader_id'] : "";

	if (isset($_POST['submit'])) {

	} ?>

	<?php include 'admin-header.inc.php'  ?>

	<?php 

	if (empty($user_id)) {

		// Get the users from the DB...
		$users = get_users(array('orderby' => 'display_name', 'fields' => 'all_with_meta'));	
	?>		
		<!-- // Build search HTML. -->
		<div class="table-filter">
			<input type="text" name="q" value="" placeholder="Search for a member..." id="q">
			<button  class="clear-filter" onclick="document.getElementById('q').value = '';$('.table-filter #q').focus();">Clear</button></div>			

		<!-- // Build list HTML -->	
		<table border="0" class="iflpm-member-table list-group filterable">
		
		<tr class="member-table-head">
			<th>Name</th>
			<th>Email</th>
			<th>Tokens</th>
			<th>Add</th>

		<!-- // Build links for each member... -->
		<?php foreach ($users as $key => $user):

			// Get users tokens array.
			$tokens = UserTokens::get_token_ids_by_user_id($user->ID);

			$guest_list_class = (IFLPMEventsManager::user_is_on_guest_list($user->user_email,$selected_event_id)) ? ' special-guest' : '';
			$guest_list_action = ($guest_list_class == ' special-guest') ? 'remove' : 'add';
			
			$user_row_class = array(
				'user-'.$user->ID, 				
				'filter-item',
				$guest_list_class,
			);
			$user_row_class = trim(implode(' ',$user_row_class));
			// $user_row_class = 'user-'. $user->ID . $guest_list_class;
		?>
			
			<tr class="<?php echo $user_row_class;?>" data-user-id="<?php echo $user->ID;?>" data-sort="' . $user->display_name . '">
				<td class="user-displayname"><?php echo $user->display_name;?></td>
				<td class="user-email"><?php echo  $user->user_email;?></td>
				<td class="user-tokens">
				
			<?php if (is_array($tokens)) {
				echo  '<ul>';
				foreach ($tokens as $key => $token_id) {
					$token_class = UserTokens::get_token_color_class($token_id);
					echo '<li class="'.$token_class.'">'.$token_id.' <a class="remove-token icon" data-tid="'.$token_id.'">x</a></li>';
				}
				echo '</ul>';
			} else {
				echo '<span>'.$tokens.'</span>';   
			}
			?>
			
				</td>
					<td>
						<a class="add-token" data-uid="<?php echo $user->ID;?>">Get New Token</a><span class="new-token"></span>
						<a class="guest-list-toggle" data-uid="<?php echo $user->ID;?>" data-action="<?php echo $guest_list_action;?>" data-event="<?php echo $selected_event_id;?>">Guest List</a>
					</td>
				</tr>
		<?php endforeach; ?>
		
		</table>

	<?php } else {            

		//  We have the user ID so let's show the page where we associate the NFC Token
		$user = get_user_by('ID', $user_id);
		$token_id = get_option('token_id');
		$associate_link = '';

		$response .= '<h2>'.$user->display_name.'</h2>';
		$response .= '<p>Current Token ID:'.$token_id.'</p>';
		$response .= '<p><a href="'.$associate_link.'" title="Associate">Associate this Token with '.$user->display_name.'</a></p>';

	}

	?>

	<?php include 'admin-footer.inc.php'; ?>
