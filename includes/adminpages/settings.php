<?php
	if ( defined( 'PMPRO_DIR' ) ) {
		require_once( PMPRO_DIR . "/adminpages/admin_header.php" );
	}
?>
<hr class="wp-header-end" />

<h1>
	<?php esc_html_e( 'Courses for Membership Settings', 'pmpro-courses' ); ?>
</h1>
<p>
	<?php esc_html_e( 'Create courses with lessons and manage member access natively in Paid Memberships Pro or integrate with the most popular LMS plugins for WordPress.', 'pmpro-courses' ); ?>
	<?php
		echo '<a href="https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/?utm_source=plugin&utm_medium=pmpro-courses-settings&utm_campaign=documentation" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Learn more about the Courses Add On for Paid Memberships Pro', 'pmpro-courses' ) . '</a>';
	?>
</p>

<form action="" method="post">
	<div id="pmpro-courses-module-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Courses Module Settings', 'pmpro-courses' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<table class="wp-list-table widefat striped">
				<?php
					$modules = pmpro_courses_get_modules();
					if ( ! empty( $modules ) ) {
						foreach ( $modules as $module ) {
							$saved = get_option( 'pmpro_courses_modules', array() );
							$checked = false;
							if ( in_array( $module['slug'], $saved ) ) {
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
							<tr class="pmpro-courses-<?php echo esc_attr( $module['slug'] );?>">
								<th scope="row">
									<strong><?php echo esc_html( $module['name'] ); ?></strong>
								</th>
								<td>
									<input type="checkbox" name="pmpro_courses_modules[]" value="<?php echo esc_attr( $module['slug'] ); ?>" id="<?php echo esc_attr( $module['slug'] ); ?>" <?php if ( $checked ) { echo 'checked="true"'; } ?>/>
									<label for="<?php echo esc_attr( $module['slug'] ); ?>"><?php echo esc_html( $module['title'] ); ?></label>
									<p class="description"><?php echo wp_kses( $module['description'], $allowed_module_description_html ); ?></p>
									<?php
										do_action( 'pmpro_courses_module_settings', $module );
									?>
								</td>
							</tr>
							<?php
						}
					}
				?>
			</table>
			<p class="submit">
				<input type="submit" name="pmpro_courses_save_settings" value="<?php esc_attr_e( 'Save Settings', 'pmpro-courses' ); ?>" class="button button-primary" />
			</p>
		</div>
	</div>
</form>

<?php
	if ( defined( 'PMPRO_DIR' ) ) {
		require_once( PMPRO_DIR . "/adminpages/admin_footer.php" );
	}
