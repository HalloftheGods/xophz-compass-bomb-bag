<?php

/**
 * Bomb Bag Email Handler
 *
 * @link       http://www.mycompassconsulting.com/
 * @since      1.0.0
 *
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/includes
 */

/**
 * Handles email sending and tracking for campaigns.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Bomb_Bag
 * @subpackage Xophz_Compass_Bomb_Bag/includes
 * @author     Xoph <xoph@midnightnerd.com>
 */
class Xophz_Compass_Bomb_Bag_Email_Handler {

	/**
	 * Queue a campaign for sending.
	 *
	 * @since    1.0.0
	 * @param    int $campaign_id
	 * @return   bool|WP_Error
	 */
	public function queue_campaign( $campaign_id ) {
		global $wpdb;
		
		$campaigns_table = $wpdb->prefix . 'bomb_bag_campaigns';
		$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';
		$list_subs_table = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';

		// Get campaign
		$campaign = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $campaigns_table WHERE id = %d", $campaign_id
		));

		if (!$campaign) {
			return new WP_Error('not_found', 'Campaign not found');
		}

		if ($campaign->status === 'sent') {
			return new WP_Error('already_sent', 'Campaign has already been sent');
		}

		// Get subscribers from the campaign's list
		$subscribers = $wpdb->get_results($wpdb->prepare(
			"SELECT s.* FROM $subscribers_table s
			 INNER JOIN $list_subs_table ls ON s.id = ls.subscriber_id
			 WHERE ls.list_id = %d AND s.status = 'active'",
			$campaign->list_id
		));

		if (empty($subscribers)) {
			return new WP_Error('no_subscribers', 'No active subscribers in this list');
		}

		// Update campaign status
		$wpdb->update($campaigns_table, array(
			'status' => 'sending',
			'total_recipients' => count($subscribers)
		), array('id' => $campaign_id));

		$variants_table = $wpdb->prefix . 'bomb_bag_campaign_variants';
		$variants = $wpdb->get_results($wpdb->prepare(
			"SELECT id, weight_percentage FROM $variants_table WHERE campaign_id = %d",
			$campaign_id
		));

		// Normalize weights into a distribution array
		$variant_pool = array();
		if (!empty($variants)) {
			foreach ($variants as $v) {
				$weight = max(1, (int)$v->weight_percentage);
				for ($i = 0; $i < $weight; $i++) {
					$variant_pool[] = $v->id;
				}
			}
			shuffle($variant_pool);
		}

		// Queue emails for each subscriber
		$pool_index = 0;
		$pool_size = count($variant_pool);

		foreach ($subscribers as $subscriber) {
			$tracking_id = $this->generate_tracking_id();
			
			$variant_id = null;
			if ($pool_size > 0) {
				$variant_id = $variant_pool[$pool_index % $pool_size];
				$pool_index++;
			}
			
			$wpdb->insert($emails_table, array(
				'campaign_id' => $campaign_id,
				'variant_id' => $variant_id,
				'subscriber_id' => $subscriber->id,
				'status' => 'queued',
				'tracking_id' => $tracking_id
			));
		}

		// Schedule the actual sending via WP Cron
		if (!wp_next_scheduled('bomb_bag_send_emails', array($campaign_id))) {
			wp_schedule_single_event(time(), 'bomb_bag_send_emails', array($campaign_id));
		}

		return true;
	}

	/**
	 * Process sending for a campaign.
	 *
	 * @since    1.0.0
	 * @param    int $campaign_id
	 */
	public function process_campaign_emails( $campaign_id ) {
		global $wpdb;
		
		$campaigns_table = $wpdb->prefix . 'bomb_bag_campaigns';
		$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';

		$settings = get_option('bomb_bag_settings', array());
		$batch_size = $settings['batch_size'] ?? 50;
		$batch_delay = $settings['batch_delay'] ?? 1;

		// Get campaign
		$campaign = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $campaigns_table WHERE id = %d", $campaign_id
		));

		if (!$campaign || $campaign->status === 'sent') {
			return;
		}

		$variants_table = $wpdb->prefix . 'bomb_bag_campaign_variants';
		$variants = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $variants_table WHERE campaign_id = %d",
			$campaign_id
		), OBJECT_K);

		// Get queued emails for this campaign
		$emails = $wpdb->get_results($wpdb->prepare(
			"SELECT e.*, s.email, s.first_name, s.last_name 
			 FROM $emails_table e
			 INNER JOIN $subscribers_table s ON e.subscriber_id = s.id
			 WHERE e.campaign_id = %d AND e.status = 'queued'
			 LIMIT %d",
			$campaign_id, $batch_size
		));

		if (empty($emails)) {
			// All done, mark campaign as sent
			$wpdb->update($campaigns_table, array(
				'status' => 'sent',
				'sent_at' => current_time('mysql')
			), array('id' => $campaign_id));
			
			$this->update_campaign_stats($campaign_id);
			return;
		}

		$from_name = $campaign->from_name ?: ($settings['from_name'] ?? get_bloginfo('name'));
		$from_email = $campaign->from_email ?: ($settings['from_email'] ?? get_option('admin_email'));

		foreach ($emails as $email) {
			$email_subject = $campaign->subject;
			$email_content = $campaign->content;
			
			if (!empty($email->variant_id) && isset($variants[$email->variant_id])) {
				$email_subject = $variants[$email->variant_id]->subject;
				$email_content = $variants[$email->variant_id]->content;
			}

			$email_content = self::apply_template($email_content, $campaign->template_id);

			$content = $this->personalize_content($email_content, $email);
			$content = $this->add_tracking($content, $email->tracking_id, $campaign_id, $email->variant_id);
			
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . $from_name . ' <' . $from_email . '>',
				'List-Unsubscribe: <' . $this->get_unsubscribe_url($email->tracking_id) . '>'
			);

			$sent = Xophz_Compass_Bomb_Bag_Email_Providers::send($email->email, $email_subject, $content, $headers);

			if ($sent) {
				$wpdb->update($emails_table, array(
					'status' => 'sent',
					'sent_at' => current_time('mysql')
				), array('id' => $email->id));
			} else {
				$wpdb->update($emails_table, array(
					'status' => 'failed',
					'error_message' => 'wp_mail returned false'
				), array('id' => $email->id));
			}
		}

		// Update sent count
		$this->update_campaign_stats($campaign_id);

		// Schedule next batch if more emails remain
		$remaining = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $emails_table WHERE campaign_id = %d AND status = 'queued'",
			$campaign_id
		));

		if ($remaining > 0) {
			wp_schedule_single_event(time() + $batch_delay, 'bomb_bag_send_emails', array($campaign_id));
		} else {
			// Mark as sent
			$wpdb->update($campaigns_table, array(
				'status' => 'sent',
				'sent_at' => current_time('mysql')
			), array('id' => $campaign_id));
		}
	}

	/**
	 * Track an email open event.
	 *
	 * @since    1.0.0
	 * @param    string $tracking_id
	 */
	public function track_open( $tracking_id ) {
		global $wpdb;
		
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';
		$analytics_table = $wpdb->prefix . 'bomb_bag_analytics';

		$email = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $emails_table WHERE tracking_id = %s", $tracking_id
		));

		if (!$email) {
			return;
		}

		// Update first open time
		if (!$email->opened_at) {
			$wpdb->update($emails_table, array(
				'opened_at' => current_time('mysql')
			), array('id' => $email->id));
		}

		// Log analytics event
		$wpdb->insert($analytics_table, array(
			'email_id' => $email->id,
			'event_type' => 'open',
			'ip_address' => $this->get_client_ip(),
			'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
		));

		// Update campaign stats
		$this->update_campaign_stats($email->campaign_id);

		// Increment Lead Score (+10 for opens)
		$this->increment_subscriber_score($email->subscriber_id, 10);
	}

	/**
	 * Track a link click event.
	 *
	 * @since    1.0.0
	 * @param    string $tracking_id
	 * @param    string $url
	 */
	public function track_click( $tracking_id, $url ) {
		global $wpdb;
		
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';
		$analytics_table = $wpdb->prefix . 'bomb_bag_analytics';

		$email = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $emails_table WHERE tracking_id = %s", $tracking_id
		));

		if (!$email) {
			return;
		}

		// Update first click time
		if (!$email->clicked_at) {
			$wpdb->update($emails_table, array(
				'clicked_at' => current_time('mysql')
			), array('id' => $email->id));
		}

		// Log analytics event
		$wpdb->insert($analytics_table, array(
			'email_id' => $email->id,
			'event_type' => 'click',
			'event_data' => wp_json_encode(array('url' => $url)),
			'ip_address' => $this->get_client_ip(),
			'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
		));

		// Update campaign stats
		$this->update_campaign_stats($email->campaign_id);

		// Increment Lead Score (+20 for clicks)
		$this->increment_subscriber_score($email->subscriber_id, 20);
	}

	/**
	 * Handle unsubscribe.
	 *
	 * @since    1.0.0
	 * @param    string $tracking_id
	 */
	public function handle_unsubscribe( $tracking_id ) {
		global $wpdb;
		
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';
		$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';
		$analytics_table = $wpdb->prefix . 'bomb_bag_analytics';

		$email = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $emails_table WHERE tracking_id = %s", $tracking_id
		));

		if (!$email) {
			return false;
		}

		// Update subscriber status
		$wpdb->update($subscribers_table, array(
			'status' => 'unsubscribed',
			'unsubscribed_at' => current_time('mysql')
		), array('id' => $email->subscriber_id));

		// Log analytics event
		$wpdb->insert($analytics_table, array(
			'email_id' => $email->id,
			'event_type' => 'unsubscribe',
			'ip_address' => $this->get_client_ip(),
			'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
		));

		return true;
	}

	/**
	 * Increment a subscriber's lead score and update status.
	 *
	 * @since    1.0.0
	 * @param    int $subscriber_id
	 * @param    int $points
	 */
	private function increment_subscriber_score( $subscriber_id, $points ) {
		global $wpdb;
		$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';

		// Ensure the columns exist before updating
		$has_score = $wpdb->get_results( "SHOW COLUMNS FROM `$subscribers_table` LIKE 'score'" );
		if ( empty( $has_score ) ) {
			return; // columns don't exist yet, dbDelta hasn't run
		}

		$subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT score FROM $subscribers_table WHERE id = %d", $subscriber_id ) );
		if ( ! $subscriber ) return;

		$new_score = intval( $subscriber->score ) + intval( $points );
		
		$new_status = 'cold';
		if ( $new_score >= 100 ) {
			$new_status = 'hot';
		} elseif ( $new_score >= 50 ) {
			$new_status = 'warm';
		}

		$wpdb->update( $subscribers_table, array(
			'score'       => $new_score,
			'lead_status' => $new_status
		), array( 'id' => $subscriber_id ) );
	}

	/**
	 * Generate a unique tracking ID.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function generate_tracking_id() {
		return bin2hex(random_bytes(32));
	}

	/**
	 * Personalize email content with subscriber data.
	 *
	 * @since    1.0.0
	 * @param    string $content
	 * @param    object $email
	 * @return   string
	 */
	private function personalize_content( $content, $email ) {
		$replacements = array(
			'{{first_name}}' => $email->first_name ?: 'Subscriber',
			'{{last_name}}' => $email->last_name ?: '',
			'{{email}}' => $email->email,
			'{{unsubscribe_url}}' => $this->get_unsubscribe_url($email->tracking_id)
		);

		return str_replace(array_keys($replacements), array_values($replacements), $content);
	}

	/**
	 * Add tracking pixel and link tracking to content.
	 *
	 * @since    1.0.0
	 * @param    string $content
	 * @param    string $tracking_id
	 * @param    int|null $campaign_id
	 * @param    int|null $variant_id
	 * @return   string
	 */
	private function add_tracking( $content, $tracking_id, $campaign_id = null, $variant_id = null ) {
		$tracking_url = add_query_arg(array(
			'px_track' => 'open',
			't_id' => $tracking_id
		), rest_url('xophz-compass/v1/bomb-bag/track'));

		// Add tracking pixel before closing body tag
		$pixel = '<img src="' . esc_url($tracking_url) . '" width="1" height="1" style="display:none;" alt="" />';
		
		if (strpos($content, '</body>') !== false) {
			$content = str_replace('</body>', $pixel . '</body>', $content);
		} else {
			$content .= $pixel;
		}

		// Rewrite links for click tracking
		$content = preg_replace_callback(
			'/<a\s+([^>]*href=["\'])([^"\']+)(["\'][^>]*)>/i',
			function($matches) use ($tracking_id, $campaign_id, $variant_id) {
				$url = $matches[2];
				// Don't track unsubscribe links
				if (strpos($url, 'unsubscribe') !== false) {
					return $matches[0];
				}
				$tracked_url = add_query_arg(array(
					'px_track' => 'click',
					't_id' => $tracking_id,
					'url' => urlencode($url)
				), rest_url('xophz-compass/v1/bomb-bag/track'));
				
				// Append UTM params for Silver Arrow integration
				if ($campaign_id) {
					$tracked_url = add_query_arg('utm_campaign', 'bombbag_' . $campaign_id, $tracked_url);
					$tracked_url = add_query_arg('sa_test', $campaign_id, $tracked_url);
					if ($variant_id) {
						$tracked_url = add_query_arg('sa_variant', $variant_id, $tracked_url);
					}
				}
				
				return '<a ' . $matches[1] . esc_url($tracked_url) . $matches[3] . '>';
			},
			$content
		);

		return $content;
	}

	/**
	 * Get unsubscribe URL.
	 *
	 * @since    1.0.0
	 * @param    string $tracking_id
	 * @return   string
	 */
	private function get_unsubscribe_url( $tracking_id ) {
		return add_query_arg(array(
			'px_track' => 'unsubscribe',
			't_id' => $tracking_id
		), rest_url('xophz-compass/v1/bomb-bag/track'));
	}

	/**
	 * Get client IP address.
	 *
	 * @since    1.0.0
	 * @return   string
	 */
	private function get_client_ip() {
		$ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR');
		
		foreach ($ip_keys as $key) {
			if (!empty($_SERVER[$key])) {
				$ip = $_SERVER[$key];
				if (strpos($ip, ',') !== false) {
					$ip = explode(',', $ip)[0];
				}
				return sanitize_text_field(trim($ip));
			}
		}
		
		return '';
	}

	/**
	 * Update campaign statistics.
	 *
	 * @since    1.0.0
	 * @param    int $campaign_id
	 */
	private function update_campaign_stats( $campaign_id ) {
		global $wpdb;
		
		$campaigns_table = $wpdb->prefix . 'bomb_bag_campaigns';
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';

		$stats = $wpdb->get_row($wpdb->prepare(
			"SELECT 
				COUNT(CASE WHEN status = 'sent' THEN 1 END) as total_sent,
				COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) as total_opened,
				COUNT(CASE WHEN clicked_at IS NOT NULL THEN 1 END) as total_clicked
			 FROM $emails_table WHERE campaign_id = %d",
			$campaign_id
		));

		$wpdb->update($campaigns_table, array(
			'total_sent' => $stats->total_sent,
			'total_opened' => $stats->total_opened,
			'total_clicked' => $stats->total_clicked
		), array('id' => $campaign_id));
	}

	public function process_drip_emails() {
		global $wpdb;

		$enroll_table = $wpdb->prefix . 'bomb_bag_drip_enrollments';
		$step_table   = $wpdb->prefix . 'bomb_bag_drip_steps';
		$seq_table    = $wpdb->prefix . 'bomb_bag_drip_sequences';
		$sub_table    = $wpdb->prefix . 'bomb_bag_subscribers';
		$emails_table = $wpdb->prefix . 'bomb_bag_emails';

		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$enroll_table}'" ) === $enroll_table;
		if ( ! $table_exists ) {
			return;
		}

		$col_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$enroll_table} LIKE 'next_send_at'" );
		if ( empty( $col_exists ) ) {
			return;
		}

		$due_enrollments = $wpdb->get_results( $wpdb->prepare(
			"SELECT e.*, s.email, s.first_name, s.last_name
			 FROM $enroll_table e
			 INNER JOIN $sub_table s ON e.subscriber_id = s.id
			 WHERE e.status = 'active' AND e.next_send_at <= %s
			 LIMIT 100",
			current_time( 'mysql' )
		));

		foreach ( $due_enrollments as $enrollment ) {
			$step = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $step_table WHERE sequence_id = %d ORDER BY position ASC LIMIT 1 OFFSET %d",
				$enrollment->sequence_id,
				$enrollment->current_step
			));

			if ( ! $step ) {
				$wpdb->update( $enroll_table, array(
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
				), array( 'id' => $enrollment->id ) );

				$wpdb->query( $wpdb->prepare(
					"UPDATE $seq_table SET total_completed = total_completed + 1 WHERE id = %d",
					$enrollment->sequence_id
				));
				continue;
			}

			$sequence = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $seq_table WHERE id = %d", $enrollment->sequence_id
			));

			$settings   = get_option( 'bomb_bag_settings', array() );
			$from_name  = $sequence->from_name ?: ( $settings['from_name'] ?? get_bloginfo( 'name' ) );
			$from_email = $sequence->from_email ?: ( $settings['from_email'] ?? get_option( 'admin_email' ) );

			$tracking_id = $this->generate_tracking_id();

			$content = $this->personalize_content( $step->content, $enrollment );
			$content = $this->add_tracking( $content, $tracking_id );

			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . $from_name . ' <' . $from_email . '>',
				'List-Unsubscribe: <' . $this->get_unsubscribe_url( $tracking_id ) . '>'
			);

			$sent = Xophz_Compass_Bomb_Bag_Email_Providers::send(
				$enrollment->email, $step->subject, $content, $headers
			);

			$wpdb->insert( $emails_table, array(
				'drip_step_id'  => $step->id,
				'subscriber_id' => $enrollment->subscriber_id,
				'status'        => $sent ? 'sent' : 'failed',
				'tracking_id'   => $tracking_id,
				'sent_at'       => $sent ? current_time( 'mysql' ) : null,
				'error_message' => $sent ? null : 'Email send failed',
			));

			$next_step = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $step_table WHERE sequence_id = %d ORDER BY position ASC LIMIT 1 OFFSET %d",
				$enrollment->sequence_id,
				$enrollment->current_step + 1
			));

			$has_next_step = !! $next_step;

			if ( $has_next_step ) {
				$delay_seconds = $next_step->delay_days * 86400 + $next_step->delay_hours * 3600;
				$next_send     = date( 'Y-m-d H:i:s', time() + $delay_seconds );

				$wpdb->update( $enroll_table, array(
					'current_step' => $enrollment->current_step + 1,
					'next_send_at' => $next_send,
				), array( 'id' => $enrollment->id ) );
			} else {
				$wpdb->update( $enroll_table, array(
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
					'next_send_at' => null,
				), array( 'id' => $enrollment->id ) );

				$wpdb->query( $wpdb->prepare(
					"UPDATE $seq_table SET total_completed = total_completed + 1 WHERE id = %d",
					$enrollment->sequence_id
				));
			}
		}
	}

	/**
	 * Process node-based Journey emails and automation steps.
	 *
	 * @since    1.0.0
	 */
	public function process_journey_enrollments() {
		global $wpdb;

		$enroll_table  = $wpdb->prefix . 'bomb_bag_journey_enrollments';
		$journey_table = $wpdb->prefix . 'bomb_bag_journeys';
		$sub_table     = $wpdb->prefix . 'bomb_bag_subscribers';
		$emails_table  = $wpdb->prefix . 'bomb_bag_emails';
		$sub_tags_tbl  = $wpdb->prefix . 'bomb_bag_subscriber_tags';
		$list_sub_tbl  = $wpdb->prefix . 'bomb_bag_list_subscribers';

		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$enroll_table}'" ) === $enroll_table;
		if ( ! $table_exists ) {
			return;
		}

		$has_eval_col = ! empty( $wpdb->get_results( "SHOW COLUMNS FROM {$enroll_table} LIKE 'next_evaluation_at'" ) );
		$eval_col = $has_eval_col ? 'next_evaluation_at' : 'next_send_at';

		$has_node_id_col = ! empty( $wpdb->get_results( "SHOW COLUMNS FROM {$enroll_table} LIKE 'current_node_id'" ) );
		$node_col = $has_node_id_col ? 'current_node_id' : 'current_node';

		$due_enrollments = $wpdb->get_results( $wpdb->prepare(
			"SELECT e.*, s.email, s.first_name, s.last_name, s.status as subscriber_status, s.custom_fields
			 FROM $enroll_table e
			 INNER JOIN $sub_table s ON e.subscriber_id = s.id
			 WHERE e.status = 'active' AND (e.{$eval_col} <= %s OR e.{$eval_col} IS NULL)
			 LIMIT 100",
			current_time( 'mysql' )
		));

		if ( empty( $due_enrollments ) ) {
			return;
		}

		$settings   = get_option( 'bomb_bag_settings', array() );
		$default_from_name  = $settings['from_name'] ?? get_bloginfo( 'name' );
		$default_from_email = $settings['from_email'] ?? get_option( 'admin_email' );

		foreach ( $due_enrollments as $enrollment ) {
			$journey = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM $journey_table WHERE id = %d AND status = 'active'", 
				$enrollment->journey_id
			));

			if ( ! $journey ) {
				continue;
			}

			$nodes = json_decode( $journey->nodes_json, true );
			$edges = json_decode( $journey->edges_json, true );

			if ( ! is_array( $nodes ) || ! is_array( $edges ) ) {
				continue;
			}

			$current_node_id = isset( $enrollment->{$node_col} ) ? $enrollment->{$node_col} : $enrollment->current_node_id;
			$current_node = null;

			foreach ( $nodes as $node ) {
				if ( isset( $node['id'] ) && $node['id'] === $current_node_id ) {
					$current_node = $node;
					break;
				}
			}

			if ( ! $current_node ) {
				// If no node found, end journey
				$wpdb->update( $enroll_table, array(
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
					$eval_col      => null,
				), array( 'id' => $enrollment->id ) );

				$wpdb->query( $wpdb->prepare(
					"UPDATE $journey_table SET total_completed = total_completed + 1 WHERE id = %d",
					$enrollment->journey_id
				));
				continue;
			}

			$delay_seconds = 0;
			$next_node_id = null;
			$next_nodes = array();
			
			// Find outgoing connecting edges
			foreach ( $edges as $edge ) {
				if ( isset( $edge['source'] ) && $edge['source'] === $current_node_id ) {
					$next_nodes[] = $edge;
				}
			}

			$node_type = $current_node['type'] ?? '';
			$node_data = $current_node['data'] ?? array();

			// 1. TRIGGER NODES
			if ( strpos( $node_type, 'trigger' ) === 0 || $node_type === 'trigger' ) {
				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 2. ACTION: SEND EMAIL
			elseif ( $node_type === 'action_email' || $node_type === 'email' ) {
				$from_name  = ! empty( $node_data['from_name'] ) ? $node_data['from_name'] : $default_from_name;
				$from_email = ! empty( $node_data['from_email'] ) ? $node_data['from_email'] : $default_from_email;

				$tracking_id = $this->generate_tracking_id();
				$subject     = ! empty( $node_data['subject'] ) ? $node_data['subject'] : 'Update from ' . get_bloginfo( 'name' );
				$content     = ! empty( $node_data['content'] ) ? $node_data['content'] : '<p>' . esc_html( $subject ) . '</p>';

				// Apply Layout Wrapper Template
				$template_id = isset( $node_data['template_id'] ) ? $node_data['template_id'] : null;
				if ( $template_id !== null && $template_id !== '' ) {
					$content = self::apply_template( $content, $template_id );
				}

				$content = $this->personalize_content( $content, $enrollment );
				$content = $this->add_tracking( $content, $tracking_id );

				$headers = array(
					'Content-Type: text/html; charset=UTF-8',
					'From: ' . $from_name . ' <' . $from_email . '>',
					'List-Unsubscribe: <' . $this->get_unsubscribe_url( $tracking_id ) . '>'
				);

				$sent = Xophz_Compass_Bomb_Bag_Email_Providers::send(
					$enrollment->email, $subject, $content, $headers
				);

				$wpdb->insert( $emails_table, array(
					'journey_node_id' => $current_node_id,
					'subscriber_id'   => $enrollment->subscriber_id,
					'status'          => $sent ? 'sent' : 'failed',
					'tracking_id'     => $tracking_id,
					'sent_at'         => $sent ? current_time( 'mysql' ) : null,
					'error_message'   => $sent ? null : 'Email send failed',
				));

				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 3. ACTION: ADD TAG
			elseif ( $node_type === 'action_add_tag' || $node_type === 'add_tag' ) {
				$tag_id = isset( $node_data['tag_id'] ) ? (int) $node_data['tag_id'] : 0;
				if ( $tag_id > 0 ) {
					$has_tag = $wpdb->get_var( $wpdb->prepare(
						"SELECT id FROM $sub_tags_tbl WHERE subscriber_id = %d AND tag_id = %d",
						$enrollment->subscriber_id, $tag_id
					) );
					if ( ! $has_tag ) {
						$wpdb->insert( $sub_tags_tbl, array(
							'subscriber_id' => $enrollment->subscriber_id,
							'tag_id'        => $tag_id
						) );
					}
				}
				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 4. ACTION: REMOVE TAG
			elseif ( $node_type === 'action_remove_tag' || $node_type === 'remove_tag' ) {
				$tag_id = isset( $node_data['tag_id'] ) ? (int) $node_data['tag_id'] : 0;
				if ( $tag_id > 0 ) {
					$wpdb->delete( $sub_tags_tbl, array(
						'subscriber_id' => $enrollment->subscriber_id,
						'tag_id'        => $tag_id
					) );
				}
				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 5. ACTION: UPDATE CUSTOM FIELD
			elseif ( $node_type === 'action_update_field' || $node_type === 'update_field' ) {
				$field_name  = sanitize_key( $node_data['field_name'] ?? '' );
				$field_value = sanitize_text_field( $node_data['field_value'] ?? '' );

				if ( ! empty( $field_name ) ) {
					if ( in_array( $field_name, array( 'lead_status', 'score', 'source' ), true ) ) {
						$wpdb->update( $sub_table, array( $field_name => $field_value ), array( 'id' => $enrollment->subscriber_id ) );
					} else {
						$custom = json_decode( $enrollment->custom_fields, true ) ?: array();
						$custom[ $field_name ] = $field_value;
						$wpdb->update( $sub_table, array( 'custom_fields' => wp_json_encode( $custom ) ), array( 'id' => $enrollment->subscriber_id ) );
					}
				}
				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 6. ACTION: UNSUBSCRIBE
			elseif ( $node_type === 'action_unsubscribe' || $node_type === 'unsubscribe' ) {
				$scope = $node_data['unsubscribe_scope'] ?? 'all';
				if ( $scope === 'list' && ! empty( $node_data['list_id'] ) ) {
					$wpdb->delete( $list_sub_tbl, array(
						'subscriber_id' => $enrollment->subscriber_id,
						'list_id'       => (int) $node_data['list_id']
					) );
				} else {
					$wpdb->update( $sub_table, array(
						'status'          => 'unsubscribed',
						'unsubscribed_at' => current_time( 'mysql' )
					), array( 'id' => $enrollment->subscriber_id ) );
				}
				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 7. LOGIC: WAIT DELAY
			elseif ( $node_type === 'logic_wait' || $node_type === 'wait' ) {
				$days    = intval( $node_data['days'] ?? 0 );
				$hours   = intval( $node_data['hours'] ?? 0 );
				$minutes = intval( $node_data['minutes'] ?? 0 );

				$delay_seconds = ( $days * 86400 ) + ( $hours * 3600 ) + ( $minutes * 60 );
				
				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 8. LOGIC: IF / ELSE CONDITION
			elseif ( $node_type === 'logic_condition' || $node_type === 'condition' ) {
				$cond_type  = $node_data['condition_type'] ?? 'tag';
				$cond_val   = $node_data['condition_value'] ?? '';
				$condition_met = false;

				if ( $cond_type === 'tag' || $cond_type === 'has_tag' ) {
					$tag_id = (int) $cond_val;
					if ( $tag_id > 0 ) {
						$has_tag = $wpdb->get_var( $wpdb->prepare(
							"SELECT id FROM $sub_tags_tbl WHERE subscriber_id = %d AND tag_id = %d",
							$enrollment->subscriber_id, $tag_id
						) );
						$condition_met = ! empty( $has_tag );
					}
				} elseif ( $cond_type === 'list' || $cond_type === 'in_list' ) {
					$list_id = (int) $cond_val;
					if ( $list_id > 0 ) {
						$in_list = $wpdb->get_var( $wpdb->prepare(
							"SELECT list_id FROM $list_sub_tbl WHERE subscriber_id = %d AND list_id = %d",
							$enrollment->subscriber_id, $list_id
						) );
						$condition_met = ! empty( $in_list );
					}
				} elseif ( $cond_type === 'activity' || $cond_type === 'email_opened' ) {
					$opened = $wpdb->get_var( $wpdb->prepare(
						"SELECT id FROM $emails_table WHERE subscriber_id = %d AND opened_at IS NOT NULL",
						$enrollment->subscriber_id
					) );
					$condition_met = ! empty( $opened );
				} else {
					$condition_met = true;
				}

				foreach ( $next_nodes as $edge ) {
					$handle = $edge['sourceHandle'] ?? '';
					if ( $condition_met && ( $handle === 'true' || $handle === 'yes' ) ) {
						$next_node_id = $edge['target'];
						break;
					} elseif ( ! $condition_met && ( $handle === 'false' || $handle === 'no' ) ) {
						$next_node_id = $edge['target'];
						break;
					}
				}

				if ( ! $next_node_id && ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}
			// 9. LOGIC: A/B SPLIT
			elseif ( $node_type === 'logic_split' || $node_type === 'split' ) {
				$split_pct = isset( $node_data['split_percentage'] ) ? (int) $node_data['split_percentage'] : 50;
				$rand = wp_rand( 1, 100 );
				$is_path_a = ( $rand <= $split_pct );

				foreach ( $next_nodes as $edge ) {
					$handle = $edge['sourceHandle'] ?? '';
					if ( $is_path_a && ( $handle === 'path_a' || $handle === 'true' ) ) {
						$next_node_id = $edge['target'];
						break;
					} elseif ( ! $is_path_a && ( $handle === 'path_b' || $handle === 'false' ) ) {
						$next_node_id = $edge['target'];
						break;
					}
				}

				if ( ! $next_node_id && ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[ $is_path_a ? 0 : ( count( $next_nodes ) - 1 ) ]['target'];
				}
			} else {
				if ( ! empty( $next_nodes ) ) {
					$next_node_id = $next_nodes[0]['target'];
				}
			}

			// Advance to next node or finish
			if ( $next_node_id ) {
				$next_eval = date( 'Y-m-d H:i:s', time() + $delay_seconds );
				$update_data = array(
					$node_col => $next_node_id,
					$eval_col => $next_eval,
				);
				$wpdb->update( $enroll_table, $update_data, array( 'id' => $enrollment->id ) );
			} else {
				// End of Journey reached
				$wpdb->update( $enroll_table, array(
					'status'       => 'completed',
					'completed_at' => current_time( 'mysql' ),
					$eval_col      => null,
				), array( 'id' => $enrollment->id ) );

				$wpdb->query( $wpdb->prepare(
					"UPDATE $journey_table SET total_completed = total_completed + 1 WHERE id = %d",
					$enrollment->journey_id
				));
			}
		}
	}

	/**
	 * Enroll a subscriber in eligible active Journeys upon creation or list join.
	 *
	 * @since    1.0.0
	 * @param    int $subscriber_id
	 * @param    int $list_id
	 * @param    string $trigger_event ('subscribe', 'tag_added')
	 * @param    int $tag_id
	 */
	public function enroll_subscriber_in_journeys( $subscriber_id, $list_id = 0, $trigger_event = 'subscribe', $tag_id = 0 ) {
		global $wpdb;

		$journey_table = $wpdb->prefix . 'bomb_bag_journeys';
		$enroll_table  = $wpdb->prefix . 'bomb_bag_journey_enrollments';

		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$enroll_table}'" ) === $enroll_table;
		if ( ! $table_exists ) {
			return;
		}

		$has_eval_col = ! empty( $wpdb->get_results( "SHOW COLUMNS FROM {$enroll_table} LIKE 'next_evaluation_at'" ) );
		$eval_col = $has_eval_col ? 'next_evaluation_at' : 'next_send_at';

		$has_node_id_col = ! empty( $wpdb->get_results( "SHOW COLUMNS FROM {$enroll_table} LIKE 'current_node_id'" ) );
		$node_col = $has_node_id_col ? 'current_node_id' : 'current_node';

		$active_journeys = $wpdb->get_results( "SELECT * FROM $journey_table WHERE status = 'active'" );
		if ( empty( $active_journeys ) ) {
			return;
		}

		foreach ( $active_journeys as $journey ) {
			$nodes = json_decode( $journey->nodes_json, true );
			$edges = json_decode( $journey->edges_json, true ) ?: array();
			if ( ! is_array( $nodes ) ) continue;

			$trigger_node = null;
			foreach ( $nodes as $node ) {
				$t = $node['type'] ?? '';
				if ( strpos( $t, 'trigger' ) === 0 || $t === 'trigger' ) {
					$trigger_node = $node;
					break;
				}
			}

			if ( ! $trigger_node ) continue;

			$node_type = $trigger_node['type'] ?? '';
			$node_data = $trigger_node['data'] ?? array();

			// Verify trigger conditions
			if ( $trigger_event === 'subscribe' ) {
				if ( $node_type !== 'trigger_subscribe' && $node_type !== 'trigger' && $journey->trigger_type !== 'subscribe' && $journey->trigger_type !== 'list_subscription' ) {
					continue;
				}
				if ( ! empty( $node_data['list_id'] ) ) {
					$trigger_list_id = intval( $node_data['list_id'] );
					if ( $trigger_list_id !== 0 && $trigger_list_id !== intval( $list_id ) ) {
						continue;
					}
				}
			} elseif ( $trigger_event === 'tag_added' ) {
				if ( $node_type !== 'trigger_tag' && $journey->trigger_type !== 'tag_added' ) {
					continue;
				}
				if ( ! empty( $node_data['tag_id'] ) ) {
					$trigger_tag_id = intval( $node_data['tag_id'] );
					if ( $trigger_tag_id !== 0 && $trigger_tag_id !== intval( $tag_id ) ) {
						continue;
					}
				}
			}

			// Find first action/logic node connected to the trigger
			$first_step_id = $trigger_node['id'];
			foreach ( $edges as $edge ) {
				if ( isset( $edge['source'] ) && $edge['source'] === $trigger_node['id'] ) {
					$first_step_id = $edge['target'];
					break;
				}
			}

			// Check if already enrolled to avoid duplicates
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $enroll_table WHERE journey_id = %d AND subscriber_id = %d",
				$journey->id, $subscriber_id
			) );

			if ( ! $exists ) {
				$insert_data = array(
					'journey_id'    => $journey->id,
					'subscriber_id' => $subscriber_id,
					$node_col       => $first_step_id,
					'status'        => 'active',
					$eval_col       => current_time( 'mysql' )
				);
				$wpdb->insert( $enroll_table, $insert_data );
				$wpdb->query( $wpdb->prepare( "UPDATE $journey_table SET total_enrolled = total_enrolled + 1 WHERE id = %d", $journey->id ) );
			}
		}
	}

	/**
	 * Enroll a subscriber in tag-triggered journeys when a tag is assigned.
	 *
	 * @param int $subscriber_id
	 * @param int $tag_id
	 */
	public function enroll_subscriber_tag_journey( $subscriber_id, $tag_id ) {
		$this->enroll_subscriber_in_journeys( $subscriber_id, 0, 'tag_added', $tag_id );
	}

	/**
	 * Wrap content in a template if specified.
	 *
	 * @param string $content
	 * @param int|null $template_id
	 * @return string
	 */
	public static function apply_template( $content, $template_id ) {
		if ( empty( $template_id ) && $template_id !== 0 && $template_id !== '0' ) {
			return $content;
		}

		if ( $template_id == 0 ) {
			$branda_template = get_option('ub_email_template');
			if ( ! empty( $branda_template ) && is_array( $branda_template ) && ! empty( $branda_template['email']['content'] ) ) {
				$template_content = $branda_template['email']['content'];
			} else {
				return $content;
			}
		} else {
			global $wpdb;
			$table = $wpdb->prefix . 'bomb_bag_templates';
			$template_content = $wpdb->get_var( $wpdb->prepare( "SELECT content FROM $table WHERE id = %d", $template_id ) );
			
			if ( empty( $template_content ) ) {
				return $content;
			}
		}

		if ( strpos( $template_content, '{MESSAGE}' ) !== false ) {
			return str_replace( '{MESSAGE}', $content, $template_content );
		}

		return $content;
	}
}
