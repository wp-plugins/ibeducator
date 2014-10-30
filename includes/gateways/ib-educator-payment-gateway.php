<?php

abstract class IB_Educator_Payment_Gateway {
	protected $id = '';
	protected $title = '';
	protected $options = array();
	protected $values = array();

	/**
	 * Get gateway id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get gateway title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Is the gateway set as default?
	 *
	 * @return int
	 */
	public function is_default() {
		return $this->get_option( 'default' );
	}

	public function is_enabled() {
		return $this->get_option( 'enabled' );
	}

	/**
	 * Initialize gateway options.
	 *
	 * @param array $options
	 */
	public function init_options( $options = array() ) {
		$this->options['enabled'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Enabled', 'ibeducator' ),
			'id'    => 'ib-edu-enabled',
		);
		$this->options['default'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Default', 'ibeducator' ),
			'id'    => 'ib-edu-default',
		);
		$this->options = array_merge( $this->options, $options );
		$values = get_option( 'ibedu_payment_gateways', array() );
		$this->values = isset( $values[ $this->id ] ) ? $values[ $this->id ] : array();
	}

	/**
	 * Get gateway options.
	 *
	 * @param string $option_name
	 * @return mixed
	 */
	public function get_option( $option_name ) {
		if ( isset( $this->values[ $option_name ] ) ) {
			return $this->values[ $option_name ];
		}

		return null;
	}

	/**
	 * Save gateway options.
	 */
	public function save_admin_options() {
		if ( ! count( $_POST ) ) {
			return false;
		}

		$input = array();
		$gateways_options = get_option( 'ibedu_payment_gateways', array() );

		foreach ( $this->options as $option_name => $data ) {
			$value = isset( $_POST[ 'ibedu_' . $this->id . '_' . $option_name ] ) ? $_POST[ 'ibedu_' . $this->id . '_' . $option_name ] : '' ;

			if ( 'checkbox' == $data['type'] ) {
				if ( $value != 1 ) {
					$value = 0;
				}
			}

			if ( 'default' == $option_name && $value == 1 ) {
				// Clear "default" option from other gateways.
				foreach ( $gateways_options as $gateway_id => $options ) {
					$gateways_options[ $gateway_id ]['default'] = 0;
				}
			}

			$input[ $option_name ] = $value;
		}
		
		$gateways_options[ $this->id ] = $this->values = $this->sanitize_admin_options( $input );
		return update_option( 'ibedu_payment_gateways', $gateways_options );
	}

	/**
	 * Output gateway options form.
	 */
	public function admin_options_form() {
		require_once( IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-form.php' );

		foreach ( $this->options as $name => $data ) {
			$method_name = 'field_' . $data['type'];

			if ( method_exists( 'IB_Educator_Form', $method_name ) ) {
				echo call_user_func_array( array( 'IB_Educator_Form', $method_name ), array( 'ibedu_' . $this->id . '_' . $name, $this->get_option( $name ), $data ) );
			}
		}
	}
	
	/**
	 * Sanitize gateway options.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_options( $input ) {
		return $input;
	}

	/**
	 * Process payment.
	 *
	 * @param int $course_id
	 */
	public function process_payment( $course_id, $user_id ) {}

	/**
	 * Get the url to the "thank you" page.
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_redirect_url( $args ) {
		$redirect = '';

		if ( isset( $args['value'] ) ) {
			$redirect = ib_edu_get_endpoint_url( 'edu-thankyou', $args['value'], get_permalink( ib_edu_page_id( 'payment' ) ) );
		} else {
			$redirect = ib_edu_get_endpoint_url( 'edu-thankyou', '', get_permalink( ib_edu_page_id( 'payment' ) ) );
		}

		return $redirect;
	}
}