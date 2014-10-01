<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'ibeducator' ); ?></h2>

	<?php
		settings_errors();
		IBEdu_Admin::settings_tabs( 'pages' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'ibedu_pages' );
		do_settings_sections( 'ibedu_pages' );
		submit_button();
		echo '</form>';
	?>
</div>