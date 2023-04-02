<?php

if ( ! defined( 'ABSPATH' ) ) die();

if ( ! class_exists( 'LMG_ASA_Events_National_Settings' ) ) :
class LMG_ASA_Events_National_Settings {

	function __construct() {

		add_action( 'admin_menu',            array( $this, 'admin_menu'             )        );
		add_action( 'admin_init',            array( $this, 'settings_init'          )        );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_register_scripts' ), 10, 1 );

		add_filter( 'plugin_action_links_lmg-asa-events-national/lmg-asa-events-national.php', array( $this, 'settings_link' ) );

	}

	function admin_register_scripts( $hook ) {

		if ( 'settings_page_lmgasaevtnat_settings' !== $hook ) return;

		wp_enqueue_script( 'lmgasaevtnat', LMGASAEVTNAT_DIR_URL . 'admin/js/script.js',   array( 'jquery' ), LMGASAEVTNAT_VERSION, true );
		wp_enqueue_style(  'lmgasaevtnat', LMGASAEVTNAT_DIR_URL . 'admin/css/style.css', array(          ), LMGASAEVTNAT_VERSION       );

	}

	function settings_link( $links ) {

		$url = add_query_arg( 'page', 'lmgasaevtnat_settings', get_admin_url( null, 'options-general.php' ) );

		$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'lmgasaevtnat' ) . '</a>';

		return $links;

	}

	function admin_menu() {

		add_options_page(
			esc_html__( 'LMG ASA Events National Settings', 'lmgasaevtnat' ),
			esc_html__( 'LMG ASA Events National', 'lmgasaevtnat' ),
			'manage_options',
			'lmgasaevtnat_settings',
			array( $this, 'settings_page' )
		);

	}

	function settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) return;

		?>
		<div class="wrap lmgasaevtnat">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				
			<form action="options.php" method="post">
				<?php
					settings_fields( 'lmgasaevtnat_settings' );
					do_settings_sections( 'lmgasaevtnat_settings' );
					submit_button( esc_html__( 'Save Settings', 'lmgasaevtnat' ) );
				?>
			</form>
		</div>
		<?php

	}

	function settings_init() {

		register_setting( 'lmgasaevtnat_settings', 'lmgasaevtnat_settings' );

		add_settings_section(
			'global_settings',
			esc_html__( 'Global Settings', 'lmgasaevtnat' ),
			function(){},
			'lmgasaevtnat_settings'
		);

		add_settings_field(
			'dummy_option',
			esc_html__( 'Dummy Option', 'lmgasaevtnat' ),
			array( $this, 'input_select' ),
			'lmgasaevtnat_settings',
			'global_settings',
			array(
				'name'    => 'dummy_option',
				'desc'    => esc_html__( 'Dummy description.', 'lmgasaevtnat' ),
				'options' => array(
					'no'  => esc_html__( 'No', 'lmgasaevtnat' ),
					'yes' => esc_html__( 'Yes', 'lmgasaevtnat' ),
				),
			)
		);

	}

	function get_val( $args ) {

		$val = get_option( 'lmgasaevtnat_settings' );
		if ( empty( $val[ $args['name'] ] ) ) {
			$val = '';
		} else {
			$val = $val[ $args['name'] ];
		}

		return $val;

	}

	function get_atts( $args ) {

		$atts  = '';
		$atts .= ' id="' . esc_attr( $args['name'] ) . '"';
		$atts .= ' name="' . esc_attr( 'lmgasaevtnat_settings[' . $args['name'] . ']' ) .'"';
		$atts .= empty( $args['class'] )    ? '' : ' class="' . esc_attr( $args['class'] ) . '"';
		$atts .= empty( $args['required'] ) ? '' : ' required="required"';
		$atts .= empty( $args['min'] )      ? '' : ' min="' . esc_attr( $args['min'] ) . '"';
		$atts .= empty( $args['max'] )      ? '' : ' max="' . esc_attr( $args['max'] ) . '"';
		$atts .= empty( $args['step'] )     ? '' : ' step="' . esc_attr( $args['step'] ) . '"';

		return $atts;

	}

	function input_text( $args ) {

		$val = $this->get_val( $args );
		if ( empty( $val ) && ! empty( $args['default'] ) ) $val = $args['default'];

		$atts = $this->get_atts( $args );
		$atts .= empty( $val ) ? '' : ' value="' . esc_attr( $val ) . '"';

		echo '<input type="text"' . $atts . '>';
		if ( ! empty( $args['desc'] ) ) echo '<p class="description">' . $args['desc'] . '</p>';

	}

	function input_number( $args ) {

		$val = $this->get_val( $args );
		if ( empty( $val ) && ! empty( $args['default'] ) ) $val = $args['default'];

		$atts = $this->get_atts( $args );
		$atts .= empty( $val ) ? '' : ' value="' . esc_attr( $val ) . '"';

		echo '<input type="number"' . $atts . '>';
		if ( ! empty( $args['desc'] ) ) echo '<p class="description">' . $args['desc'] . '</p>';

	}

	function input_select( $args ) {

		$val = $this->get_val( $args );
		if ( empty( $val ) && isset( $args['default'] ) && in_array( $args['default'], array_keys( $args['options'] ) ) ) $val = $args['default'];

		$atts = $this->get_atts( $args );

		echo '<select' . $atts . '>';
		foreach ( $args['options'] as $k => $v ) {
			echo '<option value="' . $k . '" ' . selected( $k, $val, false ) . '>' . $v . '</option>';
		}
		echo '</select>';
		if ( ! empty( $args['desc'] ) ) echo '<p class="description">' . $args['desc'] . '</p>';

	}

}
endif;

?>
