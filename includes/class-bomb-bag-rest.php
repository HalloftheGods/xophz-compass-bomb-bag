<?php

/**
 * Bomb Bag REST API Controller
 *
 * @link       http://www.mycompassconsulting.com/
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/includes
 */

/**
 * REST API endpoints for Bomb Bag email marketing.
 *
 * Provides endpoints for campaigns, subscribers, lists, settings, and analytics.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/includes
 * @author     Xoph <xoph@midnightnerd.com>
 */
class Xophz_Compass_Bomb_Bag_Rest {

	/**
	 * The namespace for REST routes.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $namespace = 'xophz-compass/v1';

	/**
	 * Register REST routes.
	 *
	 * @since    1.0.0
	 */
	public function register_routes() {
		// Stats/Dashboard endpoint
		register_rest_route( $this->namespace, '/bomb-bag/stats', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_stats' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		// Settings endpoints
		register_rest_route( $this->namespace, '/bomb-bag/settings', array(
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'update_settings' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/settings/test', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'test_email_connection' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		// Campaigns endpoints
		register_rest_route( $this->namespace, '/bomb-bag/campaigns', array(
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_campaigns' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args' => array(
					'page' => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
					'per_page' => array( 'default' => 20, 'sanitize_callback' => 'absint' ),
					'status' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				)
			),
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'create_campaign' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/campaigns/(?P<id>\d+)', array(
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_campaign' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
			array(
				'methods'  => 'PUT',
				'callback' => array( $this, 'update_campaign' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
			array(
				'methods'  => 'DELETE',
				'callback' => array( $this, 'delete_campaign' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/campaigns/(?P<id>\d+)/send', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'send_campaign' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/campaigns/(?P<id>\d+)/test', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'send_test_email' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/campaigns/(?P<id>\d+)/schedule', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'schedule_campaign' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		// Subscribers endpoints
		register_rest_route( $this->namespace, '/bomb-bag/subscribers', array(
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_subscribers' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
				'args' => array(
					'page' => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
					'per_page' => array( 'default' => 20, 'sanitize_callback' => 'absint' ),
					'list_id' => array( 'sanitize_callback' => 'absint' ),
					'status' => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'search' => array( 'sanitize_callback' => 'sanitize_text_field' ),
				)
			),
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'create_subscriber' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/subscribers/(?P<id>\d+)', array(
			array(
				'methods'  => 'PUT',
				'callback' => array( $this, 'update_subscriber' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
			array(
				'methods'  => 'DELETE',
				'callback' => array( $this, 'delete_subscriber' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/subscribers/import', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'import_subscribers' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		// Lists endpoints
		register_rest_route( $this->namespace, '/bomb-bag/lists', array(
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_lists' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
			array(
				'methods'  => 'POST',
				'callback' => array( $this, 'create_list' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/lists/(?P<id>\d+)', array(
			array(
				'methods'  => 'PUT',
				'callback' => array( $this, 'update_list' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
			array(
				'methods'  => 'DELETE',
				'callback' => array( $this, 'delete_list' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		// Analytics endpoint
		register_rest_route( $this->namespace, '/bomb-bag/analytics/(?P<campaign_id>\d+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_campaign_analytics' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));
	}

	/**
	 * Check if user has admin permissions.
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	// =====================
	// STATS / DASHBOARD
	// =====================

	/**
	 * Get dashboard statistics.
	 *
	 * @since    1.0.0
	 * @return   WP_REST_Response
	 */
	public function get_stats() {
		global $wpdb;
		
		$campaigns_table = $wpdb->prefix . 'bomb_bag_campaigns';
		$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';
		$analytics_table = $wpdb->prefix . 'bomb_bag_analytics';

		// Total subscribers (active)
		$total_subscribers = $wpdb->get_var(
			"SELECT COUNT(*) FROM $subscribers_table WHERE status = 'active'"
		);

		// Total campaigns sent
		$campaigns_sent = $wpdb->get_var(
			"SELECT COUNT(*) FROM $campaigns_table WHERE status = 'sent'"
		);

		// Total emails sent
		$total_sent = $wpdb->get_var(
			"SELECT COUNT(*) FROM $emails_table WHERE status = 'sent'"
		);

		// Total opens
		$total_opens = $wpdb->get_var(
			"SELECT COUNT(DISTINCT email_id) FROM $analytics_table WHERE event_type = 'open'"
		);

		// Total clicks
		$total_clicks = $wpdb->get_var(
			"SELECT COUNT(DISTINCT email_id) FROM $analytics_table WHERE event_type = 'click'"
		);

		// Calculate rates
		$open_rate = $total_sent > 0 ? round(($total_opens / $total_sent) * 100, 1) : 0;
		$click_rate = $total_sent > 0 ? round(($total_clicks / $total_sent) * 100, 1) : 0;

		// Subscriber growth (last 30 days)
		$subscriber_growth = $wpdb->get_results($wpdb->prepare(
			"SELECT DATE(subscribed_at) as date, COUNT(*) as count 
			 FROM $subscribers_table 
			 WHERE subscribed_at >= %s AND status = 'active'
			 GROUP BY DATE(subscribed_at) 
			 ORDER BY date ASC",
			date('Y-m-d', strtotime('-30 days'))
		));

		// Recent campaigns with performance
		$recent_campaigns = $wpdb->get_results(
			"SELECT id, name, subject, status, sent_at, total_recipients, 
			        total_sent, total_opened, total_clicked 
			 FROM $campaigns_table 
			 ORDER BY created_at DESC 
			 LIMIT 5"
		);

		$drip_table = $wpdb->prefix . 'bomb_bag_drip_sequences';
		$active_drips = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM $drip_table WHERE status = 'active'"
		);

		return rest_ensure_response(array(
			'total_subscribers' => (int) $total_subscribers,
			'campaigns_sent' => (int) $campaigns_sent,
			'open_rate' => $open_rate,
			'click_rate' => $click_rate,
			'total_sent' => (int) $total_sent,
			'total_opens' => (int) $total_opens,
			'total_clicks' => (int) $total_clicks,
			'active_drips' => $active_drips,
			'subscriber_growth' => $subscriber_growth,
			'recent_campaigns' => $recent_campaigns
		));
	}

	// =====================
	// SETTINGS
	// =====================

	/**
	 * Get plugin settings.
	 *
	 * @since    1.0.0
	 * @return   WP_REST_Response
	 */
	public function get_settings() {
		$settings = get_option('bomb_bag_settings', array());
		
		// Mask sensitive data for security
		$masked = $settings;
		$sensitive_keys = array('sendgrid_api_key', 'mailgun_api_key', 'smtp_password');
		foreach ($sensitive_keys as $key) {
			if (!empty($masked[$key])) {
				$masked[$key] = '••••••••' . substr($masked[$key], -4);
			}
		}
		
		return rest_ensure_response($masked);
	}

	/**
	 * Update plugin settings.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function update_settings( $request ) {
		$current = get_option('bomb_bag_settings', array());
		$body = $request->get_json_params();

		// Only update non-masked values
		$sensitive_keys = array('sendgrid_api_key', 'mailgun_api_key', 'smtp_password');
		foreach ($sensitive_keys as $key) {
			if (isset($body[$key]) && strpos($body[$key], '••••') === 0) {
				unset($body[$key]); // Don't overwrite with masked value
			}
		}

		$updated = array_merge($current, $body);
		update_option('bomb_bag_settings', $updated);

		return rest_ensure_response(array('success' => true));
	}

	/**
	 * Test email connection/send.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function test_email_connection( $request ) {
		$settings = get_option('bomb_bag_settings', array());
		$test_email = sanitize_email($request->get_param('email')) ?: get_option('admin_email');

		$subject = 'Bomb Bag Test Email';
		$message = 'This is a test email from your Bomb Bag email marketing plugin. If you received this, your email configuration is working correctly!';
		
		$result = $this->send_email($test_email, $subject, $message);

		if ($result) {
			return rest_ensure_response(array('success' => true, 'message' => 'Test email sent successfully'));
		} else {
			return new WP_Error('email_failed', 'Failed to send test email', array('status' => 500));
		}
	}

	// =====================
	// CAMPAIGNS
	// =====================

	/**
	 * Get paginated campaigns.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function get_campaigns( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_campaigns';

		$page = $request->get_param('page');
		$per_page = min($request->get_param('per_page'), 100);
		$offset = ($page - 1) * $per_page;

		$where = array('1=1');
		$params = array();

		if ($request->get_param('status')) {
			$where[] = 'status = %s';
			$params[] = $request->get_param('status');
		}

		$where_sql = implode(' AND ', $where);

		// Total count
		$count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
		if (!empty($params)) {
			$count_sql = $wpdb->prepare($count_sql, $params);
		}
		$total = $wpdb->get_var($count_sql);

		// Get campaigns
		$sql = "SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;
		$campaigns = $wpdb->get_results($wpdb->prepare($sql, $params));

		return rest_ensure_response(array(
			'campaigns' => $campaigns,
			'total' => (int) $total,
			'page' => $page,
			'per_page' => $per_page,
			'total_pages' => ceil($total / $per_page)
		));
	}

	/**
	 * Get a single campaign.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function get_campaign( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_campaigns';
		$id = $request->get_param('id');

		$campaign = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table WHERE id = %d", $id
		));

		if (!$campaign) {
			return new WP_Error('not_found', 'Campaign not found', array('status' => 404));
		}

		return rest_ensure_response($campaign);
	}

	/**
	 * Create a new campaign.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function create_campaign( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_campaigns';

		$data = array(
			'name' => sanitize_text_field($request->get_param('name')),
			'subject' => sanitize_text_field($request->get_param('subject')),
			'content' => wp_kses_post($request->get_param('content')),
			'from_name' => sanitize_text_field($request->get_param('from_name')),
			'from_email' => sanitize_email($request->get_param('from_email')),
			'list_id' => absint($request->get_param('list_id')),
			'status' => 'draft'
		);

		$result = $wpdb->insert($table, $data);

		if ($result === false) {
			return new WP_Error('create_failed', 'Failed to create campaign', array('status' => 500));
		}

		$data['id'] = $wpdb->insert_id;
		return rest_ensure_response($data);
	}

	/**
	 * Update a campaign.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function update_campaign( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_campaigns';
		$id = $request->get_param('id');

		$data = array();
		$fields = array('name', 'subject', 'from_name', 'from_email', 'list_id', 'scheduled_at');

		foreach ($fields as $field) {
			if ($request->get_param($field) !== null) {
				if ($field === 'list_id') {
					$data[$field] = absint($request->get_param($field));
				} elseif ($field === 'from_email') {
					$data[$field] = sanitize_email($request->get_param($field));
				} else {
					$data[$field] = sanitize_text_field($request->get_param($field));
				}
			}
		}

		if ($request->get_param('content') !== null) {
			$data['content'] = wp_kses_post($request->get_param('content'));
		}

		$result = $wpdb->update($table, $data, array('id' => $id));

		if ($result === false) {
			return new WP_Error('update_failed', 'Failed to update campaign', array('status' => 500));
		}

		return rest_ensure_response(array('success' => true, 'id' => $id));
	}

	/**
	 * Delete a campaign.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function delete_campaign( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_campaigns';
		$id = $request->get_param('id');

		$result = $wpdb->delete($table, array('id' => $id));

		if ($result === false) {
			return new WP_Error('delete_failed', 'Failed to delete campaign', array('status' => 500));
		}

		return rest_ensure_response(array('success' => true));
	}

	/**
	 * Send a campaign to all subscribers.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function send_campaign( $request ) {
		global $wpdb;
		$id = $request->get_param('id');

		// This should trigger the email handler
		$handler = new Xophz_Compass_Bomb_Bag_Email_Handler();
		$result = $handler->queue_campaign($id);

		if (is_wp_error($result)) {
			return $result;
		}

		return rest_ensure_response(array('success' => true, 'message' => 'Campaign queued for sending'));
	}

	/**
	 * Send a test email for a campaign.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function send_test_email( $request ) {
		global $wpdb;
		$id = $request->get_param('id');
		$test_email = sanitize_email($request->get_param('email')) ?: get_option('admin_email');

		$campaign = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . $wpdb->prefix . "bomb_bag_campaigns WHERE id = %d", $id
		));

		if (!$campaign) {
			return new WP_Error('not_found', 'Campaign not found', array('status' => 404));
		}

		$result = $this->send_email($test_email, '[TEST] ' . $campaign->subject, $campaign->content);

		if ($result) {
			return rest_ensure_response(array('success' => true, 'message' => 'Test email sent'));
		} else {
			return new WP_Error('email_failed', 'Failed to send test email', array('status' => 500));
		}
	}

	public function schedule_campaign( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_campaigns';
		$id = $request->get_param('id');
		$scheduled_at = sanitize_text_field( $request->get_param('scheduled_at') );

		if ( empty( $scheduled_at ) ) {
			return new WP_Error( 'missing_date', 'scheduled_at is required', array( 'status' => 400 ) );
		}

		$result = $wpdb->update( $table, array(
			'status' => 'scheduled',
			'scheduled_at' => $scheduled_at
		), array( 'id' => $id ) );

		if ( $result === false ) {
			return new WP_Error( 'schedule_failed', 'Failed to schedule campaign', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'success' => true, 'message' => 'Campaign scheduled' ) );
	}

	// =====================
	// SUBSCRIBERS
	// =====================

	/**
	 * Get paginated subscribers.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function get_subscribers( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$page = $request->get_param('page');
		$per_page = min($request->get_param('per_page'), 100);
		$offset = ($page - 1) * $per_page;

		$where = array('1=1');
		$params = array();

		if ($request->get_param('status')) {
			$where[] = 's.status = %s';
			$params[] = $request->get_param('status');
		}

		if ($request->get_param('search')) {
			$search = '%' . $wpdb->esc_like($request->get_param('search')) . '%';
			$where[] = '(s.email LIKE %s OR s.first_name LIKE %s OR s.last_name LIKE %s)';
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
		}

		$list_join = '';
		if ($request->get_param('list_id')) {
			$list_join = "INNER JOIN $junction ls ON s.id = ls.subscriber_id";
			$where[] = 'ls.list_id = %d';
			$params[] = $request->get_param('list_id');
		}

		$where_sql = implode(' AND ', $where);

		// Total count
		$count_sql = "SELECT COUNT(DISTINCT s.id) FROM $table s $list_join WHERE $where_sql";
		if (!empty($params)) {
			$count_sql = $wpdb->prepare($count_sql, $params);
		}
		$total = $wpdb->get_var($count_sql);

		// Get subscribers
		$sql = "SELECT DISTINCT s.* FROM $table s $list_join WHERE $where_sql ORDER BY s.created_at DESC LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;
		$subscribers = $wpdb->get_results($wpdb->prepare($sql, $params));

		return rest_ensure_response(array(
			'subscribers' => $subscribers,
			'total' => (int) $total,
			'page' => $page,
			'per_page' => $per_page,
			'total_pages' => ceil($total / $per_page)
		));
	}

	/**
	 * Create a new subscriber.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function create_subscriber( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$email = sanitize_email($request->get_param('email'));
		
		// Check for duplicate
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $table WHERE email = %s", $email
		));
		
		if ($existing) {
			return new WP_Error('duplicate', 'Email already exists', array('status' => 409));
		}

		$data = array(
			'email' => $email,
			'first_name' => sanitize_text_field($request->get_param('first_name')),
			'last_name' => sanitize_text_field($request->get_param('last_name')),
			'status' => 'active',
			'source' => sanitize_text_field($request->get_param('source')) ?: 'manual'
		);

		$result = $wpdb->insert($table, $data);

		if ($result === false) {
			return new WP_Error('create_failed', 'Failed to create subscriber', array('status' => 500));
		}

		$subscriber_id = $wpdb->insert_id;
		$data['id'] = $subscriber_id;

		// Add to list if specified
		$list_id = absint($request->get_param('list_id'));
		if ($list_id) {
			$wpdb->insert($junction, array(
				'list_id' => $list_id,
				'subscriber_id' => $subscriber_id
			));
			$this->update_list_count($list_id);
		}

		return rest_ensure_response($data);
	}

	/**
	 * Update a subscriber.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function update_subscriber( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$id = $request->get_param('id');

		$data = array();
		
		if ($request->get_param('email') !== null) {
			$data['email'] = sanitize_email($request->get_param('email'));
		}
		if ($request->get_param('first_name') !== null) {
			$data['first_name'] = sanitize_text_field($request->get_param('first_name'));
		}
		if ($request->get_param('last_name') !== null) {
			$data['last_name'] = sanitize_text_field($request->get_param('last_name'));
		}
		if ($request->get_param('status') !== null) {
			$data['status'] = sanitize_text_field($request->get_param('status'));
			if ($data['status'] === 'unsubscribed') {
				$data['unsubscribed_at'] = current_time('mysql');
			}
		}

		$result = $wpdb->update($table, $data, array('id' => $id));

		if ($result === false) {
			return new WP_Error('update_failed', 'Failed to update subscriber', array('status' => 500));
		}

		return rest_ensure_response(array('success' => true, 'id' => $id));
	}

	/**
	 * Delete a subscriber.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function delete_subscriber( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$id = $request->get_param('id');

		// Remove from all lists first
		$wpdb->delete($junction, array('subscriber_id' => $id));

		// Delete subscriber
		$result = $wpdb->delete($table, array('id' => $id));

		if ($result === false) {
			return new WP_Error('delete_failed', 'Failed to delete subscriber', array('status' => 500));
		}

		return rest_ensure_response(array('success' => true));
	}

	/**
	 * Import subscribers from CSV.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function import_subscribers( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$subscribers = $request->get_param('subscribers');
		$list_id = absint($request->get_param('list_id'));

		$imported = 0;
		$skipped = 0;

		foreach ($subscribers as $sub) {
			$email = sanitize_email($sub['email'] ?? '');
			if (!is_email($email)) {
				$skipped++;
				continue;
			}

			// Check for duplicate
			$existing = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM $table WHERE email = %s", $email
			));

			if ($existing) {
				// Just add to list
				if ($list_id) {
					$wpdb->query($wpdb->prepare(
						"INSERT IGNORE INTO $junction (list_id, subscriber_id) VALUES (%d, %d)",
						$list_id, $existing
					));
				}
				$skipped++;
				continue;
			}

			$data = array(
				'email' => $email,
				'first_name' => sanitize_text_field($sub['first_name'] ?? ''),
				'last_name' => sanitize_text_field($sub['last_name'] ?? ''),
				'status' => 'active',
				'source' => 'import'
			);

			$wpdb->insert($table, $data);
			$subscriber_id = $wpdb->insert_id;

			if ($list_id && $subscriber_id) {
				$wpdb->insert($junction, array(
					'list_id' => $list_id,
					'subscriber_id' => $subscriber_id
				));
			}

			$imported++;
		}

		if ($list_id) {
			$this->update_list_count($list_id);
		}

		return rest_ensure_response(array(
			'success' => true,
			'imported' => $imported,
			'skipped' => $skipped
		));
	}

	// =====================
	// LISTS
	// =====================

	/**
	 * Get all lists.
	 *
	 * @since    1.0.0
	 * @return   WP_REST_Response
	 */
	public function get_lists() {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_lists';

		$lists = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

		return rest_ensure_response($lists);
	}

	/**
	 * Create a new list.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function create_list( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_lists';

		$data = array(
			'name' => sanitize_text_field($request->get_param('name')),
			'description' => sanitize_textarea_field($request->get_param('description'))
		);

		$result = $wpdb->insert($table, $data);

		if ($result === false) {
			return new WP_Error('create_failed', 'Failed to create list', array('status' => 500));
		}

		$data['id'] = $wpdb->insert_id;
		$data['subscriber_count'] = 0;
		return rest_ensure_response($data);
	}

	/**
	 * Update a list.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function update_list( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_lists';
		$id = $request->get_param('id');

		$data = array();
		if ($request->get_param('name') !== null) {
			$data['name'] = sanitize_text_field($request->get_param('name'));
		}
		if ($request->get_param('description') !== null) {
			$data['description'] = sanitize_textarea_field($request->get_param('description'));
		}

		$result = $wpdb->update($table, $data, array('id' => $id));

		if ($result === false) {
			return new WP_Error('update_failed', 'Failed to update list', array('status' => 500));
		}

		return rest_ensure_response(array('success' => true, 'id' => $id));
	}

	/**
	 * Delete a list.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function delete_list( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_lists';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$id = $request->get_param('id');

		// Remove all subscribers from this list
		$wpdb->delete($junction, array('list_id' => $id));

		// Delete list
		$result = $wpdb->delete($table, array('id' => $id));

		if ($result === false) {
			return new WP_Error('delete_failed', 'Failed to delete list', array('status' => 500));
		}

		return rest_ensure_response(array('success' => true));
	}

	// =====================
	// ANALYTICS
	// =====================

	/**
	 * Get analytics for a specific campaign.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function get_campaign_analytics( $request ) {
		global $wpdb;
		$campaign_id = $request->get_param('campaign_id');
		
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';
		$analytics_table = $wpdb->prefix . 'bomb_bag_analytics';

		// Get email stats
		$emails_sent = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $emails_table WHERE campaign_id = %d AND status = 'sent'",
			$campaign_id
		));

		$opens = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT e.id) FROM $emails_table e 
			 INNER JOIN $analytics_table a ON e.id = a.email_id 
			 WHERE e.campaign_id = %d AND a.event_type = 'open'",
			$campaign_id
		));

		$clicks = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(DISTINCT e.id) FROM $emails_table e 
			 INNER JOIN $analytics_table a ON e.id = a.email_id 
			 WHERE e.campaign_id = %d AND a.event_type = 'click'",
			$campaign_id
		));

		$unsubscribes = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $emails_table e 
			 INNER JOIN $analytics_table a ON e.id = a.email_id 
			 WHERE e.campaign_id = %d AND a.event_type = 'unsubscribe'",
			$campaign_id
		));

		// Activity over time
		$activity = $wpdb->get_results($wpdb->prepare(
			"SELECT DATE(a.created_at) as date, a.event_type, COUNT(*) as count 
			 FROM $analytics_table a
			 INNER JOIN $emails_table e ON a.email_id = e.id
			 WHERE e.campaign_id = %d
			 GROUP BY DATE(a.created_at), a.event_type
			 ORDER BY date ASC",
			$campaign_id
		));

		return rest_ensure_response(array(
			'emails_sent' => (int) $emails_sent,
			'opens' => (int) $opens,
			'clicks' => (int) $clicks,
			'unsubscribes' => (int) $unsubscribes,
			'open_rate' => $emails_sent > 0 ? round(($opens / $emails_sent) * 100, 1) : 0,
			'click_rate' => $emails_sent > 0 ? round(($clicks / $emails_sent) * 100, 1) : 0,
			'activity' => $activity
		));
	}

	// =====================
	// HELPERS
	// =====================

	/**
	 * Update list subscriber count.
	 *
	 * @since    1.0.0
	 * @param    int $list_id
	 */
	private function update_list_count( $list_id ) {
		global $wpdb;
		$lists_table = $wpdb->prefix . 'bomb_bag_lists';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $junction WHERE list_id = %d",
			$list_id
		));

		$wpdb->update($lists_table, array('subscriber_count' => $count), array('id' => $list_id));
	}

	/**
	 * Send an email using configured provider.
	 *
	 * @since    1.0.0
	 * @param    string $to
	 * @param    string $subject
	 * @param    string $content
	 * @return   bool
	 */
	private function send_email( $to, $subject, $content ) {
		return Xophz_Compass_Bomb_Bag_Email_Providers::send( $to, $subject, $content );
	}
}
