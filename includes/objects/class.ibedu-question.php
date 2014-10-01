<?php

class IBEdu_Question {
	public $ID = 0;
	public $lesson_id = 0;
	public $question = '';
	public $question_type = '';
	public $menu_order = 0;

	/**
	 * Get instance.
	 *
	 * @param mixed $data
	 * @return IBEdu_Payment
	 */
	public static function get_instance( $data = null ) {
		if ( is_numeric( $data ) ) {
			global $wpdb;
			$data = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'ibedu_questions WHERE ID = %d', $data ) );
		}

		return new self( $data );
	}

	/**
	 * Constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data ) {
		if ( ! empty( $data ) ) {
			$this->ID = $data->ID;
			$this->lesson_id = $data->lesson_id;
			$this->question = $data->question;
			$this->question_type = $data->question_type;
			$this->menu_order = $data->menu_order;
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
		$data = array(
			'lesson_id'     => $this->lesson_id,
			'question'      => $this->question,
			'question_type' => $this->question_type,
			'menu_order'    => $this->menu_order
		);
		$data_format = array( '%d', '%s', '%s', '%d' );

		if ( is_numeric( $this->ID ) && $this->ID > 0 ) {
			$affected_rows = $wpdb->update(
				$wpdb->prefix . 'ibedu_questions',
				$data,
				array( 'ID' => $this->ID ),
				$data_format,
				array( '%d' )
			);
		} else {
			$affected_rows = $wpdb->insert(
				$wpdb->prefix . 'ibedu_questions',
				$data,
				$data_format
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
		
		if ( $wpdb->delete( $wpdb->prefix . 'ibedu_questions', array( 'ID' => $this->ID ), array( '%d' ) ) ) {
			return true;
		}

		return false;
	}
}