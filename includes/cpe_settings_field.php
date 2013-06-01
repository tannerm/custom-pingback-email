<?php
/**
 * Class for adding a new field to the options-general.php page
 */
class Add_Settings_Field {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_filter( 'admin_init' , array( &$this , 'register_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	function enqueue() {
		wp_enqueue_script( 'cpe', CPE_URL . '/assets/js/cpe.js', array( 'jquery' ) );
	}
	/**
	 * Add new fields to wp-admin/options-general.php page
	 */
	public function register_fields() {
		register_setting( 'general', 'cpe_pingback_email', 'sanitize_email' );
		add_settings_field(
			'fav_color',
			'<label for="cpe_pingback_email">' . __( 'Pingback E-mail' , 'cpe_pingback_email' ) . '</label>',
			array( &$this, 'fields_html' ),
			'general'
		);
	}

	/**
	 * HTML for extra settings
	 */
	public function fields_html() {
		$value = get_option( 'cpe_pingback_email', '' );
		echo '
			<input type="text" id="cpe_pingback_email" name="cpe_pingback_email" class="regular-text ltr" value="' . esc_attr( $value ) . '" />
			<p class="description">This address is used for pingback notifications. If left blank, pingback notifications will go to the admin e-mail.</p>
		';
	}

}
new Add_Settings_Field();