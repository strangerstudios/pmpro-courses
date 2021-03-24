<div class='wrap'>
	<h2><?php _e( 'PMPro Courses Settings', 'pmpro-courses' ); ?></h2>
	<p><?php _e( 'Which modules would you like to enable?', 'pmpro-courses' ); ?></p>
	<form method='POST'>
		<h3><?php __( 'Modules', 'pmpro-courses' );?></h3>
		<table class='form-table'>	
		<?php
			$modules = pmpro_courses_get_modules();            
            if( !empty( $modules ) ){
				foreach( $modules as $module ){

					$saved = get_option( 'pmpro_courses_modules', array() );
					$checked = false;
					if( in_array( $module['slug'], $saved ) ){
						$checked = true;
					}
					?>
					<tr>
						<th scope="row" valign="top">
							<label for="<?php echo esc_attr( $module['slug'] ); ?>"><?php echo esc_attr( $module['name'] ); ?></label>
						</th>
						<td>
                            <input type='checkbox' name='pmpro_courses_modules[]' value='<?php echo esc_attr( $module['slug'] ); ?>' id='<?php echo esc_attr( $module['slug'] ); ?>' <?php if( $checked ){ echo 'checked="true"'; } ?>/>
                            <label for="<?php echo esc_attr( $module['slug'] ); ?>"><?php echo esc_html( $module['description'] );?></label>
                        </td>
					</tr>
					<?php
				}
			}
		?>
		</table>	
		<br/><hr/><br/>		
		<input type='submit' name='pmpro_courses_save_settings' value='<?php _e('Save Settings', 'pmpro-courses'); ?>' class='button button-primary'/>
	</form>
</div>