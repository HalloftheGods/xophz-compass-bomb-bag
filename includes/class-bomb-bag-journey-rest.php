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

		register_rest_route( $this->namespace, '/journeys/(?P<id>\d+)/duplicate', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'duplicate_journey' ),
				'permission_callback' => array( $this, 'check_permission' )
			)
		));

		register_rest_route( $this->namespace, '/journeys/(?P<id>\d+)/enroll', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'enroll_subscriber' ),
				'permission_callback' => array( $this, 'check_permission' )
			)
		));

		register_rest_route( $this->namespace, '/journeys/(?P<id>\d+)/enrollments', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_enrollments' ),
				'permission_callback' => array( $this, 'check_permission' )
			)
		));

		register_rest_route( $this->namespace, '/journeys/(?P<id>\d+)/test-run', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_run' ),
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
		$enrollments_table = $wpdb->prefix . 'bomb_bag_journey_enrollments';

		$items = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		
		foreach ( $items as &$item ) {
			$item->id = (int) $item->id;
			$item->nodes_json = json_decode( $item->nodes_json, true ) ?: array();
			$item->edges_json = json_decode( $item->edges_json, true ) ?: array();

			// Calculate real-time dynamic stats
			$stats = $wpdb->get_row( $wpdb->prepare(
				"SELECT 
					COUNT(*) as total_enrolled,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
					SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as total_active
				 FROM $enrollments_table WHERE journey_id = %d",
				$item->id
			) );

			$item->total_enrolled = $stats ? (int) $stats->total_enrolled : (int) $item->total_enrolled;
			$item->total_completed = $stats ? (int) $stats->total_completed : (int) $item->total_completed;
			$item->active_enrolled = $stats ? (int) $stats->total_active : 0;
		}
		
		return rest_ensure_response( $items );
	}

	public function get_journey( $request ) {
		global $wpdb;
		$id = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_journeys';
		$enrollments_table = $wpdb->prefix . 'bomb_bag_journey_enrollments';

		$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		if ( ! $item ) {
			return new WP_Error( 'not_found', 'Journey not found', array( 'status' => 404 ) );
		}
		
		$item->id = (int) $item->id;
		$item->nodes_json = json_decode( $item->nodes_json, true ) ?: array();
		$item->edges_json = json_decode( $item->edges_json, true ) ?: array();

		$stats = $wpdb->get_row( $wpdb->prepare(
			"SELECT 
				COUNT(*) as total_enrolled,
				SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as total_completed,
				SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as total_active
			 FROM $enrollments_table WHERE journey_id = %d",
			$item->id
		) );

		$item->total_enrolled = $stats ? (int) $stats->total_enrolled : (int) $item->total_enrolled;
		$item->total_completed = $stats ? (int) $stats->total_completed : (int) $item->total_completed;
		$item->active_enrolled = $stats ? (int) $stats->total_active : 0;

		return rest_ensure_response( $item );
	}

	public function create_journey( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_journeys';

		$nodes_raw = $request->get_param( 'nodes_json' );
		$edges_raw = $request->get_param( 'edges_json' );

		$nodes_str = is_string( $nodes_raw ) ? $nodes_raw : wp_json_encode( $nodes_raw ?: array() );
		$edges_str = is_string( $edges_raw ) ? $edges_raw : wp_json_encode( $edges_raw ?: array() );

		$name = sanitize_text_field( $request->get_param( 'name' ) ?: 'Untitled Journey' );

		$data = array(
			'name'            => $name,
			'description'     => sanitize_textarea_field( $request->get_param( 'description' ) ?: '' ),
			'status'          => sanitize_text_field( $request->get_param( 'status' ) ?: 'draft' ),
			'trigger_type'    => sanitize_text_field( $request->get_param( 'trigger_type' ) ?: 'subscribe' ),
			'nodes_json'      => $nodes_str,
			'edges_json'      => $edges_str,
			'total_enrolled'  => 0,
			'total_completed' => 0,
		);

		$wpdb->insert( $table, $data );
		$data['id'] = (int) $wpdb->insert_id;
		$data['nodes_json'] = json_decode( $data['nodes_json'], true ) ?: array();
		$data['edges_json'] = json_decode( $data['edges_json'], true ) ?: array();

		return rest_ensure_response( $data );
	}

	public function update_journey( $request ) {
		global $wpdb;
		$id = (int) $request['id'];
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
			$nodes_raw = $request->get_param( 'nodes_json' );
			$data['nodes_json'] = is_string( $nodes_raw ) ? $nodes_raw : wp_json_encode( $nodes_raw );
		}
		if ( $request->has_param( 'edges_json' ) ) {
			$edges_raw = $request->get_param( 'edges_json' );
			$data['edges_json'] = is_string( $edges_raw ) ? $edges_raw : wp_json_encode( $edges_raw );
		}

		if ( ! empty( $data ) ) {
			$wpdb->update( $table, $data, array( 'id' => $id ) );
		}

		$request->set_param( 'id', $id );
		return $this->get_journey( $request );
	}

	public function delete_journey( $request ) {
		global $wpdb;
		$id = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_journeys';
		$enrollments_table = $wpdb->prefix . 'bomb_bag_journey_enrollments';

		$wpdb->delete( $table, array( 'id' => $id ) );
		$wpdb->delete( $enrollments_table, array( 'journey_id' => $id ) );

		return rest_ensure_response( array( 'success' => true, 'deleted' => $id ) );
	}

	public function duplicate_journey( $request ) {
		global $wpdb;
		$id = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_journeys';

		$original = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );
		if ( ! $original ) {
			return new WP_Error( 'not_found', 'Journey not found', array( 'status' => 404 ) );
		}

		$data = array(
			'name'            => 'Copy of ' . $original->name,
			'description'     => $original->description,
			'status'          => 'draft',
			'trigger_type'    => $original->trigger_type,
			'nodes_json'      => $original->nodes_json,
			'edges_json'      => $original->edges_json,
			'total_enrolled'  => 0,
			'total_completed' => 0,
		);

		$wpdb->insert( $table, $data );
		$new_id = (int) $wpdb->insert_id;

		$req = new WP_REST_Request( 'GET', $this->namespace . '/journeys/' . $new_id );
		$req->set_param( 'id', $new_id );
		return $this->get_journey( $req );
	}

	public function enroll_subscriber( $request ) {
		global $wpdb;
		$journey_id = (int) $request['id'];
		$subscriber_id = (int) $request->get_param( 'subscriber_id' );
		$email = sanitize_email( $request->get_param( 'email' ) );

		$sub_table = $wpdb->prefix . 'bomb_bag_subscribers';
		$journey_table = $wpdb->prefix . 'bomb_bag_journeys';
		$enroll_table = $wpdb->prefix . 'bomb_bag_journey_enrollments';

		if ( ! $subscriber_id && ! empty( $email ) ) {
			$subscriber_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $sub_table WHERE email = %s", $email ) );
		}

		if ( ! $subscriber_id ) {
			return new WP_Error( 'invalid_subscriber', 'Valid subscriber ID or email is required', array( 'status' => 400 ) );
		}

		$journey = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $journey_table WHERE id = %d", $journey_id ) );
		if ( ! $journey ) {
			return new WP_Error( 'not_found', 'Journey not found', array( 'status' => 404 ) );
		}

		$nodes = json_decode( $journey->nodes_json, true ) ?: array();
		$edges = json_decode( $journey->edges_json, true ) ?: array();

		$first_node_id = null;
		foreach ( $nodes as $node ) {
			if ( isset( $node['type'] ) && strpos( $node['type'], 'trigger' ) !== false ) {
				// Find outgoing edge
				foreach ( $edges as $edge ) {
					if ( isset( $edge['source'] ) && $edge['source'] === $node['id'] ) {
						$first_node_id = $edge['target'];
						break;
					}
				}
				if ( ! $first_node_id ) {
					$first_node_id = $node['id'];
				}
				break;
			}
		}

		if ( ! $first_node_id && ! empty( $nodes ) ) {
			$first_node_id = $nodes[0]['id'];
		}

		// Insert or reset enrollment
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM $enroll_table WHERE journey_id = %d AND subscriber_id = %d",
			$journey_id, $subscriber_id
		) );

		if ( $existing ) {
			$wpdb->update( $enroll_table, array(
				'current_node_id'    => $first_node_id,
				'status'             => 'active',
				'enrolled_at'        => current_time( 'mysql' ),
				'completed_at'       => null,
				'next_evaluation_at' => current_time( 'mysql' ),
			), array( 'id' => $existing->id ) );
			$enrollment_id = (int) $existing->id;
		} else {
			$wpdb->insert( $enroll_table, array(
				'journey_id'         => $journey_id,
				'subscriber_id'      => $subscriber_id,
				'current_node_id'    => $first_node_id,
				'status'             => 'active',
				'enrolled_at'        => current_time( 'mysql' ),
				'next_evaluation_at' => current_time( 'mysql' ),
			) );
			$enrollment_id = (int) $wpdb->insert_id;
			$wpdb->query( $wpdb->prepare( "UPDATE $journey_table SET total_enrolled = total_enrolled + 1 WHERE id = %d", $journey_id ) );
		}

		return rest_ensure_response( array(
			'success'       => true,
			'enrollment_id' => $enrollment_id,
			'subscriber_id' => $subscriber_id,
			'journey_id'    => $journey_id,
			'current_node'  => $first_node_id,
		) );
	}

	public function get_enrollments( $request ) {
		global $wpdb;
		$journey_id = (int) $request['id'];
		$enroll_table = $wpdb->prefix . 'bomb_bag_journey_enrollments';
		$sub_table = $wpdb->prefix . 'bomb_bag_subscribers';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT e.*, s.email, s.first_name, s.last_name, s.status as subscriber_status
			 FROM $enroll_table e
			 INNER JOIN $sub_table s ON e.subscriber_id = s.id
			 WHERE e.journey_id = %d
			 ORDER BY e.enrolled_at DESC
			 LIMIT 100",
			$journey_id
		) );

		return rest_ensure_response( $results ?: array() );
	}

	public function test_run( $request ) {
		$email_handler = new Xophz_Compass_Bomb_Bag_Email_Handler();
		$email_handler->process_journey_enrollments();

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Journey enrollment batch processed successfully',
			'executed_at' => current_time( 'mysql' )
		) );
	}
}

