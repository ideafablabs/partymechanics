<?php 	
	
if (isset($_POST['submit'])) {

	if ( ! isset( $_POST['settings-submit-nonce'] ) || ! wp_verify_nonce( $_POST['settings-submit-nonce'], 'settings-submit' ) ) {
		
		$errors['nonce-error'] = "Nonce check failed.";
		self::log_action($errors['nonce-error']);
	
	} else {

		$update_token_reader_count = $_POST['token_reader_count'];  
		update_option('iflpm_token_reader_count', $update_token_reader_count);
		
		self::log_action("IFLPM Settings Updated Successfully");
	}
}	

$token_reader_count = get_option('iflpm_token_reader_count');

?>

<?php include 'admin-header.inc.php'  ?>

<form name="iflpm_settings" method="post" action="#">
	<?php wp_nonce_field('settings-submit','settings-submit-nonce'); ?>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th><label for="token_reader_count">Token Reader Count</label></th>
				<td><input name="token_reader_count" id="token_reader_count" value="<?php echo $token_reader_count; ?>" type="text" class="regular-text" /></td>
			</tr>
		</tbody>	
	</table>
	
	<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
	
</form>

<?php include 'admin-footer.inc.php'; ?>
