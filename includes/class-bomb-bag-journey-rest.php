<?php

class Xophz_Compass_Bomb_Bag_Journey_Rest {

	private $namespace = 'xophz-compass/v1/bomb-bag';

	public function register_routes() {
		register_rest_route( $this->namespace, '/journeys', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_journeys' ),
				'permission_callback' => array( $this, 'check_permission' )
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_journey' ),
				'permission_callback' => array( $this, 'check_permission' )
			)
		));

		register_rest_route( $this->namespace, '/journeys/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_journey' ),
				'permission_callback' => array( $this, 'check_permission' )
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_journey' ),
				'permission_callback' => array( $this, 'check_permission' )
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_journey' ),
				'permission_callback' => array( $this, 'check_permission' )
			)
		));
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	public function get_journeys( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_journeys';
		$items = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
		
		foreach ( $items as &$item ) {
			$item->nodes_json = json_decode( $item->nodes_json, true ) ?: array();
			$item->edges_json = json_decode( $item->edges_json, true ) ?: array();
		}
		
		return rest_ensure_response( $items );
	}

	public function get_journey( $request ) {
		global $wpdb;
		$id = $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_journeys';
		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		if ( ! $item ) {
			return new WP_Error( 'not_found', 'Journey not found', array( 'status' => 404 ) );
		}
		
		$item->nodes_json = json_decode( $item->nodes_json, true ) ?: array();
		$item->edges_json = json_decode( $item->edges_json, true ) ?: array();

		return rest_ensure_response( $item );
	}

	public function create_journey( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_journeys';

		$data = array(
			'name'         => sanitize_text_field( $request->get_param( 'name' ) ),
			'description'  => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'status'       => sanitize_text_field( $request->get_param( 'status' ) ?: 'draft' ),
			'trigger_type' => sanitize_text_field( $request->get_param( 'trigger_type' ) ?: 'manual' ),
			'nodes_json'   => wp_json_encode( $request->get_param( 'nodes_json' ) ?: array() ),
			'edges_json'   => wp_json_encode( $request->get_param( 'edges_json' ) ?: array() ),
		);

		$wpdb->insert( $table, $data );
		$data['id'] = $wpdb->insert_id;
		$data['nodes_json'] = json_decode($data['nodes_json'], true);
		$data['edges_json'] = json_decode($data['edges_json'], true);

		return rest_ensure_response( $data );
	}

	public function update_journey( $request ) {
		global $wpdb;
		$id = $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_journeys';

		$data = array();
		if ( $request->has_param( 'name' ) ) {
			$data['name'] = sanitize_text_field( $request->get_param( 'name' ) );
		}
		if ( $request->has_param( 'description' ) ) {
			$data['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
		}
		if ( $request->has_param( 'status' ) ) {
			$data['status'] = sanitize_text_field( $request->get_param( 'status' ) );
		}
		if ( $request->has_param( 'trigger_type' ) ) {
			$data['trigger_type'] = sanitize_text_field( $request->get_param( 'trigger_type' ) );
		}
		if ( $request->has_param( 'nodes_json' ) ) {
			$data['nodes_json'] = wp_json_encode( $request->get_param( 'nodes_json' ) );
		}
		if ( $request->has_param( 'edges_json' ) ) {
			$data['edges_json'] = wp_json_encode( $request->get_param( 'edges_json' ) );
		}

		if ( ! empty( $data ) ) {
			$wpdb->update( $table, $data, array( 'id' => $id ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_journey( $request ) {
		global $wpdb;
		$id = $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_journeys';
		$enrollments_table = $wpdb->prefix . 'bomb_bag_journey_enrollments';

		$wpdb->delete( $table, array( 'id' => $id ) );
		$wpdb->delete( $enrollments_table, array( 'journey_id' => $id ) );

		return rest_ensure_response( array( 'success' => true ) );
	}
}
