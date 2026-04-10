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

		// Queue emails for each subscriber
		foreach ($subscribers as $subscriber) {
			$tracking_id = $this->generate_tracking_id();
			
			$wpdb->insert($emails_table, array(
				'campaign_id' => $campaign_id,
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
			$content = $this->personalize_content($campaign->content, $email);
			$content = $this->add_tracking($content, $email->tracking_id);
			
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . $from_name . ' <' . $from_email . '>',
				'List-Unsubscribe: <' . $this->get_unsubscribe_url($email->tracking_id) . '>'
			);

			$sent = Xophz_Compass_Bomb_Bag_Email_Providers::send($email->email, $campaign->subject, $content, $headers);

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
	 * @return   string
	 */
	private function add_tracking( $content, $tracking_id ) {
		$tracking_url = add_query_arg(array(
			'bomb_bag_track' => 'open',
			'tid' => $tracking_id
		), home_url());

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
			function($matches) use ($tracking_id) {
				$url = $matches[2];
				// Don't track unsubscribe links
				if (strpos($url, 'unsubscribe') !== false) {
					return $matches[0];
				}
				$tracked_url = add_query_arg(array(
					'bomb_bag_track' => 'click',
					'tid' => $tracking_id,
					'url' => urlencode($url)
				), home_url());
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
			'bomb_bag_track' => 'unsubscribe',
			'tid' => $tracking_id
		), home_url());
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
}
