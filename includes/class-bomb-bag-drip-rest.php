<?php

class Xophz_Compass_Bomb_Bag_Drip_Rest {

	private $namespace = 'xophz-compass/v1';

	public function register_routes() {
		register_rest_route( $this->namespace, '/bomb-bag/drips', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_sequences' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_sequence' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/drips/(?P<id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_sequence' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_sequence' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_sequence' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/drips/(?P<id>\d+)/steps', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'add_step' ),
			'permission_callback' => array( $this, 'check_admin' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/drips/(?P<id>\d+)/steps/(?P<step_id>\d+)', array(
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_step' ),
				'permission_callback' => array( $this, 'check_admin' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'delete_step' ),
				'permission_callback' => array( $this, 'check_admin' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/drips/(?P<id>\d+)/steps/reorder', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'reorder_steps' ),
			'permission_callback' => array( $this, 'check_admin' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/drips/(?P<id>\d+)/enrollments', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_enrollments' ),
			'permission_callback' => array( $this, 'check_admin' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/drips/(?P<id>\d+)/enroll', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'enroll_subscriber' ),
			'permission_callback' => array( $this, 'check_admin' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/drips/(?P<id>\d+)/analytics', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_analytics' ),
			'permission_callback' => array( $this, 'check_admin' ),
		));
	}

	public function check_admin() {
		return current_user_can( 'manage_options' );
	}

	public function get_sequences() {
		global $wpdb;
		$seq_table  = $wpdb->prefix . 'bomb_bag_drip_sequences';
		$step_table = $wpdb->prefix . 'bomb_bag_drip_steps';

		$sequences = $wpdb->get_results( "SELECT * FROM $seq_table ORDER BY created_at DESC" );

		foreach ( $sequences as &$seq ) {
			$seq->steps = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM $step_table WHERE sequence_id = %d ORDER BY position ASC",
				$seq->id
			));
		}

		return rest_ensure_response( $sequences );
	}

	public function get_sequence( $request ) {
		global $wpdb;
		$seq_table  = $wpdb->prefix . 'bomb_bag_drip_sequences';
		$step_table = $wpdb->prefix . 'bomb_bag_drip_steps';
		$id         = $request->get_param( 'id' );

		$sequence = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $seq_table WHERE id = %d", $id
		));

		if ( ! $sequence ) {
			return new WP_Error( 'not_found', 'Sequence not found', array( 'status' => 404 ) );
		}

		$sequence->steps = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM $step_table WHERE sequence_id = %d ORDER BY position ASC",
			$id
		));

		return rest_ensure_response( $sequence );
	}

	public function create_sequence( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_drip_sequences';

		$data = array(
			'name'         => sanitize_text_field( $request->get_param( 'name' ) ),
			'description'  => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'status'       => 'paused',
			'trigger_type' => sanitize_text_field( $request->get_param( 'trigger_type' ) ) ?: 'manual',
			'list_id'      => absint( $request->get_param( 'list_id' ) ) ?: null,
			'from_name'    => sanitize_text_field( $request->get_param( 'from_name' ) ),
			'from_email'   => sanitize_email( $request->get_param( 'from_email' ) ),
		);

		$result = $wpdb->insert( $table, $data );

		if ( $result === false ) {
			return new WP_Error( 'create_failed', 'Failed to create sequence', array( 'status' => 500 ) );
		}

		$data['id']    = $wpdb->insert_id;
		$data['steps'] = array();
		return rest_ensure_response( $data );
	}

	public function update_sequence( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_drip_sequences';
		$id    = $request->get_param( 'id' );

		$data   = array();
		$fields = array( 'name', 'description', 'status', 'trigger_type', 'list_id', 'from_name', 'from_email' );

		foreach ( $fields as $field ) {
			if ( $request->get_param( $field ) !== null ) {
				$is_email_field = $field === 'from_email';
				$is_list_field  = $field === 'list_id';

				if ( $is_email_field ) {
					$data[ $field ] = sanitize_email( $request->get_param( $field ) );
				} elseif ( $is_list_field ) {
					$data[ $field ] = absint( $request->get_param( $field ) ) ?: null;
				} else {
					$data[ $field ] = sanitize_text_field( $request->get_param( $field ) );
				}
			}
		}

		$wpdb->update( $table, $data, array( 'id' => $id ) );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_sequence( $request ) {
		global $wpdb;
		$id = $request->get_param( 'id' );

		$wpdb->delete( $wpdb->prefix . 'bomb_bag_drip_steps', array( 'sequence_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'bomb_bag_drip_enrollments', array( 'sequence_id' => $id ) );
		$wpdb->delete( $wpdb->prefix . 'bomb_bag_drip_sequences', array( 'id' => $id ) );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function add_step( $request ) {
		global $wpdb;
		$table       = $wpdb->prefix . 'bomb_bag_drip_steps';
		$sequence_id = $request->get_param( 'id' );

		$max_position = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(position) FROM $table WHERE sequence_id = %d",
			$sequence_id
		));

		$data = array(
			'sequence_id' => $sequence_id,
			'position'    => ( $max_position ?? -1 ) + 1,
			'subject'     => sanitize_text_field( $request->get_param( 'subject' ) ),
			'content'     => wp_kses_post( $request->get_param( 'content' ) ),
			'delay_days'  => absint( $request->get_param( 'delay_days' ) ),
			'delay_hours' => min( absint( $request->get_param( 'delay_hours' ) ), 23 ),
			'template_id' => absint( $request->get_param( 'template_id' ) ) ?: null,
		);

		$wpdb->insert( $table, $data );
		$data['id'] = $wpdb->insert_id;

		return rest_ensure_response( $data );
	}

	public function update_step( $request ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'bomb_bag_drip_steps';
		$step_id = $request->get_param( 'step_id' );

		$data = array();
		if ( $request->get_param( 'subject' ) !== null ) {
			$data['subject'] = sanitize_text_field( $request->get_param( 'subject' ) );
		}
		if ( $request->get_param( 'content' ) !== null ) {
			$data['content'] = wp_kses_post( $request->get_param( 'content' ) );
		}
		if ( $request->get_param( 'delay_days' ) !== null ) {
			$data['delay_days'] = absint( $request->get_param( 'delay_days' ) );
		}
		if ( $request->get_param( 'delay_hours' ) !== null ) {
			$data['delay_hours'] = min( absint( $request->get_param( 'delay_hours' ) ), 23 );
		}
		if ( $request->get_param( 'template_id' ) !== null ) {
			$data['template_id'] = absint( $request->get_param( 'template_id' ) ) ?: null;
		}

		$wpdb->update( $table, $data, array( 'id' => $step_id ) );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_step( $request ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'bomb_bag_drip_steps', array(
			'id' => $request->get_param( 'step_id' )
		));
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function reorder_steps( $request ) {
		global $wpdb;
		$table    = $wpdb->prefix . 'bomb_bag_drip_steps';
		$step_ids = $request->get_param( 'step_ids' );

		if ( ! is_array( $step_ids ) ) {
			return new WP_Error( 'invalid', 'step_ids must be an array', array( 'status' => 400 ) );
		}

		foreach ( $step_ids as $position => $step_id ) {
			$wpdb->update( $table, array( 'position' => $position ), array( 'id' => absint( $step_id ) ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_enrollments( $request ) {
		global $wpdb;
		$enroll_table = $wpdb->prefix . 'bomb_bag_drip_enrollments';
		$sub_table    = $wpdb->prefix . 'bomb_bag_subscribers';
		$seq_id       = $request->get_param( 'id' );

		$enrollments = $wpdb->get_results( $wpdb->prepare(
			"SELECT e.*, s.email as subscriber_email, 
			        CONCAT(s.first_name, ' ', s.last_name) as subscriber_name
			 FROM $enroll_table e
			 INNER JOIN $sub_table s ON e.subscriber_id = s.id
			 WHERE e.sequence_id = %d
			 ORDER BY e.enrolled_at DESC",
			$seq_id
		));

		return rest_ensure_response( $enrollments );
	}

	public function enroll_subscriber( $request ) {
		global $wpdb;
		$enroll_table = $wpdb->prefix . 'bomb_bag_drip_enrollments';
		$step_table   = $wpdb->prefix . 'bomb_bag_drip_steps';
		$seq_id       = $request->get_param( 'id' );
		$sub_id       = absint( $request->get_param( 'subscriber_id' ) );

		$already_enrolled = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $enroll_table WHERE sequence_id = %d AND subscriber_id = %d AND status = 'active'",
			$seq_id, $sub_id
		));

		if ( $already_enrolled ) {
			return new WP_Error( 'duplicate', 'Already enrolled', array( 'status' => 409 ) );
		}

		$first_step = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $step_table WHERE sequence_id = %d ORDER BY position ASC LIMIT 1",
			$seq_id
		));

		$delay_seconds = $first_step ? ( $first_step->delay_days * 86400 + $first_step->delay_hours * 3600 ) : 0;
		$next_send     = date( 'Y-m-d H:i:s', time() + $delay_seconds );

		$wpdb->insert( $enroll_table, array(
			'sequence_id'  => $seq_id,
			'subscriber_id' => $sub_id,
			'current_step' => 0,
			'status'       => 'active',
			'next_send_at' => $next_send,
		));

		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}bomb_bag_drip_sequences SET total_enrolled = total_enrolled + 1 WHERE id = %d",
			$seq_id
		));

		return rest_ensure_response( array( 'success' => true, 'message' => 'Subscriber enrolled' ) );
	}

	public function get_analytics( $request ) {
		global $wpdb;
		$seq_id       = $request->get_param( 'id' );
		$enroll_table = $wpdb->prefix . 'bomb_bag_drip_enrollments';
		$step_table   = $wpdb->prefix . 'bomb_bag_drip_steps';
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';

		$total_enrolled  = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $enroll_table WHERE sequence_id = %d", $seq_id
		));
		$total_completed = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $enroll_table WHERE sequence_id = %d AND status = 'completed'", $seq_id
		));
		$total_active    = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $enroll_table WHERE sequence_id = %d AND status = 'active'", $seq_id
		));

		$completion_rate = $total_enrolled > 0
			? round( ( $total_completed / $total_enrolled ) * 100, 1 )
			: 0;

		$steps = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, position, subject FROM $step_table WHERE sequence_id = %d ORDER BY position ASC",
			$seq_id
		));

		$step_performance = array();
		foreach ( $steps as $step ) {
			$step_performance[] = array(
				'step_id'    => (int) $step->id,
				'position'   => (int) $step->position,
				'subject'    => $step->subject,
				'sent'       => 0,
				'opened'     => 0,
				'clicked'    => 0,
				'open_rate'  => 0,
				'click_rate' => 0,
			);
		}

		return rest_ensure_response( array(
			'total_enrolled'   => (int) $total_enrolled,
			'total_completed'  => (int) $total_completed,
			'total_active'     => (int) $total_active,
			'completion_rate'  => $completion_rate,
			'step_performance' => $step_performance,
		));
	}
}
