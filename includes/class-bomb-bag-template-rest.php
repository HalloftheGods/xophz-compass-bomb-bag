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
		return rest_ensure_response( $templates );
	}

	public function get_template( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';
		$id    = $request->get_param( 'id' );

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

		$data = array(
			'name'        => sanitize_text_field( $request->get_param( 'name' ) ),
			'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'category'    => sanitize_text_field( $request->get_param( 'category' ) ) ?: 'custom',
			'content'     => wp_kses_post( $request->get_param( 'content' ) ),
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
			$data['content'] = wp_kses_post( $request->get_param( 'content' ) );
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
}
