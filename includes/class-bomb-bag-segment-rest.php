<?php

/**
 * Bomb Bag Segment REST Controller
 */
class Xophz_Compass_Bomb_Bag_Segment_Rest extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'bomb-bag/v1';
		$this->rest_base = 'segments';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
			),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			),
		) );
	}

	public function get_items_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function create_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function get_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function update_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function get_items( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_segments';
		
		$segments = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
		
		foreach ($segments as &$segment) {
			$segment->rules_json = json_decode($segment->rules_json, true);
		}

		return rest_ensure_response( $segments );
	}

	public function get_item( $request ) {
		global $wpdb;
		$id    = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_segments';

		$segment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		if ( ! $segment ) {
			return new WP_Error( 'not_found', 'Segment not found', array( 'status' => 404 ) );
		}

		$segment->rules_json = json_decode($segment->rules_json, true);

		return rest_ensure_response( $segment );
	}

	public function create_item( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_segments';

		$data = array(
			'name'        => sanitize_text_field( $request->get_param( 'name' ) ),
			'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'rules_json'  => wp_json_encode( $request->get_param( 'rules_json' ) ),
		);

		$wpdb->insert( $table, $data );
		$id = $wpdb->insert_id;

		$request->set_param( 'id', $id );
		return $this->get_item( $request );
	}

	public function update_item( $request ) {
		global $wpdb;
		$id    = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_segments';

		$data = array();
		if ( $request->has_param( 'name' ) ) {
			$data['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		}
		if ( $request->has_param( 'description' ) ) {
			$data['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
		}
		if ( $request->has_param( 'rules_json' ) ) {
			$data['rules_json'] = wp_json_encode( $request->get_param( 'rules_json' ) );
		}

		if ( ! empty( $data ) ) {
			$wpdb->update( $table, $data, array( 'id' => $id ) );
		}

		return $this->get_item( $request );
	}

	public function delete_item( $request ) {
		global $wpdb;
		$id    = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_segments';

		$wpdb->delete( $table, array( 'id' => $id ) );

		return rest_ensure_response( array( 'deleted' => true ) );
	}
}
