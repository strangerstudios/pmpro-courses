<?php

$pmproc_integrations = apply_filters( 'pmproc_settings_integrations', array() );

?>
<div class='wrap'>
	<form method='POST'>		
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
					<h3><?php echo $integration['name']; ?></h3>
					<table class='form-table'>
						<tr>
							<td width='10px'><input type='checkbox' name='pmproc_integrations[]' value='<?php echo $integration['slug']; ?>' id='<?php echo $integration['slug']; ?>' <?php if( $checked ){ echo 'checked="true"'; } ?>/></td>
							<td><label for='<?php echo $integration['slug']; ?>'><?php _e('Enable','pmpro-courses'); ?></label></td>
						</tr>
					</table>	
					<br/><hr/><br/>
					<?php
				}
			}
		?>		
		<input type='submit' name='pmproc_save_integration_settings' value='<?php _e('Save Settings', 'pmpro-courses'); ?>' class='button button-primary'/>
	</form>
</div>
