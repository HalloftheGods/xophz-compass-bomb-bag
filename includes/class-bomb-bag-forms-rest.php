<?php
/**
 * REST Controller for Public Form Subscriptions
 */
class Xophz_Compass_Bomb_Bag_Forms_Rest {
	private $namespace;

	public function __construct() {
		$this->namespace = 'xophz-compass/v1';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/bomb-bag/subscribe', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_subscription' ),
			'permission_callback' => '__return_true', // Public endpoint
		) );
	}

	public function handle_subscription( $request ) {
		global $wpdb;

		$email      = sanitize_email( $request->get_param('email') );
		$first_name = sanitize_text_field( $request->get_param('first_name') );
		$last_name  = sanitize_text_field( $request->get_param('last_name') );
		$list_id    = absint( $request->get_param('list_id') );

		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', 'Please provide a valid email address.', array( 'status' => 400 ) );
		}

		$sub_table = $wpdb->prefix . 'bombbag_subscribers';
		$list_map_table = $wpdb->prefix . 'bombbag_subscriber_lists';

		// Check if subscriber exists
		$subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $sub_table WHERE email = %s", $email ) );

		if ( $subscriber ) {
			$subscriber_id = $subscriber->id;
			// Update name if provided
			$update_data = array();
			if ( $first_name ) $update_data['first_name'] = $first_name;
			if ( $last_name ) $update_data['last_name'] = $last_name;
			
			if ( ! empty( $update_data ) ) {
				$wpdb->update( $sub_table, $update_data, array( 'id' => $subscriber_id ) );
			}
		} else {
			// Insert new subscriber
			$wpdb->insert( $sub_table, array(
				'email'      => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'status'     => 'active', // Default to active for now
				'created_at' => current_time('mysql')
			) );
			$subscriber_id = $wpdb->insert_id;
		}

		// Assign to list if a list ID was provided
		if ( $list_id > 0 ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $list_map_table WHERE subscriber_id = %d AND list_id = %d",
				$subscriber_id, $list_id
			) );

			if ( ! $exists ) {
				$wpdb->insert( $list_map_table, array(
					'subscriber_id' => $subscriber_id,
					'list_id'       => $list_id,
					'status'        => 'subscribed',
					'subscribed_at' => current_time('mysql')
				) );
			}
		}

		// Fire subscription hook (useful for Journey triggers)
		do_action( 'bomb_bag_subscriber_created', $subscriber_id, $list_id );

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Successfully subscribed!'
		) );
	}
}
