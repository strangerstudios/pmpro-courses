<?php
	$pmpro_integrations = array(
		array(
			'name' => __( 'Default', 'pmpro-courses' ),
			'slug' => 'default',
			'description' => __( 'The Course and Lesson post types bundled with PMPro Courses.', 'pmpro-courses' ),
		)
	);
	$pmproc_integrations = apply_filters( 'pmproc_settings_integrations', $pmpro_integrations );
?>
<div class='wrap'>
	<h2><?php _e( 'PMPro Courses Settings', 'pmpro-courses' ); ?></h2>
	<p><?php _e( 'Which modules would you like to enable?', 'pmpro-courses' ); ?></p>
	<form method='POST'>
		<h3><?php __( 'Modules', 'pmpro-courses' );?></h3>
		<table class='form-table'>	
		<?php
			if( !empty( $pmproc_integrations ) ){
				foreach( $pmproc_integrations as $integration ){

					$saved = pmpro_getOption( 'pmproc_integrations' );

					$saved = explode(",",$saved );

					$checked = false;
					if( in_array( $integration['slug'], $saved ) ){
						$checked = true;
					}
					?>
					<tr>
						<th scope="row" valign="top">
							<label for="<?php echo $integration['slug']; ?>"><?php echo $integration['name']; ?></label>
						</th>
						<td><input type='checkbox' name='pmproc_integrations[]' value='<?php echo $integration['slug']; ?>' id='<?php echo $integration['slug']; ?>' <?php if( $checked ){ echo 'checked="true"'; } ?>/> <label for="<?php echo $integration['slug']; ?>"><?php echo $integration['description'];?></label></td>				
					</tr>
					<?php
				}
			}
		?>
		</table>	
		<br/><hr/><br/>		
		<input type='submit' name='pmproc_save_integration_settings' value='<?php _e('Save Settings', 'pmpro-courses'); ?>' class='button button-primary'/>
	</form>
</div>
