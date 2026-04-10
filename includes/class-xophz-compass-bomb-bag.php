<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/includes
 * @author     Your Name <email@example.com>
 */
class Xophz_Compass_Bomb_Bag {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Xophz_Compass_Bomb_Bag_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'XOPHZ_COMPASS_BOMB_BAG_VERSION' ) ) {
			$this->version = XOPHZ_COMPASS_BOMB_BAG_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'xophz-compass-bomb-bag';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Xophz_Compass_Bomb_Bag_Loader. Orchestrates the hooks of the plugin.
	 * - Xophz_Compass_Bomb_Bag_i18n. Defines internationalization functionality.
	 * - Xophz_Compass_Bomb_Bag_Admin. Defines all hooks for the admin area.
	 * - Xophz_Compass_Bomb_Bag_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xophz-compass-bomb-bag-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xophz-compass-bomb-bag-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-xophz-compass-bomb-bag-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-xophz-compass-bomb-bag-public.php';

		/**
		 * REST API controller for Bomb Bag endpoints.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bomb-bag-rest.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bomb-bag-email-handler.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bomb-bag-email-providers.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bomb-bag-drip-rest.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-bomb-bag-template-rest.php';

		$this->loader = new Xophz_Compass_Bomb_Bag_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Xophz_Compass_Bomb_Bag_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Xophz_Compass_Bomb_Bag_i18n();

		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain', 5 );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Xophz_Compass_Bomb_Bag_Admin( $this->get_xophz_compass_bomb_bag(), $this->get_version() );

		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'addToMenu' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Xophz_Compass_Bomb_Bag_Public( $this->get_xophz_compass_bomb_bag(), $this->get_version() );

		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$rest_controller = new Xophz_Compass_Bomb_Bag_Rest();
		$this->loader->add_action( 'rest_api_init', $rest_controller, 'register_routes' );

		$drip_rest = new Xophz_Compass_Bomb_Bag_Drip_Rest();
		$this->loader->add_action( 'rest_api_init', $drip_rest, 'register_routes' );

		$template_rest = new Xophz_Compass_Bomb_Bag_Template_Rest();
		$this->loader->add_action( 'rest_api_init', $template_rest, 'register_routes' );

		$email_handler = new Xophz_Compass_Bomb_Bag_Email_Handler();
		add_action( 'bomb_bag_send_emails', array( $email_handler, 'process_campaign_emails' ) );
		add_action( 'bomb_bag_process_drips', array( $email_handler, 'process_drip_emails' ) );
		add_action( 'bomb_bag_check_scheduled', array( $this, 'check_scheduled_campaigns' ) );

		add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );

		$this->loader->add_action( 'init', $this, 'handle_tracking_requests' );

		// Register Spark with Event Horizon Registry
		// $this->loader->add_filter( 'xophz_register_sparks', $plugin_public, 'register_spark' );
		// $this->loader->add_filter( 'xophz_get_spark_manifest', $plugin_public, 'get_spark_manifest', 10, 2 );

		// Schedule crons on init
		add_action( 'init', array( $this, 'schedule_crons' ) );
	}

	/**
	 * Schedule the plugin's cron jobs.
	 *
	 * @since    1.0.0
	 */
	public function schedule_crons() {
		if ( ! wp_next_scheduled( 'bomb_bag_process_drips' ) ) {
			wp_schedule_event( time(), 'hourly', 'bomb_bag_process_drips' );
		}

		if ( ! wp_next_scheduled( 'bomb_bag_check_scheduled' ) ) {
			wp_schedule_event( time(), 'every_five_minutes', 'bomb_bag_check_scheduled' );
		}
	}

	/**
	 * Handle tracking pixel and link click requests.
	 *
	 * @since    1.0.0
	 */
	public function handle_tracking_requests() {
		if ( empty( $_GET['bomb_bag_track'] ) || empty( $_GET['tid'] ) ) {
			return;
		}

		$action = sanitize_text_field( $_GET['bomb_bag_track'] );
		$tracking_id = sanitize_text_field( $_GET['tid'] );
		$email_handler = new Xophz_Compass_Bomb_Bag_Email_Handler();

		switch ( $action ) {
			case 'open':
				$email_handler->track_open( $tracking_id );
				// Return 1x1 transparent GIF
				header( 'Content-Type: image/gif' );
				echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
				exit;

			case 'click':
				$url = urldecode( $_GET['url'] ?? '' );
				if ( $url && filter_var( $url, FILTER_VALIDATE_URL ) ) {
					$email_handler->track_click( $tracking_id, $url );
					wp_redirect( $url );
					exit;
				}
				break;

			case 'unsubscribe':
				$result = $email_handler->handle_unsubscribe( $tracking_id );
				if ( $result ) {
					wp_die( 
						'<h1>Unsubscribed</h1><p>You have been successfully unsubscribed from our mailing list.</p>',
						'Unsubscribed',
						array( 'response' => 200 )
					);
				} else {
					wp_die( 
						'<h1>Error</h1><p>Invalid or expired unsubscribe link.</p>',
						'Error',
						array( 'response' => 400 )
					);
				}
				break;
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_xophz_compass_bomb_bag() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Xophz_Compass_Bomb_Bag_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function check_scheduled_campaigns() {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_campaigns';

		$due_campaigns = $wpdb->get_results( $wpdb->prepare(
			"SELECT id FROM $table WHERE status = 'scheduled' AND scheduled_at <= %s",
			current_time( 'mysql' )
		));

		$handler = new Xophz_Compass_Bomb_Bag_Email_Handler();
		foreach ( $due_campaigns as $campaign ) {
			$handler->queue_campaign( $campaign->id );
		}
	}

	public function add_cron_intervals( $schedules ) {
		$schedules['every_five_minutes'] = array(
			'interval' => 300,
			'display'  => 'Every Five Minutes'
		);
		return $schedules;
	}

}
