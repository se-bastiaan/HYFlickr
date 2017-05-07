<?php

class HYFlickrBackend {

	private $option_group = "hyflickr-options";
	private $option_name = HYFlickrOptions::NAME;
	private $option_section = "hyflickr-api-settings";
	private $option_page = "hyflickr-admin";

	private $phpFlickr;

	public function __construct() {
		$this->initFlickr();
		add_action( 'admin_menu', array( $this, 'adminMenuInit' ) );
		add_action( 'admin_init', array( $this, 'adminInit' ) );
		add_action( 'wp_ajax_hyflickr_auth', array( $this, 'flickrAuthInit' ) );
	}

	function initFlickr() {
		$options         = get_option( HYFlickrOptions::NAME );
		$this->phpFlickr = new phpFlickr( $options[ HYFlickrOptions::API_KEY ], $options[ HYFlickrOptions::API_SECRET ] ? $options[ HYFlickrOptions::API_SECRET ] : null );
	}

	function flickrAuthInit() {
		if ( HYFlickrOptions::get( HYFlickrOptions::API_TOKEN ) ) {
			HYFlickrOptions::update(HYFlickrOptions::API_TOKEN, "");
			header( 'Location: ' . $_SERVER['HTTP_REFERER'] );
		} else {
			session_start();
			unset( $_SESSION['phpFlickr_auth_token'] );
			$this->phpFlickr->setToken( '' );
			$this->phpFlickr->auth( 'read', $_SERVER['HTTP_REFERER'] );
			exit;
		}
	}

	function flickrAuthRead() {
		if ( isset( $_GET['frob'] ) ) {
			$auth = $this->phpFlickr->auth_getToken( $_GET['frob'] );
			HYFlickrOptions::update( HYFlickrOptions::API_TOKEN, $auth['token']['_content'] );
			$this->phpFlickr->setToken( $auth['token']['_content'] );
			header( 'Location: ' . $_SESSION['phpFlickr_auth_redirect'] );
			exit;
		}
	}

	/**
	 * Admin menu methods
	 */

	function adminMenuInit() {
		add_options_page( 'Flickr Options', 'Flickr Options', 'manage_options', 'hyflickr', array(
			$this,
			'generateAdminOptionsPage'
		) );
	}

	function adminInit() {
		register_setting(
			$this->option_group,
			$this->option_name
		);

		add_settings_section(
			$this->option_section,
			'API Settings',
			array( $this, 'printSectionInfo' ),
			$this->option_page
		);

		add_settings_field(
			HYFlickrOptions::API_KEY,
			'API Key',
			array( $this, 'optionCallback' ),
			$this->option_page,
			$this->option_section,
			HYFlickrOptions::API_KEY
		);

		add_settings_field(
			HYFlickrOptions::API_SECRET,
			'API Secret',
			array( $this, 'optionCallback' ),
			$this->option_page,
			$this->option_section,
			HYFlickrOptions::API_SECRET
		);

		add_settings_field(
			HYFlickrOptions::API_USER,
			'API User',
			array( $this, 'optionCallback' ),
			$this->option_page,
			$this->option_section,
			HYFlickrOptions::API_USER
		);

		$this->flickrAuthRead();
	}

	function generateAdminOptionsPage() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->initFlickr();

		if ( $_POST ) {
			echo "post";
		}

		$this->options = get_option( $this->option_name );
		?>
		<div class="wrap">
			<h1>Heyendaal Flickr Settings</h1>
			<?php if ( $this->options[ HYFlickrOptions::API_TOKEN ] == "" ) { ?>
				<form method="post" action="options.php">
					<?php
					// This prints out all hidden setting fields
					settings_fields( $this->option_group );
					do_settings_sections( $this->option_page );
					submit_button();
					?>
				</form>
				<form method="post" action="<?php echo get_admin_url() . 'admin-ajax.php?action=hyflickr_auth'; ?>">
					<input type="submit" name="authenticate" class="button-secondary" value="Authenticate"/>
				</form>
			<?php } else { ?>
				<p>
					Current token: <?php echo HYFlickrOptions::get( HYFlickrOptions::API_TOKEN ); ?>
				</p>
				<form method="post" action="<?php echo get_admin_url() . 'admin-ajax.php?action=hyflickr_auth'; ?>">
					<input type="submit" name="authenticate" class="button-secondary" value="Remove authentication"/>
				</form>
			<?php } ?>
		</div>
		<?php
	}

	public function printSectionInfo() {
		// Do nothing
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function optionCallback( $option_name ) {
		printf(
			'<input type="text" id="' . $option_name . '" name="' . $this->option_name . '[' . $option_name . ']" value="%s" />',
			isset( $this->options[ $option_name ] ) ? esc_attr( $this->options[ $option_name ] ) : ''
		);
	}

}