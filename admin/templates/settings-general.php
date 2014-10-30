<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'ibeducator' ); ?></h2>

	<?php
		settings_errors();
		IB_Educator_Admin::settings_tabs( 'general' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'ib_educator_settings' );
		do_settings_sections( 'ib_educator_general' );
		submit_button();
		echo '</form>';
	?>
</div>