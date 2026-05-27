<?php

/**
 * Bomb Bag Tag REST Controller
 */
class Xophz_Compass_Bomb_Bag_Tag_Rest extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'bomb-bag/v1';
		$this->rest_base = 'tags';
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

		// Subscriber Tags Relationship
		register_rest_route( $this->namespace, '/subscribers/(?P<id>[\d]+)/tags', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_subscriber_tags' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_subscriber_tags' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
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
		$table = $wpdb->prefix . 'bomb_bag_tags';
		
		$tags = $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" );

		return rest_ensure_response( $tags );
	}

	public function get_item( $request ) {
		global $wpdb;
		$id    = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_tags';

		$tag = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ) );

		if ( ! $tag ) {
			return new WP_Error( 'not_found', 'Tag not found', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $tag );
	}

	public function create_item( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_tags';

		$data = array(
			'name'        => sanitize_text_field( $request->get_param( 'name' ) ),
			'description' => sanitize_textarea_field( $request->get_param( 'description' ) ),
			'color'       => sanitize_text_field( $request->get_param( 'color' ) ),
		);

		// Check if name exists
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE name = %s", $data['name'] ) );
		if ( $existing ) {
			return new WP_Error( 'duplicate', 'Tag name already exists', array( 'status' => 400 ) );
		}

		$wpdb->insert( $table, $data );
		$id = $wpdb->insert_id;

		$request->set_param( 'id', $id );
		return $this->get_item( $request );
	}

	public function update_item( $request ) {
		global $wpdb;
		$id    = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_tags';

		$data = array();
		if ( $request->has_param( 'name' ) ) {
			$data['name'] = sanitize_text_field( $request->get_param( 'name' ) );
			// Check if name exists
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE name = %s AND id != %d", $data['name'], $id ) );
			if ( $existing ) {
				return new WP_Error( 'duplicate', 'Tag name already exists', array( 'status' => 400 ) );
			}
		}
		if ( $request->has_param( 'description' ) ) {
			$data['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
		}
		if ( $request->has_param( 'color' ) ) {
			$data['color'] = sanitize_text_field( $request->get_param( 'color' ) );
		}

		if ( ! empty( $data ) ) {
			$wpdb->update( $table, $data, array( 'id' => $id ) );
		}

		return $this->get_item( $request );
	}

	public function delete_item( $request ) {
		global $wpdb;
		$id    = (int) $request['id'];
		$table = $wpdb->prefix . 'bomb_bag_tags';
		$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		$wpdb->delete( $sub_tags_table, array( 'tag_id' => $id ) );
		$wpdb->delete( $table, array( 'id' => $id ) );

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public function get_subscriber_tags( $request ) {
		global $wpdb;
		$subscriber_id = (int) $request['id'];
		$tags_table = $wpdb->prefix . 'bomb_bag_tags';
		$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		$tags = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.* FROM $tags_table t
			 INNER JOIN $sub_tags_table st ON t.id = st.tag_id
			 WHERE st.subscriber_id = %d",
			$subscriber_id
		) );

		return rest_ensure_response( $tags );
	}

	public function update_subscriber_tags( $request ) {
		global $wpdb;
		$subscriber_id = (int) $request['id'];
		$tag_ids = $request->get_param( 'tag_ids' );
		$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		if ( ! is_array( $tag_ids ) ) {
			$tag_ids = array();
		}

		// Clear existing
		$wpdb->delete( $sub_tags_table, array( 'subscriber_id' => $subscriber_id ) );

		// Insert new
		foreach ( $tag_ids as $tag_id ) {
			$wpdb->insert( $sub_tags_table, array(
				'subscriber_id' => $subscriber_id,
				'tag_id'        => (int) $tag_id
			) );
		}

		return $this->get_subscriber_tags( $request );
	}
}
