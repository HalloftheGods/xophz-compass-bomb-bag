<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/public
 * @author     Your Name <email@example.com>
 */
class Xophz_Compass_Bomb_Bag_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xophz_Compass_Bomb_Bag_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xophz_Compass_Bomb_Bag_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/xophz-compass-bomb-bag-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xophz_Compass_Bomb_Bag_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xophz_Compass_Bomb_Bag_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/xophz-compass-bomb-bag-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Register this spark with the YouMeOS Registry.
	 *
	 * @param array $sparks List of registered sparks.
	 * @return array Modified list.
	 */
	public function register_spark( $sparks ) {
		$sparks[] = array(
			'id' => 'xophz-bomb-bag',
			'name' => 'Bomb Bag', // legacy
			'category' => 'communication',
			'meta' => array(
				'title' => 'Bomb Bag',
				'icon' => 'fad fa-bomb',
				'dimensions' => array( 'width' => 900, 'height' => 650 ),
			),
		);
		return $sparks;
	}

	/**
	 * Retrieve the full manifest for this spark.
	 *
	 * @param mixed $manifest Current manifest (usually null).
	 * @param string $id The requested spark ID.
	 * @return array|null The manifest if ID matches, else original value.
	 */
	public function get_spark_manifest( $manifest, $id ) {
		if ( 'xophz-bomb-bag' !== $id ) {
			return $manifest;
		}

		return array(
			'id' => 'xophz-bomb-bag',
			'category' => 'communication',
			'meta' => array(
				'title' => 'Bomb Bag',
				'icon' => 'fad fa-bomb',
				'dimensions' => array( 'width' => 900, 'height' => 650 ),
			),
			'navigation' => array(
				'items' => array(
					array( 'id' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'fal fa-chart-network' ),
					array( 'id' => 'inventory', 'title' => 'Bomb Inventory', 'icon' => 'fal fa-backpack' ),
					array( 'id' => 'crafting', 'title' => 'Work Bench', 'icon' => 'fal fa-hammer' ),
					array( 'id' => 'help', 'title' => 'Manual', 'icon' => 'fal fa-book-spells' ),
				),
				'defaultActive' => 'dashboard',
			),
			'views' => array(
				'dashboard' => array(
					'type' => 'layout',
					'root' => array(
						'type' => 'v-container',
						'props' => array( 'fluid' => true ),
						'children' => array(
							array(
								'type' => 'v-row',
								'children' => array(
									array(
										'type' => 'v-col',
										'props' => array( 'cols' => 12 ),
										'children' => array(
											array(
												'type' => 'x-card',
												'props' => array(
													'title' => 'Welcome to Bomb Bag',
													'subtitle' => 'Safe handling procedures required.',
													'variant' => 'glass',
												),
												'children' => array(
													array(
														'type' => 'v-card-text',
														'children' => array(
															array( 'type' => 'text', 'content' => 'This interface is rendered entirely from a JSON layout tree! It maps to native components.' ),
														),
													),
													array(
														'type' => 'v-card-actions',
														'children' => array(
															array(
																'type' => 'v-btn',
																'props' => array( 'color' => 'primary', 'variant' => 'tonal' ),
																'content' => 'Read Manual',
															),
														),
													),
												),
											),
										),
									),
								),
							),
							array(
								'type' => 'v-row',
								'children' => array(
									array(
										'type' => 'v-col',
										'props' => array( 'cols' => 12, 'md' => 4 ),
										'children' => array(
											array(
												'type' => 'x-card',
												'props' => array( 'title' => 'Standard Bomb', 'subtitle' => 'Reliable & Loud', 'prepend-icon' => 'fas fa-bomb' ),
												'children' => array(
													array(
														'type' => 'v-card-text',
														'content' => 'The classic explosive. Good for opening cracked walls.',
													),
												),
											),
										),
									),
									array(
										'type' => 'v-col',
										'props' => array( 'cols' => 12, 'md' => 4 ),
										'children' => array(
											array(
												'type' => 'x-card',
												'props' => array( 'title' => 'Remote Bomb', 'subtitle' => 'Tactical', 'prepend-icon' => 'fas fa-wifi' ),
												'children' => array(
													array(
														'type' => 'v-card-text',
														'content' => 'Detonate at your leisure. Excellent for ambushes.',
													),
												),
											),
										),
									),
									array(
										'type' => 'v-col',
										'props' => array( 'cols' => 12, 'md' => 4 ),
										'children' => array(
											array(
												'type' => 'x-card',
												'props' => array( 'title' => 'Water Bomb', 'subtitle' => 'Aquatic', 'prepend-icon' => 'fas fa-water' ),
												'children' => array(
													array(
														'type' => 'v-card-text',
														'content' => 'Specialized casing allows detonation underwater.',
													),
												),
											),
										),
									),
								),
							),
						),
					),
				),
				'inventory' => array(
					'type' => 'html',
					'content' => '<div class="text-center pa-10"><i class="fal fa-backpack fa-3x mb-4"></i><h3>Inventory Grid Placeholder</h3><p>Future inventory management view.</p></div>',
				),
				'crafting' => array(
					'type' => 'html',
					'content' => '<div class="text-center pa-10"><i class="fal fa-hammer fa-3x mb-4"></i><h3>Crafting Bench</h3><p>Combine items to create stronger explosives.</p></div>',
				),
				'help' => array(
					'type' => 'html',
					'content' => '<div class="pa-4"><h3>Bomb Safety Manual</h3><ul><li>Do not eat bombs.</li><li>Do not juggle bombs.</li><li>Do not sleep on bombs.</li></ul></div>',
				),
			),
		);
	}

}
