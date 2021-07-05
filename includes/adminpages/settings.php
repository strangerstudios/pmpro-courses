<?php
	require_once( PMPRO_DIR . "/adminpages/admin_header.php" );
	?>
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Courses for Membership Settings', 'pmpro-courses' ); ?>
	</h1>
	<p><?php esc_html_e( 'Which modules would you like to enable?', 'pmpro-courses' ); ?></p>
	<form method='POST'>
		<h3><?php esc_html_e( 'Modules', 'pmpro-courses' );?></h3>
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

					$allowed_module_description_html = array(
						'a' => array (
							'href' => array(),
							'target' => array(),
							'title' => array(),
						),
					);
					?>
					<tr>
						<th scope="row" valign="top">
							<label for="<?php echo esc_attr( $module['slug'] ); ?>"><?php echo esc_html( $module['name'] ); ?></label>
						</th>
						<td>
                            <input type='checkbox' name='pmpro_courses_modules[]' value='<?php echo esc_attr( $module['slug'] ); ?>' id='<?php echo esc_attr( $module['slug'] ); ?>' <?php if( $checked ){ echo 'checked="true"'; } ?>/>
                            <label for="<?php echo esc_attr( $module['slug'] ); ?>"><?php echo esc_html( $module['title'] );?></label>
                            <p class="description"><?php echo wp_kses( $module['description'], $allowed_module_description_html ); ?></p>
                        </td>
					</tr>
					<?php
				}
			}
		?>
		</table>
		<p class="submit"><input type="submit" name="pmpro_courses_save_settings" value="<?php esc_attr_e( 'Save Settings', 'pmpro-courses' ); ?>" class="button button-primary" /></p>
	</form>
	<hr />
	<p><a href="https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=plugin&utm_medium=pmpro-courses-admin&utm_campaign=add-ons" target="_blank"><?php esc_html_e( 'Documentation', 'pmpro_courses' ); ?></a> | <a href="https://www.paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-courses-admin&utm_campaign=support" target="_blank"><?php esc_html_e( 'Support', 'pmpro_courses' ); ?></a></p>
	<?php
	require_once( PMPRO_DIR . "/adminpages/admin_footer.php" );
?>
