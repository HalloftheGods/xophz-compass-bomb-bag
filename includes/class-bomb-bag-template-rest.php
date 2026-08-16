<?php

class Xophz_Compass_Bomb_Bag_Template_Rest {

	private $namespace = 'xophz-compass/v1';

	public function register_routes() {
		register_rest_route( $this->namespace, '/bomb-bag/templates', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_templates' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_template' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/templates/seed-defaults', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'seed_defaults' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/templates/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_template' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_template' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_template' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		));
	}

	public function check_admin() {
		return current_user_can( 'manage_options' );
	}

	public function get_templates() {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';
		$templates = $wpdb->get_results( "SELECT * FROM $table ORDER BY is_default DESC, name ASC" );
		
		// If table is completely empty, auto-seed defaults
		if ( empty( $templates ) && class_exists( 'Xophz_Compass_Bomb_Bag_Activator' ) ) {
			$this->seed_defaults();
			$templates = $wpdb->get_results( "SELECT * FROM $table ORDER BY is_default DESC, name ASC" );
		}

		// Check for Branda template
		$branda_template = get_option('ub_email_template');
		if ( ! empty( $branda_template ) && is_array( $branda_template ) && ! empty( $branda_template['email']['content'] ) ) {
			$templates[] = (object) array(
				'id' => 0,
				'name' => 'Branda Active Template',
				'description' => 'The currently active template from the Branda plugin.',
				'category' => 'system',
				'content' => $branda_template['email']['content'],
				'is_default' => 0
			);
		}

		return rest_ensure_response( $templates );
	}

	public function get_template( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';
		$id    = $request->get_param( 'id' );

		if ( $id == 0 ) {
			$branda_template = get_option('ub_email_template');
			if ( ! empty( $branda_template ) && is_array( $branda_template ) && ! empty( $branda_template['email']['content'] ) ) {
				$template = (object) array(
					'id' => 0,
					'name' => 'Branda Active Template',
					'description' => 'The currently active template from the Branda plugin.',
					'category' => 'system',
					'content' => $branda_template['email']['content'],
					'is_default' => 0
				);
				return rest_ensure_response( $template );
			}
		}

		$template = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d", $id
		));

		if ( ! $template ) {
			return new WP_Error( 'not_found', 'Template not found', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $template );
	}

	public function create_template( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';

		$raw_content = $request->get_param( 'content' );
		$content = current_user_can( 'unfiltered_html' ) ? $raw_content : wp_kses_post( $raw_content );

		$data = array(
			'name'        => sanitize_text_field( $request->get_param( 'name' ) ),
			'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'category'    => sanitize_text_field( $request->get_param( 'category' ) ) ?: 'custom',
			'content'     => $content,
			'is_default'  => 0,
		);

		$result = $wpdb->insert( $table, $data );

		if ( $result === false ) {
			return new WP_Error( 'create_failed', 'Failed to create template', array( 'status' => 500 ) );
		}

		$data['id'] = $wpdb->insert_id;
		return rest_ensure_response( $data );
	}

	public function update_template( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';
		$id    = $request->get_param( 'id' );

		$data = array();

		if ( $request->get_param( 'name' ) !== null ) {
			$data['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		}
		if ( $request->get_param( 'description' ) !== null ) {
			$data['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
		}
		if ( $request->get_param( 'category' ) !== null ) {
			$data['category'] = sanitize_text_field( $request->get_param( 'category' ) );
		}
		if ( $request->get_param( 'content' ) !== null ) {
			$raw_content = $request->get_param( 'content' );
			$data['content'] = current_user_can( 'unfiltered_html' ) ? $raw_content : wp_kses_post( $raw_content );
		}

		$wpdb->update( $table, $data, array( 'id' => $id ) );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_template( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';
		$id    = $request->get_param( 'id' );

		$is_default = $wpdb->get_var( $wpdb->prepare(
			"SELECT is_default FROM $table WHERE id = %d", $id
		));

		if ( $is_default ) {
			return new WP_Error( 'protected', 'Cannot delete default templates', array( 'status' => 403 ) );
		}

		$wpdb->delete( $table, array( 'id' => $id ) );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function seed_defaults() {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';

		if ( ! class_exists( 'Xophz_Compass_Bomb_Bag_Activator' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-xophz-compass-bomb-bag-activator.php';
		}

		$reflection = new ReflectionClass( 'Xophz_Compass_Bomb_Bag_Activator' );
		$method = $reflection->getMethod( 'get_default_template_definitions' );
		$method->setAccessible( true );
		$templates = $method->invoke( null );

		$inserted = 0;
		foreach ( $templates as $t ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE name = %s", $t['name'] ) );
			if ( ! $exists ) {
				$wpdb->insert( $table, array(
					'name'        => $t['name'],
					'description' => $t['description'],
					'category'    => $t['category'],
					'content'     => $t['content'],
					'is_default'  => 1,
				));
				$inserted++;
			}
		}

		return rest_ensure_response( array( 'success' => true, 'seeded' => $inserted ) );
	}
}
