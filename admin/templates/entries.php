<?php
	$api = IB_Educator::get_instance();
	$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	$args = array(
		'per_page' => 10,
		'page'     => $page
	);
	$statuses = IB_Educator_Entry::get_statuses();
	$status = isset( $_GET['status'] ) && array_key_exists( $_GET['status'], $statuses ) ? $_GET['status'] : '';

	if ( ! empty( $status ) ) {
		$args['entry_status'] = $status;
	}

	if ( ! empty( $_GET['id'] ) ) {
		$args['entry_id'] = $_GET['id'];
	}

	$entries = null;

	if ( current_user_can( 'manage_educator' ) ) {
		$entries = $api->get_entries( $args );
		$entries_count = $api->get_entries_count();
	} elseif ( current_user_can( 'educator_edit_entries' ) ) {
		// Get entries for current user's courses only.
		$courses_ids = $api->get_lecturer_courses( get_current_user_id() );

		if ( ! empty( $courses_ids ) ) {
			$args['course_id'] = $courses_ids;
			$entries = $api->get_entries( $args );
			$entries_count = $api->get_entries_count( array(
				'course_id' => $courses_ids,
			) );
		}
	}
?>
<div class="wrap">
	<h2>
		<?php _e( 'Educator Entries', 'ibeducator' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry' ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'ibeducator' ); ?></a>
	</h2>

	<ul class="subsubsub ib-edu-subnav">
		<li><a href="<?php echo admin_url( 'admin.php?page=ib_educator_entries' ); ?>"<?php if ( empty( $status ) ) echo ' class="current"'; ?>><?php _e( 'All', 'ibeducator' ); ?></a> | </li>
		<?php
			$i = 1;

			foreach ( $statuses as $key => $label ) {
				$count = isset( $entries_count[ $key ] ) ? $entries_count[ $key ]->num_rows : 0;
				echo '<li><a href="' . admin_url( 'admin.php?page=ib_educator_entries&status=' . $key ) . '"' . ( $key == $status ? ' class="current"' : '' ) . '>' . $label . ' <span class="count">(' . intval( $count ) . ')</span></a>' . ( $i < count( $statuses ) ? ' | ' : '' ) . '</li>';
				++$i;
			}
		?>
	</ul>

	<form class="ib-edu-admin-search" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
		<input type="hidden" name="page" value="ib_educator_entries">

		<div class="block">
			<label for="search-entry-id"><?php echo _x( 'ID', 'ID of an item', 'ibeducator' ); ?></label>
			<input type="text" id="search-entry-id" name="id" value="<?php if ( ! empty( $args['entry_id'] ) ) echo intval( $args['entry_id'] ); ?>">
		</div>

		<div class="block">
			<input type="submit" class="button" value="<?php _e( 'Search', 'ibeducator' ); ?>">
		</div>
	</form>

	<?php if ( $entries['rows'] ) : ?>
	<table id="ib-edu-entries-table" class="wp-list-table widefat">
		<thead>
			<th><?php _e( 'ID', 'ibeducator' ); ?></th>
			<th><?php _e( 'Student', 'ibeducator' ); ?></th>
			<th><?php _e( 'Course', 'ibeducator' ); ?></th>
			<th><?php _e( 'Status', 'ibeducator' ); ?></th>
			<th><?php _e( 'Grade', 'ibeducator' ); ?></th>
			<th><?php _e( 'Date', 'ibeducator' ); ?></th>
			<th><?php _e( 'Actions', 'ibeducator' ); ?></th>
		</thead>
		<tbody>
		<?php $i = 0; ?>
		<?php foreach ( $entries['rows'] as $entry ) : ?>
		<?php
			$student = get_user_by( 'id', $entry->user_id );
			$username = '';

			if ( $student ) {
				$username = $student->display_name;
				$username .= ' (#' . absint( $student->ID ) . ')';
			}

			$course = get_post( $entry->course_id );
			$course_title = '';

			if ( $course ) {
				$course_title = $course->post_title;
			}

			$payment = IB_Educator_Payment::get_instance( $entry->payment_id );
		?>
		<tr<?php if ( 0 == $i % 2 ) echo ' class="alternate"'; ?> data-id="<?php echo absint( $entry->ID ); ?>">
			<td><?php echo absint( $entry->ID ); ?></td>
			<td><?php echo esc_html( $username ); ?></td>
			<td><?php echo esc_html( $course_title ); ?></td>
			<td><?php echo sanitize_title( $entry->entry_status ); ?></td>
			<td><?php echo ib_edu_format_grade( $entry->grade ); ?></td>
			<td><?php echo date( 'j M, Y H:i', strtotime( $entry->entry_date ) ); ?></td>
			<td>
				<?php
					echo '<a class="ib-edu-item-edit" href="' . admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . absint( $entry->ID ) ) . '">' . __( 'Edit', 'ibeducator' ) . '</a>';
					echo ' | <a class="ib-edu-item-progress" data-entry_id="' . absint( $entry->ID ) . '" href="' . admin_url( 'admin.php?page=ib_educator_entries&edu-action=entry-progress&entry_id=' . absint( $entry->ID ) ) . '">' . __( 'Progress', 'ibeducator' ) . '</a>';

					if ( current_user_can( 'manage_educator' ) ) {
						echo ' | <a class="ib-edu-item-delete" data-entry_id="' . absint( $entry->ID ) . '" data-wpnonce="' . wp_create_nonce( 'ib_educator_delete_entry_' . absint( $entry->ID ) ) . '" href="' . admin_url( 'admin-ajax.php' ) . '">' . __( 'Delete', 'ibeducator' ) . '</a>';
					}
				?>
			</td>
		</tr>
		<?php ++$i; ?>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class="ib-edu-pagination">
	<?php
		$big = 999999999;

		echo paginate_links( array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '?paged=%#%',
			'current' => $page,
			'total' => $entries['num_pages']
		) );
	?>
	</div>

	<?php else : ?>

	<p><?php _e( 'No entries found.', 'ibeducator' ); ?></p>

	<?php endif; ?>
</div>

<script type="text/template" id="tpl-ib-edu-progress">
<td colspan="8">
	<div class="no-data-returned hidden"><?php _e( 'No data available.', 'ibeducator' ); ?></div>
</td>
</script>

<script type="text/template" id="tpl-ib-edu-progress-row">
<div class="title"><%= title %></div>
<div class="grade"><%= grade %></div>
</script>

<script>
(function($) {
	$('#ib-edu-entries-table').on('click', 'a.ib-edu-item-delete', function(e) {
		e.preventDefault();
		
		if ( confirm( '<?php _e( 'Are you sure you want to delete this item?', 'ibeducator' ); ?>' ) ) {
			var a = $(this);

			$.ajax({
				type: 'post',
				cache: false,
				data: {
					action: 'ib_educator_delete_entry',
					entry_id: a.data('entry_id'),
					_wpnonce: a.data('wpnonce')
				},
				url: a.attr('href'),
				success: function(response) {
					if (response === 'success') {
						a.closest('tr').remove();

						var entriesTable = $('#ib-edu-entries-table');

						if (!entriesTable.find('> tbody > tr').length) {
							entriesTable.hide();
						}
					}
				}
			});
		}
	});
})(jQuery);
</script>