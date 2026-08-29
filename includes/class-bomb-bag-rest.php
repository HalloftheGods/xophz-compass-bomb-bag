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

		register_rest_route( $this->namespace, '/bomb-bag/subscribers/(?P<id>\d+)/lists', array(
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_subscriber_lists' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			),
			array(
				'methods'  => 'PUT',
				'callback' => array( $this, 'update_subscriber_lists' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		));

		register_rest_route( $this->namespace, '/bomb-bag/subscribers/import', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'import_subscribers' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/subscribers/export', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'export_subscribers' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/subscribers/sync-wp-users', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'sync_wp_users' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/subscribers/bulk', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'bulk_subscriber_actions' ),
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

		register_rest_route( $this->namespace, '/bomb-bag/lists/(?P<id>\d+)/merge', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'merge_list' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/lists/(?P<id>\d+)/duplicate', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'duplicate_list' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		register_rest_route( $this->namespace, '/bomb-bag/lists/(?P<id>\d+)/scrub', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'scrub_list' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		// Analytics endpoint
		register_rest_route( $this->namespace, '/bomb-bag/analytics/(?P<campaign_id>\d+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_campaign_analytics' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		));

		// Tracking endpoints (public)
		register_rest_route( $this->namespace, '/bomb-bag/track', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'handle_tracking_request' ),
			'permission_callback' => '__return_true', // Public
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
	// TRACKING
	// =====================

	/**
	 * Handle tracking pixel and link click requests.
	 *
	 * @since    1.0.0
	 */
	public function handle_tracking_request( $request ) {
		$action = sanitize_text_field( $request->get_param( 'px_track' ) );
		$tracking_id = sanitize_text_field( $request->get_param( 't_id' ) );

		if ( empty( $action ) || empty( $tracking_id ) ) {
			return new WP_Error( 'invalid_tracking', 'Missing tracking parameters', array( 'status' => 400 ) );
		}

		$email_handler = new Xophz_Compass_Bomb_Bag_Email_Handler();

		switch ( $action ) {
			case 'open':
				$email_handler->track_open( $tracking_id );
				// Return 1x1 transparent GIF
				header( 'Content-Type: image/gif' );
				echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
				exit;

			case 'click':
				$url = urldecode( $request->get_param( 'url' ) ?? '' );
				if ( $url && filter_var( $url, FILTER_VALIDATE_URL ) ) {
					$email_handler->track_click( $tracking_id, $url );
					wp_redirect( $url );
					exit;
				}
				break;

			case 'unsubscribe':
				$result = $email_handler->handle_unsubscribe( $tracking_id );
				if ( $result ) {
					wp_die( 
						'<h1>Unsubscribed</h1><p>You have been successfully unsubscribed from our mailing list.</p>',
						'Unsubscribed',
						array( 'response' => 200 )
					);
				} else {
					wp_die( 
						'<h1>Error</h1><p>Invalid or expired unsubscribe link.</p>',
						'Error',
						array( 'response' => 400 )
					);
				}
				break;
		}

		return new WP_Error( 'invalid_action', 'Invalid tracking action', array( 'status' => 400 ) );
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

		if (in_array($campaign->status, array('draft', 'scheduled'))) {
			$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';
			$list_subs_table = $wpdb->prefix . 'bomb_bag_list_subscribers';
			
			if ($campaign->list_id) {
				$campaign->total_recipients = (int) $wpdb->get_var($wpdb->prepare(
					"SELECT COUNT(s.id) FROM $subscribers_table s
					 INNER JOIN $list_subs_table ls ON s.id = ls.subscriber_id
					 WHERE ls.list_id = %d AND s.status = 'active'",
					$campaign->list_id
				));
			} else {
				$campaign->total_recipients = (int) $wpdb->get_var(
					"SELECT COUNT(id) FROM $subscribers_table WHERE status = 'active'"
				);
			}
		}

		$variants_table = $wpdb->prefix . 'bomb_bag_campaign_variants';
		$variants = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $variants_table WHERE campaign_id = %d ORDER BY id ASC", $id
		));

		$campaign->variants = $variants;

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
			'content' => current_user_can('unfiltered_html') ? $request->get_param('content') : wp_kses_post($request->get_param('content')),
			'from_name' => sanitize_text_field($request->get_param('from_name')),
			'from_email' => sanitize_email($request->get_param('from_email')),
			'list_id' => absint($request->get_param('list_id')),
			'status' => 'draft'
		);

		if ($request->get_param('template_id') !== null) {
			$data['template_id'] = $request->get_param('template_id') === '' ? null : absint($request->get_param('template_id'));
		}

		$result = $wpdb->insert($table, $data);

		if ($result === false) {
			return new WP_Error('create_failed', 'Failed to create campaign', array('status' => 500));
		}

		$data['id'] = $wpdb->insert_id;

		$variants = $request->get_param('variants');
		if (is_array($variants) && !empty($variants)) {
			$variants_table = $wpdb->prefix . 'bomb_bag_campaign_variants';
			foreach ($variants as $variant) {
				$wpdb->insert($variants_table, array(
					'campaign_id' => $data['id'],
					'subject' => sanitize_text_field($variant['subject'] ?? ''),
					'content' => wp_kses_post($variant['content'] ?? ''),
					'weight_percentage' => absint($variant['weight_percentage'] ?? 100)
				));
			}
		}

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

		if ($request->get_param('template_id') !== null) {
			$data['template_id'] = $request->get_param('template_id') === '' ? null : absint($request->get_param('template_id'));
		}

		if ($request->get_param('content') !== null) {
			$data['content'] = current_user_can('unfiltered_html') ? $request->get_param('content') : wp_kses_post($request->get_param('content'));
		}

		if (!empty($data)) {
			$result = $wpdb->update($table, $data, array('id' => $id));

			if ($result === false) {
				return new WP_Error('update_failed', 'Failed to update campaign', array('status' => 500));
			}
		}

		$variants = $request->get_param('variants');
		if (is_array($variants)) {
			$variants_table = $wpdb->prefix . 'bomb_bag_campaign_variants';
			// Clear existing variants and recreate
			$wpdb->delete($variants_table, array('campaign_id' => $id));
			foreach ($variants as $variant) {
				$wpdb->insert($variants_table, array(
					'campaign_id' => $id,
					'subject' => sanitize_text_field($variant['subject'] ?? ''),
					'content' => wp_kses_post($variant['content'] ?? ''),
					'weight_percentage' => absint($variant['weight_percentage'] ?? 100)
				));
			}
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

		$email_content = Xophz_Compass_Bomb_Bag_Email_Handler::apply_template($campaign->content, $campaign->template_id);
		$result = $this->send_email($test_email, '[TEST] ' . $campaign->subject, $email_content);

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

		$page = $request->get_param('page') ?: 1;
		$per_page = min($request->get_param('per_page') ?: 20, 100);
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

		if (!empty($subscribers)) {
			$sub_ids = wp_list_pluck($subscribers, 'id');
			$ids_in = implode(',', array_map('absint', $sub_ids));

			// Fetch list associations
			$lists_table = $wpdb->prefix . 'bomb_bag_lists';
			$list_rows = $wpdb->get_results("SELECT ls.subscriber_id, l.id, l.name FROM $junction ls INNER JOIN $lists_table l ON ls.list_id = l.id WHERE ls.subscriber_id IN ($ids_in)");
			$lists_by_sub = array();
			foreach ($list_rows as $lr) {
				$lists_by_sub[$lr->subscriber_id][] = array('id' => (int)$lr->id, 'name' => $lr->name);
			}

			// Fetch tag associations
			$tags_table = $wpdb->prefix . 'bomb_bag_tags';
			$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';
			$tag_rows = $wpdb->get_results("SELECT st.subscriber_id, t.id, t.name, t.color FROM $sub_tags_table st INNER JOIN $tags_table t ON st.tag_id = t.id WHERE st.subscriber_id IN ($ids_in)");
			$tags_by_sub = array();
			foreach ($tag_rows as $tr) {
				$tags_by_sub[$tr->subscriber_id][] = array('id' => (int)$tr->id, 'name' => $tr->name, 'color' => $tr->color);
			}

			foreach ($subscribers as &$sub) {
				$sub->id = (int)$sub->id;
				$sub->score = (int)($sub->score ?? 0);
				$sub->lead_status = $sub->lead_status ?: 'cold';
				$sub->lists = $lists_by_sub[$sub->id] ?? array();
				$sub->tags = $tags_by_sub[$sub->id] ?? array();

				if (!empty($sub->custom_fields) && is_string($sub->custom_fields)) {
					$decoded = json_decode($sub->custom_fields, true);
					$sub->custom_fields = is_array($decoded) ? $decoded : new stdClass();
				} else {
					$sub->custom_fields = new stdClass();
				}
			}
			unset($sub);
		}

		return rest_ensure_response(array(
			'subscribers' => $subscribers,
			'total' => (int) $total,
			'page' => (int) $page,
			'per_page' => (int) $per_page,
			'total_pages' => (int) ceil($total / $per_page)
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

		do_action( 'bomb_bag_subscriber_created', $subscriber_id, $list_id );

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

		// Get lists the subscriber belongs to before removing them
		$list_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT list_id FROM $junction WHERE subscriber_id = %d",
			$id
		));

		// Remove from all lists first
		$wpdb->delete($junction, array('subscriber_id' => $id));

		// Delete subscriber
		$result = $wpdb->delete($table, array('id' => $id));

		if ($result === false) {
			return new WP_Error('delete_failed', 'Failed to delete subscriber', array('status' => 500));
		}

		// Update counts for affected lists
		if (!empty($list_ids)) {
			foreach ($list_ids as $list_id) {
				$this->update_list_count($list_id);
			}
		}

		return rest_ensure_response(array('success' => true));
	}

	/**
	 * Get subscriber's lists.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function get_subscriber_lists( $request ) {
		global $wpdb;
		$subscriber_id = (int) $request['id'];
		$lists_table = $wpdb->prefix . 'bomb_bag_lists';
		$list_subs_table = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$lists = $wpdb->get_results( $wpdb->prepare(
			"SELECT l.* FROM $lists_table l
			 INNER JOIN $list_subs_table ls ON l.id = ls.list_id
			 WHERE ls.subscriber_id = %d",
			$subscriber_id
		) );

		return rest_ensure_response( $lists );
	}

	/**
	 * Update subscriber's lists.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function update_subscriber_lists( $request ) {
		global $wpdb;
		$subscriber_id = (int) $request['id'];
		$list_ids = $request->get_param( 'list_ids' );
		$list_subs_table = $wpdb->prefix . 'bomb_bag_list_subscribers';

		if ( ! is_array( $list_ids ) ) {
			$list_ids = array();
		}

		// Get old list IDs to update counts
		$old_list_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT list_id FROM $list_subs_table WHERE subscriber_id = %d",
			$subscriber_id
		) );

		// Clear existing
		$wpdb->delete( $list_subs_table, array( 'subscriber_id' => $subscriber_id ) );

		// Insert new
		foreach ( $list_ids as $list_id ) {
			$wpdb->insert( $list_subs_table, array(
				'subscriber_id' => $subscriber_id,
				'list_id'       => (int) $list_id
			) );
		}

		// Update counts for all affected lists (both old and new)
		$all_affected_lists = array_unique( array_merge( $old_list_ids, array_map( 'intval', $list_ids ) ) );
		foreach ( $all_affected_lists as $list_id ) {
			$this->update_list_count( $list_id );
		}

		return $this->get_subscriber_lists( $request );
	}

	/**
	 * Bulk actions on subscribers (add/remove from list, add/remove tags, update status, delete).
	 *
	 * @since    1.1.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response|WP_Error
	 */
	public function bulk_subscriber_actions( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		$action = sanitize_text_field( $request->get_param('action') );
		$subscriber_ids = $request->get_param('subscriber_ids');

		if ( empty($subscriber_ids) || !is_array($subscriber_ids) ) {
			return new WP_Error('invalid_ids', 'No subscriber IDs provided', array('status' => 400));
		}

		$subscriber_ids = array_unique(array_filter(array_map('absint', $subscriber_ids)));
		if ( empty($subscriber_ids) ) {
			return new WP_Error('invalid_ids', 'No valid subscriber IDs provided', array('status' => 400));
		}

		$ids_in = implode(',', $subscriber_ids);
		$count = count($subscriber_ids);

		switch ($action) {
			case 'add_to_list':
				$list_ids = $request->get_param('list_ids');
				if (empty($list_ids) && $request->get_param('list_id')) {
					$list_ids = array($request->get_param('list_id'));
				}
				if (empty($list_ids) || !is_array($list_ids)) {
					return new WP_Error('invalid_list', 'No target list specified', array('status' => 400));
				}
				$list_ids = array_unique(array_filter(array_map('absint', $list_ids)));
				foreach ($list_ids as $lid) {
					foreach ($subscriber_ids as $sid) {
						$wpdb->query($wpdb->prepare(
							"INSERT IGNORE INTO $junction (list_id, subscriber_id) VALUES (%d, %d)",
							$lid, $sid
						));
					}
					$this->update_list_count($lid);
				}
				return rest_ensure_response(array('success' => true, 'message' => "Added $count subscriber(s) to selected list(s)."));

			case 'remove_from_list':
				$list_ids = $request->get_param('list_ids');
				if (empty($list_ids) && $request->get_param('list_id')) {
					$list_ids = array($request->get_param('list_id'));
				}
				if (empty($list_ids) || !is_array($list_ids)) {
					return new WP_Error('invalid_list', 'No target list specified', array('status' => 400));
				}
				$list_ids = array_unique(array_filter(array_map('absint', $list_ids)));
				$lids_in = implode(',', $list_ids);
				$wpdb->query("DELETE FROM $junction WHERE subscriber_id IN ($ids_in) AND list_id IN ($lids_in)");
				foreach ($list_ids as $lid) {
					$this->update_list_count($lid);
				}
				return rest_ensure_response(array('success' => true, 'message' => "Removed $count subscriber(s) from selected list(s)."));

			case 'add_tags':
				$tag_ids = $request->get_param('tag_ids');
				if (empty($tag_ids) || !is_array($tag_ids)) {
					return new WP_Error('invalid_tags', 'No tags specified', array('status' => 400));
				}
				$tag_ids = array_unique(array_filter(array_map('absint', $tag_ids)));
				foreach ($tag_ids as $tid) {
					foreach ($subscriber_ids as $sid) {
						$wpdb->query($wpdb->prepare(
							"INSERT IGNORE INTO $sub_tags_table (tag_id, subscriber_id) VALUES (%d, %d)",
							$tid, $sid
						));
						do_action('bomb_bag_subscriber_tag_added', $sid, $tid);
					}
				}
				return rest_ensure_response(array('success' => true, 'message' => "Added tags to $count subscriber(s)."));

			case 'remove_tags':
				$tag_ids = $request->get_param('tag_ids');
				if (empty($tag_ids) || !is_array($tag_ids)) {
					return new WP_Error('invalid_tags', 'No tags specified', array('status' => 400));
				}
				$tag_ids = array_unique(array_filter(array_map('absint', $tag_ids)));
				$tids_in = implode(',', $tag_ids);
				$wpdb->query("DELETE FROM $sub_tags_table WHERE subscriber_id IN ($ids_in) AND tag_id IN ($tids_in)");
				return rest_ensure_response(array('success' => true, 'message' => "Removed tags from $count subscriber(s)."));

			case 'set_status':
				$status = sanitize_text_field($request->get_param('status'));
				if (!in_array($status, array('active', 'unsubscribed', 'bounced', 'complained'), true)) {
					return new WP_Error('invalid_status', 'Invalid status provided', array('status' => 400));
				}
				if ($status === 'unsubscribed') {
					$wpdb->query($wpdb->prepare(
						"UPDATE $table SET status = %s, unsubscribed_at = %s WHERE id IN ($ids_in)",
						$status, current_time('mysql')
					));
				} else {
					$wpdb->query($wpdb->prepare(
						"UPDATE $table SET status = %s WHERE id IN ($ids_in)",
						$status
					));
				}
				return rest_ensure_response(array('success' => true, 'message' => "Updated status to '$status' for $count subscriber(s)."));

			case 'delete':
				// Find affected lists
				$affected_lists = $wpdb->get_col("SELECT DISTINCT list_id FROM $junction WHERE subscriber_id IN ($ids_in)");
				// Delete junction and tags
				$wpdb->query("DELETE FROM $junction WHERE subscriber_id IN ($ids_in)");
				$wpdb->query("DELETE FROM $sub_tags_table WHERE subscriber_id IN ($ids_in)");
				// Delete subscribers
				$wpdb->query("DELETE FROM $table WHERE id IN ($ids_in)");
				// Recount affected lists
				if (!empty($affected_lists)) {
					foreach ($affected_lists as $lid) {
						$this->update_list_count($lid);
					}
				}
				return rest_ensure_response(array('success' => true, 'message' => "Permanently deleted $count subscriber(s)."));

			default:
				return new WP_Error('invalid_action', 'Unrecognized bulk action', array('status' => 400));
		}
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
		$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		$subscribers = $request->get_param('subscribers');
		$list_id = absint($request->get_param('list_id'));
		$tag_ids = $request->get_param('tag_ids');
		if ( !is_array($tag_ids) ) {
			$tag_ids = array();
		}
		$tag_ids = array_unique(array_filter(array_map('absint', $tag_ids)));

		$imported = 0;
		$skipped = 0;

		if ( !is_array($subscribers) ) {
			return new WP_Error('invalid_data', 'No subscribers provided', array('status' => 400));
		}

		foreach ($subscribers as $sub) {
			$email = sanitize_email($sub['email'] ?? '');
			if (!is_email($email)) {
				$skipped++;
				continue;
			}

			$custom_fields_json = null;
			if (!empty($sub['custom_fields'])) {
				$custom_fields_json = is_string($sub['custom_fields']) ? $sub['custom_fields'] : wp_json_encode($sub['custom_fields']);
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
				// Add tags if provided
				foreach ($tag_ids as $tid) {
					$wpdb->query($wpdb->prepare(
						"INSERT IGNORE INTO $sub_tags_table (tag_id, subscriber_id) VALUES (%d, %d)",
						$tid, $existing
					));
				}
				// Update custom fields if supplied and previously empty
				if ($custom_fields_json) {
					$wpdb->query($wpdb->prepare(
						"UPDATE $table SET custom_fields = COALESCE(NULLIF(custom_fields, ''), %s) WHERE id = %d",
						$custom_fields_json, $existing
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
				'source' => 'import',
				'custom_fields' => $custom_fields_json
			);

			$wpdb->insert($table, $data);
			$subscriber_id = $wpdb->insert_id;

			if ($list_id && $subscriber_id) {
				$wpdb->insert($junction, array(
					'list_id' => $list_id,
					'subscriber_id' => $subscriber_id
				));
			}

			if ($subscriber_id && !empty($tag_ids)) {
				foreach ($tag_ids as $tid) {
					$wpdb->insert($sub_tags_table, array(
						'tag_id' => $tid,
						'subscriber_id' => $subscriber_id
					));
					do_action('bomb_bag_subscriber_tag_added', $subscriber_id, $tid);
				}
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

	/**
	 * Sync WordPress users to subscribers.
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function sync_wp_users( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		$list_id = absint($request->get_param('list_id'));
		$tag_ids = $request->get_param('tag_ids');
		if ( !is_array($tag_ids) ) {
			$tag_ids = array();
		}
		$tag_ids = array_unique(array_filter(array_map('absint', $tag_ids)));

		$wp_users = get_users(array(
			'fields' => array('ID', 'user_email', 'first_name', 'last_name', 'display_name')
		));

		$imported = 0;
		$skipped = 0;

		foreach ($wp_users as $user) {
			$email = sanitize_email($user->user_email);
			if (!is_email($email)) {
				$skipped++;
				continue;
			}

			// Parse first/last name
			$first_name = $user->first_name;
			$last_name = $user->last_name;
			
			if (empty($first_name) && !empty($user->display_name)) {
				$parts = explode(' ', $user->display_name, 2);
				$first_name = $parts[0] ?? '';
				$last_name = $parts[1] ?? '';
			}

			// Check for duplicate
			$existing = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM $table WHERE email = %s", $email
			));

			if ($existing) {
				// Update names if missing
				$wpdb->query($wpdb->prepare(
					"UPDATE $table SET first_name = COALESCE(NULLIF(first_name, ''), %s), last_name = COALESCE(NULLIF(last_name, ''), %s) WHERE id = %d",
					$first_name, $last_name, $existing
				));

				if ($list_id) {
					$wpdb->query($wpdb->prepare(
						"INSERT IGNORE INTO $junction (list_id, subscriber_id) VALUES (%d, %d)",
						$list_id, $existing
					));
				}
				foreach ($tag_ids as $tid) {
					$wpdb->query($wpdb->prepare(
						"INSERT IGNORE INTO $sub_tags_table (tag_id, subscriber_id) VALUES (%d, %d)",
						$tid, $existing
					));
				}
				$skipped++; // Treat as skipped from creation, though updated/linked
				continue;
			}

			$data = array(
				'email' => $email,
				'first_name' => sanitize_text_field($first_name),
				'last_name' => sanitize_text_field($last_name),
				'status' => 'active',
				'source' => 'wp_sync'
			);

			$wpdb->insert($table, $data);
			$subscriber_id = $wpdb->insert_id;

			if ($list_id && $subscriber_id) {
				$wpdb->insert($junction, array(
					'list_id' => $list_id,
					'subscriber_id' => $subscriber_id
				));
			}

			if ($subscriber_id && !empty($tag_ids)) {
				foreach ($tag_ids as $tid) {
					$wpdb->insert($sub_tags_table, array(
						'tag_id' => $tid,
						'subscriber_id' => $subscriber_id
					));
					do_action('bomb_bag_subscriber_tag_added', $subscriber_id, $tid);
				}
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

	/**
	 * Export subscribers as CSV
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function export_subscribers( $request ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_subscribers';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$list_id = $request->get_param('list_id');
		$status = $request->get_param('status');

		$where = array('1=1');
		$params = array();

		if ($status) {
			$where[] = 's.status = %s';
			$params[] = $status;
		}

		$list_join = '';
		if ($list_id) {
			$list_join = "INNER JOIN $junction ls ON s.id = ls.subscriber_id";
			$where[] = 'ls.list_id = %d';
			$params[] = $list_id;
		}

		$where_sql = implode(' AND ', $where);
		$sql = "SELECT DISTINCT s.email, s.first_name, s.last_name, s.status, s.score, s.lead_status, s.subscribed_at 
		        FROM $table s $list_join WHERE $where_sql ORDER BY s.subscribed_at DESC";

		if (!empty($params)) {
			$sql = $wpdb->prepare($sql, $params);
		}

		$subscribers = $wpdb->get_results($sql, ARRAY_A);

		// Generate CSV
		$filename = 'bomb_bag_subscribers_' . date('Y-m-d') . '.csv';
		
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Pragma: no-cache');
		header('Expires: 0');

		$output = fopen('php://output', 'w');
		
		// Headers
		fputcsv($output, array('Email', 'First Name', 'Last Name', 'Status', 'Score', 'Lead Status', 'Subscribed At'));
		
		// Data
		foreach ($subscribers as $row) {
			fputcsv($output, $row);
		}
		
		fclose($output);
		exit;
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
		$lists_table = $wpdb->prefix . 'bomb_bag_lists';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';

		$sql = "SELECT l.*,
			COUNT(DISTINCT ls.subscriber_id) as subscriber_count,
			COUNT(DISTINCT CASE WHEN s.status = 'active' THEN ls.subscriber_id END) as active_count,
			COUNT(DISTINCT CASE WHEN s.status = 'unsubscribed' THEN ls.subscriber_id END) as unsubscribed_count,
			COUNT(DISTINCT CASE WHEN s.status IN ('bounced', 'complained') THEN ls.subscriber_id END) as bounced_count
		FROM $lists_table l
		LEFT JOIN $junction ls ON l.id = ls.list_id
		LEFT JOIN $subscribers_table s ON ls.subscriber_id = s.id
		GROUP BY l.id
		ORDER BY l.name ASC";

		$lists = $wpdb->get_results($sql);
		foreach ($lists as &$list) {
			$list->id = (int)$list->id;
			$list->subscriber_count = (int)$list->subscriber_count;
			$list->active_count = (int)$list->active_count;
			$list->unsubscribed_count = (int)$list->unsubscribed_count;
			$list->bounced_count = (int)$list->bounced_count;
			$list->is_suppression = !empty($list->is_suppression) && ((int)$list->is_suppression === 1);
		}
		unset($list);

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
			'description' => sanitize_textarea_field($request->get_param('description')),
			'is_suppression' => $request->get_param('is_suppression') ? 1 : 0
		);

		$result = $wpdb->insert($table, $data);

		if ($result === false) {
			return new WP_Error('create_failed', 'Failed to create list', array('status' => 500));
		}

		$data['id'] = $wpdb->insert_id;
		$data['subscriber_count'] = 0;
		$data['active_count'] = 0;
		$data['unsubscribed_count'] = 0;
		$data['bounced_count'] = 0;
		$data['is_suppression'] = (bool)$data['is_suppression'];
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
		if ($request->get_param('is_suppression') !== null) {
			$data['is_suppression'] = $request->get_param('is_suppression') ? 1 : 0;
		}

		$result = $wpdb->update($table, $data, array('id' => $id));

		if ($result === false) {
			return new WP_Error('update_failed', 'Failed to update list', array('status' => 500));
		}

		return rest_ensure_response(array('success' => true, 'id' => $id));
	}

	/**
	 * Merge source list into a target list.
	 *
	 * @since    1.1.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response|WP_Error
	 */
	public function merge_list( $request ) {
		global $wpdb;
		$lists_table = $wpdb->prefix . 'bomb_bag_lists';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$source_id = absint($request->get_param('id'));
		$target_id = absint($request->get_param('target_list_id'));
		$delete_source = (bool)$request->get_param('delete_source');

		if (!$source_id || !$target_id || $source_id === $target_id) {
			return new WP_Error('invalid_merge', 'Invalid source or target list ID', array('status' => 400));
		}

		$source_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $lists_table WHERE id = %d", $source_id));
		$target_exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $lists_table WHERE id = %d", $target_id));

		if (!$source_exists || !$target_exists) {
			return new WP_Error('not_found', 'Source or Target list not found', array('status' => 404));
		}

		// Move/Copy subscribers from source list into target list
		$wpdb->query($wpdb->prepare(
			"INSERT IGNORE INTO $junction (list_id, subscriber_id)
			 SELECT %d, subscriber_id FROM $junction WHERE list_id = %d",
			$target_id, $source_id
		));

		// Remove subscribers from source list
		$wpdb->delete($junction, array('list_id' => $source_id));

		if ($delete_source) {
			$wpdb->delete($lists_table, array('id' => $source_id));
		} else {
			$this->update_list_count($source_id);
		}

		$this->update_list_count($target_id);

		return rest_ensure_response(array(
			'success' => true,
			'message' => 'Lists successfully merged.'
		));
	}

	/**
	 * Duplicate a list with option to copy subscribers.
	 *
	 * @since    1.1.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response|WP_Error
	 */
	public function duplicate_list( $request ) {
		global $wpdb;
		$lists_table = $wpdb->prefix . 'bomb_bag_lists';
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$source_id = absint($request->get_param('id'));
		$source = $wpdb->get_row($wpdb->prepare("SELECT * FROM $lists_table WHERE id = %d", $source_id));

		if (!$source) {
			return new WP_Error('not_found', 'Source list not found', array('status' => 404));
		}

		$new_name = sanitize_text_field($request->get_param('name'));
		if (empty($new_name)) {
			$new_name = $source->name . ' (Copy)';
		}

		$include_members = (bool)$request->get_param('include_members');

		$wpdb->insert($lists_table, array(
			'name' => $new_name,
			'description' => $source->description,
			'is_suppression' => !empty($source->is_suppression) ? 1 : 0,
			'subscriber_count' => 0
		));
		$new_list_id = $wpdb->insert_id;

		if ($include_members && $new_list_id) {
			$wpdb->query($wpdb->prepare(
				"INSERT INTO $junction (list_id, subscriber_id)
				 SELECT %d, subscriber_id FROM $junction WHERE list_id = %d",
				$new_list_id, $source_id
			));
			$this->update_list_count($new_list_id);
		}

		$new_list = $wpdb->get_row($wpdb->prepare("SELECT * FROM $lists_table WHERE id = %d", $new_list_id));
		$new_list->id = (int)$new_list->id;
		$new_list->subscriber_count = (int)$new_list->subscriber_count;
		$new_list->active_count = $include_members ? (int)$new_list->subscriber_count : 0;
		$new_list->unsubscribed_count = 0;
		$new_list->bounced_count = 0;
		$new_list->is_suppression = !empty($new_list->is_suppression) && ((int)$new_list->is_suppression === 1);

		return rest_ensure_response($new_list);
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

	/**
	 * Scrub list (remove bounced/complained/unsubscribed)
	 *
	 * @since    1.0.0
	 * @param    WP_REST_Request $request
	 * @return   WP_REST_Response
	 */
	public function scrub_list( $request ) {
		global $wpdb;
		$junction = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$subscribers = $wpdb->prefix . 'bomb_bag_subscribers';
		$list_id = $request->get_param('id');

		// Remove subscribers from the list if their status is bounced, complained, or unsubscribed
		$sql = $wpdb->prepare(
			"DELETE ls FROM $junction ls 
			 INNER JOIN $subscribers s ON ls.subscriber_id = s.id 
			 WHERE ls.list_id = %d AND s.status IN ('bounced', 'complained', 'unsubscribed')",
			$list_id
		);
		$wpdb->query($sql);
		$removed_count = $wpdb->rows_affected;

		$this->update_list_count($list_id);

		return rest_ensure_response(array(
			'success' => true,
			'removed_count' => $removed_count,
			'message' => "Successfully scrubbed $removed_count inactive leads from the list."
		));
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
