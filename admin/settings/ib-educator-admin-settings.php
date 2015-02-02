<?php

class IB_Educator_Admin_Settings {
	/**
	 * Output admin settings tabs.
	 *
	 * @param string $current_tab
	 */
	public function settings_tabs( $current_tab ) {
		$tabs = apply_filters( 'ib_educator_settings_tabs', array() );
		?>
		<h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_key => $tab_name ) : ?>
		<a class="nav-tab<?php if ( $tab_key == $current_tab ) echo ' nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_admin&tab=' . $tab_key ) ); ?>"><?php echo esc_html( $tab_name ); ?></a>
		<?php endforeach; ?>
		</h2>
		<?php
	}

	/**
	 * Dummy section description callback.
	 */
	public function section_description( $args ) {}
	
	/**
	 * Text field.
	 *
	 * @param array $args
	 */
	public function setting_text( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && isset( $args['default'] ) ) $value = $args['default'];
		$size = isset( $args['size'] ) ? ' size="' . intval( $args['size'] ) . '"' : '';

		echo '<input type="text" name="' . esc_attr( $name ) . '" class="regular-text"' . $size . ' value="' . esc_attr( $value ) . '">';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Textarea field.
	 *
	 * @param array $args
	 */
	public function setting_textarea( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && isset( $args['default'] ) ) $value = $args['default'];

		echo '<textarea name="' . esc_attr( $name ) . '" class="large-text" rows="5" cols="40">' . esc_textarea( $value ) . '</textarea>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Select field.
	 *
	 * @param array $args
	 */
	public function setting_select( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		echo '<select name="' . esc_attr( $name ) . '">';
		echo '<option value="">&mdash; ' . __( 'Select', 'ibeducator' ) . ' &mdash;</option>';

		foreach ( $args['choices'] as $choice => $label ) {
			echo '<option value="' . esc_attr( $choice ) . '"' . ( $choice == $value ? ' selected="selected"' : '' ) . '>' . esc_html( $label ) . '</option>';
		}

		echo '</select>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Checkbox field.
	 *
	 * @param array $args
	 */
	public function setting_checkbox( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && 0 !== $value && isset( $args['default'] ) ) $value = $args['default'];

		$id_attr = ! empty( $args['id'] ) ? $id_attr = ' id="' . esc_attr( $args['id'] ) . '"' : '';

		echo '<input type="checkbox"' . $id_attr . ' name="' . esc_attr( $name ) . '" value="1" ' . checked( 1, $value, false ) . '>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}
}