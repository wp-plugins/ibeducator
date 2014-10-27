<?php

class IB_Educator_Form {
	/**
	 * Get field description HTML.
	 *
	 * @param array $data
	 * @return string
	 */
	public static function description_html( $data ) {
		if ( ! empty( $data['description'] ) ) {
			return '<div class="description">' . $data['description'] . '</div>';
		}

		return '';
	}

	/**
	 * Get text field.
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $data
	 * @return string
	 */
	public static function field_text( $name, $value, $data ) {
		$data = wp_parse_args( $data, array(
			'label'       => '',
			'class'       => 'regular-text',
			'description' => '',
			'id'          => '',
		) );
		$for = empty( $data['id'] ) ? '' : ' for="' . esc_attr( $data['id'] ) . '"';
		$id = empty( $data['id'] ) ? '' : ' id="' . esc_attr( $data['id'] ) . '"';
		$output = '<div class="ibedu-field">';
		$output .= '<div class="ibedu-label"><label' . $for . '>' . $data['label'] . '</label></div>';
		$output .= '<div class="ibedu-control"><input type="text" class="' . esc_attr( $data['class'] ) . '"' . $id . ' name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		$output .= self::description_html( $data );
		$output .= '</div></div>';
		return $output;
	}

	/**
	 * Get checkbox field.
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $data
	 * @return string
	 */
	public static function field_checkbox( $name, $value, $data ) {
		$data = wp_parse_args( $data, array(
			'label'       => '',
			'class'       => '',
			'description' => '',
			'id'          => '',
		) );
		$for = empty( $data['id'] ) ? '' : ' for="' . esc_attr( $data['id'] ) . '"';
		$id = empty( $data['id'] ) ? '' : ' id="' . esc_attr( $data['id'] ) . '"';
		$output = '<div class="ibedu-field">';
		$output .= '<div class="ibedu-label"><label' . $for . '>' . $data['label'] . '</label></div>';
		$output .= '<div class="ibedu-control"><input type="checkbox"' . $id . ' name="' . esc_attr( $name ) . '" value="1"' . checked( $value, true, false ) . '>';
		$output .= self::description_html( $data );
		$output .= '</div></div>';
		return $output;
	}

	/**
	 * Get textarea field.
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $data
	 * @return string
	 */
	public static function field_textarea( $name, $value, $data ) {
		$data = wp_parse_args( $data, array(
			'label'       => '',
			'class'       => 'large-text code',
			'description' => '',
			'cols'        => 40,
			'rows'        => 4,
			'id'          => '',
		) );
		$for = empty( $data['id'] ) ? '' : ' for="' . esc_attr( $data['id'] ) . '"';
		$id = empty( $data['id'] ) ? '' : ' id="' . esc_attr( $data['id'] ) . '"';
		$output = '<div class="ibedu-field">';
		$output .= '<div class="ibedu-label"><label' . $for . '>' . $data['label'] . '</label></div>';
		$output .= '<div class="ibedu-control"><textarea class="' . esc_attr( $data['class'] ) . '"' . $id . ' name="' . esc_attr( $name ) . '" cols="' . absint( $data['cols'] ) . '" rows="' . absint( $data['rows'] ) . '">' . esc_textarea( $value ) . '</textarea>';
		$output .= self::description_html( $data );
		$output .= '</div></div>';
		return $output;
	}
}