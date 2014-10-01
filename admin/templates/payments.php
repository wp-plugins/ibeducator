<?php
	$api = IBEdu_API::get_instance();
	$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	$statuses = IBEdu_Payment::get_statuses();
	$status = isset( $_GET['status'] ) && array_key_exists( $_GET['status'], $statuses ) ? $_GET['status'] : '';
	$args = array(
		'per_page' => 10,
		'page'     => $page
	);

	if ( ! empty( $status ) ) {
		$args['payment_status'] = array( $status );
	}

	$payments = $api->get_payments( $args );
	$payments_count = $api->get_payments_count();
?>
<div class="wrap">
	<h2><?php _e( 'Educator Payments', 'ibeducator' ); ?></h2>

	<ul class="subsubsub ibedu-subnav">
		<li><a href="<?php echo admin_url( 'admin.php?page=ibedu_payments' ); ?>"<?php if ( empty( $status ) ) echo ' class="current"'; ?>><?php _e( 'All', 'ibeducator' ); ?></a> | </li>
		<?php
			$i = 1;

			foreach ( $statuses as $key => $label ) {
				$count = isset( $payments_count[ $key ] ) ? $payments_count[ $key ]->num_rows : 0;
				echo '<li><a href="' . admin_url( 'admin.php?page=ibedu_payments&status=' . $key ) . '"' . ( $key == $status ? ' class="current"' : '' ) . '>' . $label . ' <span class="count">(' . intval( $count ) . ')</span></a>' . ( $i < count( $statuses ) ? ' | ' : '' ) . '</li>';
				++$i;
			}
		?>
	</ul>

	<?php if ( $payments['rows'] ) : ?>

	<table id="ibedu-payments-table" class="wp-list-table widefat">
		<thead>
			<th><?php _e( 'ID', 'ibeducator' ); ?></th>
			<th><?php _e( 'Student', 'ibeducator' ); ?></th>
			<th><?php _e( 'Course', 'ibeducator' ); ?></th>
			<th><?php _e( 'Amount', 'ibeducator' ); ?></th>
			<th><?php _e( 'Method', 'ibeducator' ); ?></th>
			<th><?php _e( 'Status', 'ibeducator' ); ?></th>
			<th><?php _e( 'Date', 'ibeducator' ); ?></th>
			<th><?php _e( 'Actions', 'ibeducator' ); ?></th>
		</thead>
		<tbody>
		<?php $i = 0; ?>
		<?php foreach ( $payments['rows'] as $payment ) : ?>
		<?php
			$student = get_user_by( 'id', $payment->user_id );
			$course = get_post( $payment->course_id );
			$username = '';
			if ( $student->first_name && $student->last_name ) {$username = $student->first_name . ' ' . $student->last_name;}
			else {$username = $student->display_name;}
		?>
		<tr<?php if ( 0 == $i % 2 ) echo ' class="alternate"'; ?>>
			<td><?php echo absint( $payment->ID ); ?></td>
			<td><?php echo esc_html( $username ) . ' (#' . absint( $student->ID ) . ')'; ?></td>
			<td><?php echo esc_html( $course->post_title ); ?></td>
			<td><?php echo sanitize_title( $payment->currency ) . ' ' . number_format( $payment->amount, 2 ); ?></td>
			<td><?php echo sanitize_title( $payment->payment_gateway ); ?></td>
			<td><?php echo sanitize_title( $payment->payment_status ); ?></td>
			<td><?php echo date( 'j M, Y H:i', strtotime( $payment->payment_date ) ); ?></td>
			<td>
			<?php
				echo '<a class="ibedu-item-edit" href="' . admin_url( 'admin.php?page=ibedu_payments&edu-action=edit-payment&payment_id=' . absint( $payment->ID ) ) . '">' . __( 'Edit', 'ibeducator' ) . '</a>';
				echo ' | <a class="ibedu-item-delete" data-payment_id="' . absint( $payment->ID ) . '" data-wpnonce="' . wp_create_nonce( 'ibedu_delete_payment_' . absint( $payment->ID ) ) . '" href="' . admin_url( 'admin-ajax.php' ) . '">' . __( 'Delete', 'ibeducator' ) . '</a>';
			?>
			</td>
		</tr>
		<?php ++$i; ?>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class="ibedu-pagination">
	<?php
		$big = 999999999;

		echo paginate_links( array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '?paged=%#%',
			'current' => $page,
			'total' => $payments['num_pages']
		) );
	?>
	</div>

	<?php else : ?>

	<p><?php _e( 'No payments found.', 'ibeducator' ); ?></p>

	<?php endif; ?>
</div>
<script>
(function($) {
	$('#ibedu-payments-table').on('click', 'a.ibedu-item-delete', function(e) {
		e.preventDefault();

		if ( confirm( '<?php _e( 'Are you sure you want to delete this item?', 'ibeducator' ); ?>' ) ) {
			var a = $(this);

			$.ajax({
				type: 'post',
				cache: false,
				data: {
					action: 'ibedu_delete_payment',
					payment_id: a.data('payment_id'),
					_wpnonce: a.data('wpnonce')
				},
				url: a.attr('href'),
				success: function(response) {
					if (response === 'success') {
						a.closest('tr').remove();

						var paymentsTable = $('#ibedu-payments-table');

						if (!paymentsTable.find('> tbody > tr').length) {
							paymentsTable.hide();
						}
					}
				}
			});
		}
	});
})(jQuery);
</script>