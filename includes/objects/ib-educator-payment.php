<?php

class IB_Educator_Payment {
	public $ID = 0;
	public $course_id = 0;
	public $user_id = 0;
	public $payment_gateway = '';
	public $payment_status = '';
	public $amount = 0.00;
	public $currency = '';
	public $payment_date = '';
	protected $table_name;

	/**
	 * Get instance.
	 *
	 * @param mixed $data
	 * @return IB_Educator_Payment
	 */
	public static function get_instance( $data = null ) {
		if ( is_numeric( $data ) ) {
			global $wpdb;
			$tables = ib_edu_table_names();
			$data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . $tables['payments'] . " WHERE ID = %d", $data ) );
		}

		return new self( $data );
	}

	/**
	 * Get available statuses.
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			'pending'   => __( 'Pending', 'ibeducator' ),
			'complete'  => __( 'Complete', 'ibeducator' ),
			'failed'    => __( 'Failed', 'ibeducator' ),
			'cancelled' => __( 'Cancelled', 'ibeducator' )
		);
	}

	/**
	 * @constructor
	 *
	 * @param array $data
	 */
	public function __construct( $data ) {
		global $wpdb;
		$tables = ib_edu_table_names();
		$this->table_name = $tables['payments'];

		if ( ! empty( $data ) ) {
			$this->ID = $data->ID;
			$this->course_id = $data->course_id;
			$this->user_id = $data->user_id;
			$this->payment_gateway = $data->payment_gateway;
			$this->payment_status = $data->payment_status;
			$this->amount = $data->amount;
			$this->currency = $data->currency;
			$this->payment_date = $data->payment_date;
		}
	}

	/**
	 * Save to database.
	 *
	 * @return boolean
	 */
	public function save() {
		global $wpdb;
		$affected_rows = 0;

		if ( is_numeric( $this->ID ) && $this->ID > 0 ) {
			$affected_rows = $wpdb->update(
				$this->table_name,
				array(
					'course_id'       => $this->course_id,
					'user_id'         => $this->user_id,
					'payment_gateway' => $this->payment_gateway,
					'payment_status'  => array_key_exists( $this->payment_status, self::get_statuses() ) ? $this->payment_status : '',
					'amount'          => $this->amount,
					'currency'        => $this->currency,
					'payment_date'    => $this->payment_date
				),
				array( 'ID' => $this->ID ),
				array( '%d', '%d', '%s', '%s', '%f', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$affected_rows = $wpdb->insert(
				$this->table_name,
				array(
					'course_id'       => $this->course_id,
					'user_id'         => $this->user_id,
					'payment_gateway' => $this->payment_gateway,
					'payment_status'  => array_key_exists( $this->payment_status, self::get_statuses() ) ? $this->payment_status : '',
					'amount'          => $this->amount,
					'currency'        => $this->currency,
					'payment_date'    => empty( $this->payment_date ) ? date( 'Y-m-d H:i:s' ) : $this->payment_date
				),
				array( '%d', '%d', '%s', '%s', '%f', '%s', '%s' )
			);
			$this->ID = $wpdb->insert_id;
		}

		return ( 1 === $affected_rows || 0 === $affected_rows ) ? true : false;
	}

	/**
	 * Delete from database.
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;
		
		if ( $wpdb->delete( $this->table_name, array( 'ID' => $this->ID ), array( '%d' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Update payment status.
	 *
	 * @param string $new_status
	 * @return int Number of rows updated.
	 */
	public function update_status( $new_status ) {
		global $wpdb;
		return $wpdb->update(
			$this->table_name,
			array( 'payment_status' => $new_status ),
			array( 'ID' => $this->ID ),
			array( '%s' ),
			array( '%d' )
		);
	}
}