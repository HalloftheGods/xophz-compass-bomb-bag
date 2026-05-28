<?php

class Xophz_Compass_Bomb_Bag_Activator {

	public static function activate() {
		if ( !function_exists('is_plugin_active') ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		if ( !class_exists( 'Xophz_Compass' ) ) {  
			die('This plugin requires COMPASS to be active.');
		}
		
		self::create_tables();
		self::set_default_options();
	}

	private static function create_tables() {
		global $wpdb;
		
		$charset_collate = $wpdb->get_charset_collate();
		
		$campaigns_table        = $wpdb->prefix . 'bomb_bag_campaigns';
		$subscribers_table      = $wpdb->prefix . 'bomb_bag_subscribers';
		$lists_table            = $wpdb->prefix . 'bomb_bag_lists';
		$list_subscribers_table = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$emails_table           = $wpdb->prefix . 'bomb_bag_emails';
		$analytics_table        = $wpdb->prefix . 'bomb_bag_analytics';
		$drip_sequences_table   = $wpdb->prefix . 'bomb_bag_drip_sequences';
		$drip_steps_table       = $wpdb->prefix . 'bomb_bag_drip_steps';
		$drip_enrollments_table = $wpdb->prefix . 'bomb_bag_drip_enrollments';
		$templates_table        = $wpdb->prefix . 'bomb_bag_templates';
		
		// Phase 1 & 2 Modnernization additions
		$journeys_table            = $wpdb->prefix . 'bomb_bag_journeys';
		$journey_enrollments_table = $wpdb->prefix . 'bomb_bag_journey_enrollments';
		$segments_table            = $wpdb->prefix . 'bomb_bag_segments';
		$tags_table                = $wpdb->prefix . 'bomb_bag_tags';
		$subscriber_tags_table     = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		// Phase 3 additions
		$campaign_variants_table   = $wpdb->prefix . 'bomb_bag_campaign_variants';

		$sql_campaigns = "CREATE TABLE IF NOT EXISTS $campaigns_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			subject VARCHAR(500) NOT NULL,
			content LONGTEXT,
			from_name VARCHAR(255) DEFAULT NULL,
			from_email VARCHAR(255) DEFAULT NULL,
			status ENUM('draft', 'scheduled', 'sending', 'sent', 'paused') DEFAULT 'draft',
			list_id BIGINT(20) UNSIGNED DEFAULT NULL,
			template_id BIGINT(20) UNSIGNED DEFAULT NULL,
			scheduled_at DATETIME DEFAULT NULL,
			sent_at DATETIME DEFAULT NULL,
			total_recipients INT UNSIGNED DEFAULT 0,
			total_sent INT UNSIGNED DEFAULT 0,
			total_opened INT UNSIGNED DEFAULT 0,
			total_clicked INT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY list_id (list_id),
			KEY scheduled_at (scheduled_at)
		) $charset_collate;";

		$sql_subscribers = "CREATE TABLE IF NOT EXISTS $subscribers_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			email VARCHAR(255) NOT NULL,
			first_name VARCHAR(100) DEFAULT NULL,
			last_name VARCHAR(100) DEFAULT NULL,
			status ENUM('active', 'unsubscribed', 'bounced', 'complained') DEFAULT 'active',
			source VARCHAR(100) DEFAULT 'manual',
			score INT DEFAULT 0,
			lead_status VARCHAR(50) DEFAULT 'cold',
			custom_fields TEXT,
			subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			unsubscribed_at DATETIME DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY email (email),
			KEY status (status),
			KEY score (score)
		) $charset_collate;";

		$sql_lists = "CREATE TABLE IF NOT EXISTS $lists_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			subscriber_count INT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		$sql_list_subscribers = "CREATE TABLE IF NOT EXISTS $list_subscribers_table (
			list_id BIGINT(20) UNSIGNED NOT NULL,
			subscriber_id BIGINT(20) UNSIGNED NOT NULL,
			added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (list_id, subscriber_id),
			KEY subscriber_id (subscriber_id)
		) $charset_collate;";

		$sql_emails = "CREATE TABLE IF NOT EXISTS $emails_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT(20) UNSIGNED DEFAULT NULL,
			variant_id BIGINT(20) UNSIGNED DEFAULT NULL,
			drip_step_id BIGINT(20) UNSIGNED DEFAULT NULL,
			journey_node_id VARCHAR(64) DEFAULT NULL,
			subscriber_id BIGINT(20) UNSIGNED NOT NULL,
			status ENUM('queued', 'sent', 'failed', 'bounced') DEFAULT 'queued',
			tracking_id VARCHAR(64) NOT NULL,
			sent_at DATETIME DEFAULT NULL,
			opened_at DATETIME DEFAULT NULL,
			clicked_at DATETIME DEFAULT NULL,
			error_message TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY tracking_id (tracking_id),
			KEY campaign_id (campaign_id),
			KEY variant_id (variant_id),
			KEY drip_step_id (drip_step_id),
			KEY journey_node_id (journey_node_id),
			KEY subscriber_id (subscriber_id),
			KEY status (status)
		) $charset_collate;";

		$sql_analytics = "CREATE TABLE IF NOT EXISTS $analytics_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			email_id BIGINT(20) UNSIGNED NOT NULL,
			event_type ENUM('open', 'click', 'unsubscribe', 'bounce', 'complaint') NOT NULL,
			event_data TEXT,
			ip_address VARCHAR(45) DEFAULT NULL,
			user_agent TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY email_id (email_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) $charset_collate;";

		$sql_drip_sequences = "CREATE TABLE IF NOT EXISTS $drip_sequences_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			status ENUM('active', 'paused', 'archived') DEFAULT 'paused',
			trigger_type ENUM('subscribe', 'manual', 'tag_added') DEFAULT 'manual',
			list_id BIGINT(20) UNSIGNED DEFAULT NULL,
			from_name VARCHAR(255) DEFAULT NULL,
			from_email VARCHAR(255) DEFAULT NULL,
			total_enrolled INT UNSIGNED DEFAULT 0,
			total_completed INT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY list_id (list_id)
		) $charset_collate;";

		$sql_drip_steps = "CREATE TABLE IF NOT EXISTS $drip_steps_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			sequence_id BIGINT(20) UNSIGNED NOT NULL,
			position INT UNSIGNED DEFAULT 0,
			subject VARCHAR(500) NOT NULL,
			content LONGTEXT,
			delay_days INT UNSIGNED DEFAULT 0,
			delay_hours INT UNSIGNED DEFAULT 0,
			template_id BIGINT(20) UNSIGNED DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY sequence_id (sequence_id),
			KEY position (position)
		) $charset_collate;";

		$sql_drip_enrollments = "CREATE TABLE IF NOT EXISTS $drip_enrollments_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			sequence_id BIGINT(20) UNSIGNED NOT NULL,
			subscriber_id BIGINT(20) UNSIGNED NOT NULL,
			current_step INT UNSIGNED DEFAULT 0,
			status ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
			enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			completed_at DATETIME DEFAULT NULL,
			next_send_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY sequence_id (sequence_id),
			KEY subscriber_id (subscriber_id),
			KEY status (status),
			KEY next_send_at (next_send_at)
		) $charset_collate;";

		$sql_templates = "CREATE TABLE IF NOT EXISTS $templates_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			category VARCHAR(100) DEFAULT 'custom',
			thumbnail_url VARCHAR(500) DEFAULT NULL,
			content LONGTEXT NOT NULL,
			is_default TINYINT(1) DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY category (category),
			KEY is_default (is_default)
		) $charset_collate;";

		$sql_journeys = "CREATE TABLE IF NOT EXISTS $journeys_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			status ENUM('active', 'paused', 'draft') DEFAULT 'draft',
			trigger_type VARCHAR(100) DEFAULT 'manual',
			nodes_json LONGTEXT,
			edges_json LONGTEXT,
			total_enrolled INT UNSIGNED DEFAULT 0,
			total_completed INT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";

		$sql_journey_enrollments = "CREATE TABLE IF NOT EXISTS $journey_enrollments_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			journey_id BIGINT(20) UNSIGNED NOT NULL,
			subscriber_id BIGINT(20) UNSIGNED NOT NULL,
			current_node_id VARCHAR(255) DEFAULT NULL,
			status ENUM('active', 'completed', 'paused', 'cancelled') DEFAULT 'active',
			state_data LONGTEXT,
			enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			completed_at DATETIME DEFAULT NULL,
			next_evaluation_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY journey_id (journey_id),
			KEY subscriber_id (subscriber_id),
			KEY status (status),
			KEY next_evaluation_at (next_evaluation_at)
		) $charset_collate;";

		$sql_segments = "CREATE TABLE IF NOT EXISTS $segments_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			rules_json LONGTEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		$sql_tags = "CREATE TABLE IF NOT EXISTS $tags_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			color VARCHAR(32) DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY name (name)
		) $charset_collate;";

		$sql_subscriber_tags = "CREATE TABLE IF NOT EXISTS $subscriber_tags_table (
			tag_id BIGINT(20) UNSIGNED NOT NULL,
			subscriber_id BIGINT(20) UNSIGNED NOT NULL,
			assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (tag_id, subscriber_id),
			KEY subscriber_id (subscriber_id)
		) $charset_collate;";

		$sql_campaign_variants = "CREATE TABLE IF NOT EXISTS $campaign_variants_table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT(20) UNSIGNED NOT NULL,
			subject VARCHAR(500) NOT NULL,
			content LONGTEXT,
			weight_percentage INT UNSIGNED DEFAULT 100,
			total_sent INT UNSIGNED DEFAULT 0,
			total_opened INT UNSIGNED DEFAULT 0,
			total_clicked INT UNSIGNED DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_campaigns );
		dbDelta( $sql_subscribers );
		dbDelta( $sql_lists );
		dbDelta( $sql_list_subscribers );
		dbDelta( $sql_emails );
		dbDelta( $sql_analytics );
		dbDelta( $sql_drip_sequences );
		dbDelta( $sql_drip_steps );
		dbDelta( $sql_drip_enrollments );
		dbDelta( $sql_templates );
		dbDelta( $sql_journeys );
		dbDelta( $sql_journey_enrollments );
		dbDelta( $sql_segments );
		dbDelta( $sql_tags );
		dbDelta( $sql_subscriber_tags );
		dbDelta( $sql_campaign_variants );

		self::seed_default_list();
		self::seed_default_templates();
		self::seed_default_journeys();
	}

	private static function set_default_options() {
		$defaults = array(
			'email_provider'  => 'wordpress',
			'from_name'       => get_bloginfo('name'),
			'from_email'      => get_option('admin_email'),
			'sendgrid_api_key' => '',
			'mailgun_api_key' => '',
			'mailgun_domain'  => '',
			'smtp_host'       => '',
			'smtp_port'       => 587,
			'smtp_username'   => '',
			'smtp_password'   => '',
			'smtp_encryption' => 'tls',
			'batch_size'      => 50,
			'batch_delay'     => 1,
		);

		$existing = get_option('bomb_bag_settings', array());
		$merged = array_merge($defaults, $existing);
		update_option('bomb_bag_settings', $merged);
	}

	private static function seed_default_list() {
		global $wpdb;
		$lists_table = $wpdb->prefix . 'bomb_bag_lists';

		$count = $wpdb->get_var("SELECT COUNT(*) FROM $lists_table");
		if ( $count > 0 ) {
			return;
		}

		$wpdb->insert( $lists_table, array(
			'name'        => 'Main Newsletter',
			'description' => 'Primary subscriber list for newsletters'
		));
	}

	private static function seed_default_templates() {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_templates';

		$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
		if ( $count > 0 ) {
			return;
		}

		$templates = self::get_default_template_definitions();
		foreach ( $templates as $t ) {
			$wpdb->insert( $table, array(
				'name'        => $t['name'],
				'description' => $t['description'],
				'category'    => $t['category'],
				'content'     => $t['content'],
				'is_default'  => 1,
			));
		}
	}

	private static function seed_default_journeys() {
		global $wpdb;
		$table = $wpdb->prefix . 'bomb_bag_journeys';

		$count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
		if ( $count > 0 ) {
			return;
		}

		$nodes = array(
			array(
				'id' => 'node_trigger',
				'type' => 'trigger_subscribe',
				'data' => array( 'label' => 'Subscriber Joined List', 'list_id' => 1 ),
				'position' => array( 'x' => 250, 'y' => 50 )
			),
			array(
				'id' => 'node_email_welcome',
				'type' => 'action_email',
				'data' => array( 'label' => 'Send Welcome Email', 'subject' => 'Welcome!', 'template_id' => 3 ),
				'position' => array( 'x' => 250, 'y' => 150 )
			),
			array(
				'id' => 'node_wait_1',
				'type' => 'logic_wait',
				'data' => array( 'label' => 'Wait 2 Days', 'days' => 2, 'hours' => 0, 'minutes' => 0 ),
				'position' => array( 'x' => 250, 'y' => 250 )
			),
			array(
				'id' => 'node_email_followup',
				'type' => 'action_email',
				'data' => array( 'label' => 'Send Follow-up Email', 'subject' => 'Checking in', 'template_id' => 2 ),
				'position' => array( 'x' => 250, 'y' => 350 )
			)
		);

		$edges = array(
			array(
				'id' => 'e-node_trigger-node_email_welcome',
				'source' => 'node_trigger',
				'target' => 'node_email_welcome'
			),
			array(
				'id' => 'e-node_email_welcome-node_wait_1',
				'source' => 'node_email_welcome',
				'target' => 'node_wait_1'
			),
			array(
				'id' => 'e-node_wait_1-node_email_followup',
				'source' => 'node_wait_1',
				'target' => 'node_email_followup'
			)
		);

		$wpdb->insert( $table, array(
			'name'        => 'Welcome Series',
			'description' => 'A default journey that welcomes new subscribers and follows up 2 days later.',
			'status'      => 'draft',
			'trigger_type'=> 'subscribe',
			'nodes_json'  => wp_json_encode( $nodes ),
			'edges_json'  => wp_json_encode( $edges ),
		));
	}

	private static function get_default_template_definitions() {
		return array(

			array(
				'name'        => 'Obsidian Digest',
				'description' => 'Dark-mode newsletter with accent dividers and multi-section layout',
				'category'    => 'newsletter',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Newsletter</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f0f14; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f0f14;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 16px 16px 0 0; padding: 48px 40px; text-align: center; border-bottom: 3px solid #62c9ff;">
              <h1 style="margin: 0; font-size: 32px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">Your Weekly Digest</h1>
              <p style="margin: 12px 0 0; font-size: 15px; color: #8b95a5;">Fresh insights delivered to your inbox</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #1a1a2e; padding: 40px;">
              <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.7; color: #c8cdd5;">Hello {{first_name}},</p>
              <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.7; color: #c8cdd5;">Here are the highlights from this week that we thought you&#39;d love.</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                <tr>
                  <td style="padding: 24px; background-color: rgba(98,201,255,0.06); border-left: 3px solid #62c9ff; border-radius: 0 8px 8px 0;">
                    <h3 style="margin: 0 0 8px; font-size: 18px; color: #62c9ff; font-weight: 600;">Featured Story</h3>
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #a0a8b4;">Share your top story or announcement here. Give readers a reason to click through and engage.</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                <tr>
                  <td style="padding: 24px; background-color: rgba(255,255,255,0.03); border-radius: 8px;">
                    <h3 style="margin: 0 0 8px; font-size: 18px; color: #ffffff; font-weight: 600;">Quick Updates</h3>
                    <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6; color: #a0a8b4;">&#8226; First update or news item goes here</p>
                    <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.6; color: #a0a8b4;">&#8226; Second update or news item goes here</p>
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #a0a8b4;">&#8226; Third update or news item goes here</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="background: linear-gradient(135deg, #62c9ff, #4facfe); border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 16px 40px; font-size: 15px; font-weight: 600; color: #0f0f14; text-decoration: none;">Read the Full Issue</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #12121a; border-radius: 0 0 16px 16px; padding: 32px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #555e6e;">You received this because you subscribed to our newsletter.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #62c9ff; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Ivory Editorial',
				'description' => 'Clean light-mode newsletter with refined typography and subtle accents',
				'category'    => 'newsletter',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Newsletter</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f2ee; font-family: Georgia, \'Times New Roman\', serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f2ee;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="padding: 40px 40px 24px; text-align: center;">
              <h1 style="margin: 0; font-size: 14px; font-weight: 600; letter-spacing: 4px; text-transform: uppercase; color: #1a1a2e;">Your Brand</h1>
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; border-radius: 4px; padding: 48px 44px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-bottom: 1px solid #e8e4de; margin-bottom: 32px; padding-bottom: 32px;">
                <tr>
                  <td>
                    <p style="margin: 0 0 4px; font-size: 12px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: #b08d57;">This Week</p>
                    <h2 style="margin: 0; font-size: 28px; font-weight: 400; line-height: 1.3; color: #1a1a2e;">The stories that matter most</h2>
                  </td>
                </tr>
              </table>
              <p style="margin: 0 0 24px; font-size: 17px; line-height: 1.8; color: #3d3d3d;">Dear {{first_name}},</p>
              <p style="margin: 0 0 24px; font-size: 17px; line-height: 1.8; color: #3d3d3d;">We&#39;ve curated the best reads, insights, and inspiration from this past week. Settle in and enjoy.</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                <tr>
                  <td style="border-left: 3px solid #b08d57; padding-left: 20px;">
                    <h3 style="margin: 0 0 6px; font-size: 20px; font-weight: 400; color: #1a1a2e;">Feature Article Title</h3>
                    <p style="margin: 0; font-size: 15px; line-height: 1.7; color: #6b6b6b;">A brief preview that entices readers to click through for the full story. Keep it compelling.</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                <tr>
                  <td style="border-left: 3px solid #e8e4de; padding-left: 20px;">
                    <h3 style="margin: 0 0 6px; font-size: 20px; font-weight: 400; color: #1a1a2e;">Second Story Title</h3>
                    <p style="margin: 0; font-size: 15px; line-height: 1.7; color: #6b6b6b;">Another story preview goes here. Give enough detail to spark curiosity.</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="border: 2px solid #1a1a2e; border-radius: 4px;">
                    <a href="#" style="display: inline-block; padding: 14px 36px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif; font-size: 13px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #1a1a2e; text-decoration: none;">Continue Reading</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding: 32px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #999;">You received this because you subscribed to our editorial.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #b08d57; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Neon Welcome',
				'description' => 'High-impact dark welcome email with neon accent and onboarding steps',
				'category'    => 'welcome',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a12; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a12;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="background: linear-gradient(180deg, #141428 0%, #0f0f1e 100%); border-radius: 16px 16px 0 0; padding: 56px 40px; text-align: center; border-top: 4px solid #62c9ff;">
              <p style="margin: 0 0 16px; font-size: 48px;">&#127881;</p>
              <h1 style="margin: 0; font-size: 36px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">You&#39;re In, {{first_name}}!</h1>
              <p style="margin: 16px 0 0; font-size: 17px; color: #8b95a5; line-height: 1.6;">Welcome to something special. We&#39;re glad to have you.</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #141428; padding: 0 40px 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
                <tr>
                  <td style="padding: 24px; background-color: rgba(98,201,255,0.08); border-radius: 12px; border: 1px solid rgba(98,201,255,0.12);">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="48" valign="top" style="padding-right: 16px;">
                          <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #62c9ff, #4facfe); border-radius: 10px; text-align: center; line-height: 40px; font-size: 18px; font-weight: 700; color: #0a0a12;">1</div>
                        </td>
                        <td valign="top">
                          <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #ffffff;">Explore Your Dashboard</h3>
                          <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #8b95a5;">Take a quick tour and discover the features waiting for you.</p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
                <tr>
                  <td style="padding: 24px; background-color: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="48" valign="top" style="padding-right: 16px;">
                          <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.08); border-radius: 10px; text-align: center; line-height: 40px; font-size: 18px; font-weight: 700; color: #ffffff;">2</div>
                        </td>
                        <td valign="top">
                          <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #ffffff;">Personalize Your Experience</h3>
                          <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #8b95a5;">Set your preferences so everything feels right from day one.</p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 36px;">
                <tr>
                  <td style="padding: 24px; background-color: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06);">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td width="48" valign="top" style="padding-right: 16px;">
                          <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.08); border-radius: 10px; text-align: center; line-height: 40px; font-size: 18px; font-weight: 700; color: #ffffff;">3</div>
                        </td>
                        <td valign="top">
                          <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600; color: #ffffff;">Connect With Us</h3>
                          <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #8b95a5;">Join our community and stay in the loop with updates and tips.</p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="background: linear-gradient(135deg, #62c9ff, #4facfe); border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 16px 48px; font-size: 15px; font-weight: 700; color: #0a0a12; text-decoration: none;">Get Started</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #0c0c16; border-radius: 0 0 16px 16px; padding: 28px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #555e6e;">Need help? Just reply to this email.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #62c9ff; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Warm Handshake',
				'description' => 'Friendly light welcome with warm tones and a personal touch',
				'category'    => 'welcome',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome</title>
</head>
<body style="margin: 0; padding: 0; background-color: #faf8f5; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #faf8f5;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="background: linear-gradient(135deg, #e8725a 0%, #f0956e 50%, #f4c76b 100%); border-radius: 16px 16px 0 0; padding: 56px 40px; text-align: center;">
              <h1 style="margin: 0; font-size: 34px; font-weight: 700; color: #ffffff;">Welcome, {{first_name}} &#128075;</h1>
              <p style="margin: 14px 0 0; font-size: 16px; color: rgba(255,255,255,0.9); line-height: 1.5;">We&#39;re so happy you&#39;re here.</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; padding: 40px 44px;">
              <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.8; color: #4a4a4a;">Thanks for joining us! We built this for people just like you, and we&#39;re excited to show you what&#39;s possible.</p>
              <p style="margin: 0 0 28px; font-size: 16px; line-height: 1.8; color: #4a4a4a;">Here&#39;s what you can look forward to:</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                <tr>
                  <td width="32" valign="top" style="padding-top: 2px; font-size: 18px;">&#10024;</td>
                  <td style="padding-bottom: 16px;">
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #4a4a4a;"><strong style="color: #2d2d2d;">Hand-picked content</strong> tailored to your interests</p>
                  </td>
                </tr>
                <tr>
                  <td width="32" valign="top" style="padding-top: 2px; font-size: 18px;">&#128640;</td>
                  <td style="padding-bottom: 16px;">
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #4a4a4a;"><strong style="color: #2d2d2d;">Early access</strong> to new features before anyone else</p>
                  </td>
                </tr>
                <tr>
                  <td width="32" valign="top" style="padding-top: 2px; font-size: 18px;">&#127873;</td>
                  <td style="padding-bottom: 16px;">
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #4a4a4a;"><strong style="color: #2d2d2d;">Exclusive offers</strong> just for our subscribers</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 24px auto 0;">
                <tr>
                  <td style="background-color: #e8725a; border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 16px 44px; font-size: 15px; font-weight: 700; color: #ffffff; text-decoration: none;">Explore Now</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #f5f0eb; border-radius: 0 0 16px 16px; padding: 28px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #999;">Questions? Just hit reply &#8212; a real human will answer.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #e8725a; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Flash Sale',
				'description' => 'Bold promotional with urgency banner and feature highlights',
				'category'    => 'promotional',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Special Offer</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f0f14; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f0f14;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="background: linear-gradient(135deg, #ff6b6b, #ee5a24); padding: 16px; text-align: center; border-radius: 16px 16px 0 0;">
              <p style="margin: 0; font-size: 14px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #ffffff;">&#9889; Limited Time Offer &#9889;</p>
            </td>
          </tr>
          <tr>
            <td style="background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); padding: 48px 40px; text-align: center;">
              <h1 style="margin: 0; font-size: 52px; font-weight: 800; color: #ffffff; letter-spacing: -1px;">40% OFF</h1>
              <p style="margin: 12px 0 0; font-size: 18px; color: #8b95a5; line-height: 1.5;">Everything. No exclusions. Ends Sunday.</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #1a1a2e; padding: 0 40px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="33%" style="padding: 20px 8px; text-align: center; vertical-align: top;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 12px; background: rgba(98,201,255,0.1); border-radius: 12px; line-height: 48px; font-size: 22px;">&#128230;</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #ffffff;">Free Shipping</h4>
                    <p style="margin: 0; font-size: 13px; color: #8b95a5;">On all orders</p>
                  </td>
                  <td width="33%" style="padding: 20px 8px; text-align: center; vertical-align: top;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 12px; background: rgba(98,201,255,0.1); border-radius: 12px; line-height: 48px; font-size: 22px;">&#128274;</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #ffffff;">Secure Checkout</h4>
                    <p style="margin: 0; font-size: 13px; color: #8b95a5;">256-bit encrypted</p>
                  </td>
                  <td width="33%" style="padding: 20px 8px; text-align: center; vertical-align: top;">
                    <div style="width: 48px; height: 48px; margin: 0 auto 12px; background: rgba(98,201,255,0.1); border-radius: 12px; line-height: 48px; font-size: 22px;">&#128260;</div>
                    <h4 style="margin: 0 0 4px; font-size: 14px; font-weight: 600; color: #ffffff;">Easy Returns</h4>
                    <p style="margin: 0; font-size: 13px; color: #8b95a5;">30-day guarantee</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #1a1a2e; padding: 32px 40px 48px; text-align: center;">
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="background: linear-gradient(135deg, #ff6b6b, #ee5a24); border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 18px 56px; font-size: 16px; font-weight: 700; color: #ffffff; text-decoration: none; letter-spacing: 0.5px;">Shop the Sale</a>
                  </td>
                </tr>
              </table>
              <p style="margin: 16px 0 0; font-size: 13px; color: #555e6e;">Use code <strong style="color: #ff6b6b;">SAVE40</strong> at checkout</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #12121a; border-radius: 0 0 16px 16px; padding: 28px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #555e6e;">Offer valid through Sunday at midnight.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #62c9ff; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Product Launch',
				'description' => 'Premium product announcement with gradient hero and feature grid',
				'category'    => 'promotional',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Product</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="background: linear-gradient(160deg, #667eea 0%, #764ba2 50%, #f093fb 100%); border-radius: 16px 16px 0 0; padding: 60px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; color: rgba(255,255,255,0.7);">Introducing</p>
              <h1 style="margin: 0; font-size: 40px; font-weight: 800; color: #ffffff; letter-spacing: -1px;">Something Amazing</h1>
              <p style="margin: 16px 0 0; font-size: 17px; color: rgba(255,255,255,0.85); line-height: 1.6;">The product you&#39;ve been waiting for is finally here.</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; padding: 40px;">
              <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.7; color: #555; text-align: center;">Hey {{first_name}}, we&#39;ve been working on this for months and can&#39;t wait for you to try it.</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                <tr>
                  <td width="50%" style="padding: 16px; vertical-align: top;">
                    <div style="padding: 24px; background-color: #f8f7ff; border-radius: 12px; text-align: center; height: 100%;">
                      <p style="margin: 0 0 8px; font-size: 28px;">&#9889;</p>
                      <h4 style="margin: 0 0 6px; font-size: 15px; font-weight: 600; color: #1a1a2e;">Lightning Fast</h4>
                      <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #666;">Performance that sets a new standard.</p>
                    </div>
                  </td>
                  <td width="50%" style="padding: 16px; vertical-align: top;">
                    <div style="padding: 24px; background-color: #f8f7ff; border-radius: 12px; text-align: center; height: 100%;">
                      <p style="margin: 0 0 8px; font-size: 28px;">&#128272;</p>
                      <h4 style="margin: 0 0 6px; font-size: 15px; font-weight: 600; color: #1a1a2e;">Secure by Design</h4>
                      <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #666;">Your privacy is our priority.</p>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td width="50%" style="padding: 16px; vertical-align: top;">
                    <div style="padding: 24px; background-color: #f8f7ff; border-radius: 12px; text-align: center; height: 100%;">
                      <p style="margin: 0 0 8px; font-size: 28px;">&#127912;</p>
                      <h4 style="margin: 0 0 6px; font-size: 15px; font-weight: 600; color: #1a1a2e;">Beautiful Design</h4>
                      <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #666;">Crafted with care, down to every pixel.</p>
                    </div>
                  </td>
                  <td width="50%" style="padding: 16px; vertical-align: top;">
                    <div style="padding: 24px; background-color: #f8f7ff; border-radius: 12px; text-align: center; height: 100%;">
                      <p style="margin: 0 0 8px; font-size: 28px;">&#129309;</p>
                      <h4 style="margin: 0 0 6px; font-size: 15px; font-weight: 600; color: #1a1a2e;">Built Together</h4>
                      <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #666;">Shaped by feedback from people like you.</p>
                    </div>
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 16px 48px; font-size: 15px; font-weight: 700; color: #ffffff; text-decoration: none;">Discover More</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #f0eef5; border-radius: 0 0 16px 16px; padding: 28px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #999;">You received this as a valued subscriber.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #764ba2; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Order Confirmation',
				'description' => 'Clean transactional receipt with order summary and line items',
				'category'    => 'transactional',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmation</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="background-color: #ffffff; border-radius: 12px 12px 0 0; padding: 40px 40px 32px; text-align: center; border-bottom: 1px solid #eef0f3;">
              <div style="width: 56px; height: 56px; margin: 0 auto 20px; background-color: #e8faf0; border-radius: 50%; line-height: 56px; font-size: 28px;">&#10003;</div>
              <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #1a1a2e;">Order Confirmed</h1>
              <p style="margin: 8px 0 0; font-size: 15px; color: #6b7280;">Thank you for your purchase, {{first_name}}!</p>
              <p style="margin: 16px 0 0; font-size: 13px; color: #9ca3af;">Order #12345 &#183; May 27, 2026</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; padding: 32px 40px;">
              <h3 style="margin: 0 0 20px; font-size: 14px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #9ca3af;">Order Summary</h3>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="padding: 16px 0; border-bottom: 1px solid #f3f4f6;">
                    <p style="margin: 0; font-size: 15px; font-weight: 600; color: #1a1a2e;">Product Name</p>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #9ca3af;">Qty: 1 &#183; Size: M</p>
                  </td>
                  <td style="padding: 16px 0; border-bottom: 1px solid #f3f4f6; text-align: right; vertical-align: top;">
                    <p style="margin: 0; font-size: 15px; font-weight: 600; color: #1a1a2e;">$49.00</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding: 16px 0; border-bottom: 1px solid #f3f4f6;">
                    <p style="margin: 0; font-size: 15px; font-weight: 600; color: #1a1a2e;">Another Product</p>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #9ca3af;">Qty: 2</p>
                  </td>
                  <td style="padding: 16px 0; border-bottom: 1px solid #f3f4f6; text-align: right; vertical-align: top;">
                    <p style="margin: 0; font-size: 15px; font-weight: 600; color: #1a1a2e;">$30.00</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 16px;">
                <tr>
                  <td style="padding: 8px 0;">
                    <p style="margin: 0; font-size: 14px; color: #6b7280;">Subtotal</p>
                  </td>
                  <td style="padding: 8px 0; text-align: right;">
                    <p style="margin: 0; font-size: 14px; color: #6b7280;">$79.00</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding: 8px 0;">
                    <p style="margin: 0; font-size: 14px; color: #6b7280;">Shipping</p>
                  </td>
                  <td style="padding: 8px 0; text-align: right;">
                    <p style="margin: 0; font-size: 14px; color: #22c55e;">Free</p>
                  </td>
                </tr>
                <tr>
                  <td style="padding: 12px 0 0; border-top: 2px solid #1a1a2e;">
                    <p style="margin: 0; font-size: 16px; font-weight: 700; color: #1a1a2e;">Total</p>
                  </td>
                  <td style="padding: 12px 0 0; border-top: 2px solid #1a1a2e; text-align: right;">
                    <p style="margin: 0; font-size: 16px; font-weight: 700; color: #1a1a2e;">$79.00</p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; padding: 0 40px 36px; text-align: center;">
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="background-color: #1a1a2e; border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 14px 40px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none;">Track Your Order</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #f9fafb; border-radius: 0 0 12px 12px; padding: 24px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #9ca3af;">Questions about your order? Reply to this email.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #9ca3af; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'System Notification',
				'description' => 'Minimal dark transactional alert with status indicator',
				'category'    => 'transactional',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notification</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f0f14; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f0f14;">
    <tr>
      <td align="center" style="padding: 48px 16px;">
        <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="max-width: 520px; width: 100%;">
          <tr>
            <td style="background-color: #1a1a2e; border-radius: 16px; padding: 48px 40px; border: 1px solid rgba(255,255,255,0.06);">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                <tr>
                  <td width="12" style="padding-right: 14px; vertical-align: middle;">
                    <div style="width: 10px; height: 10px; background-color: #22c55e; border-radius: 50%;"></div>
                  </td>
                  <td>
                    <h1 style="margin: 0; font-size: 20px; font-weight: 600; color: #ffffff;">Action Required</h1>
                  </td>
                </tr>
              </table>
              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.7; color: #a0a8b4;">Hi {{first_name}},</p>
              <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.7; color: #a0a8b4;">We detected a new sign-in to your account. If this was you, no action is needed. Otherwise, please secure your account immediately.</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                <tr>
                  <td style="padding: 20px; background-color: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid rgba(255,255,255,0.06);">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="padding-bottom: 8px;">
                          <span style="font-size: 12px; color: #555e6e;">DEVICE</span><br>
                          <span style="font-size: 14px; color: #c8cdd5;">Chrome on macOS</span>
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-bottom: 8px;">
                          <span style="font-size: 12px; color: #555e6e;">LOCATION</span><br>
                          <span style="font-size: 14px; color: #c8cdd5;">San Francisco, CA</span>
                        </td>
                      </tr>
                      <tr>
                        <td>
                          <span style="font-size: 12px; color: #555e6e;">TIME</span><br>
                          <span style="font-size: 14px; color: #c8cdd5;">May 27, 2026 at 3:42 PM</span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="background: linear-gradient(135deg, #62c9ff, #4facfe); border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 14px 36px; font-size: 14px; font-weight: 600; color: #0f0f14; text-decoration: none;">Review Activity</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding: 24px 40px; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #555e6e;">This is an automated security notification.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #62c9ff; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Knowledge Series',
				'description' => 'Educational drip email with numbered lesson format and progress bar',
				'category'    => 'drip',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lesson</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f0f14; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f0f14;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="background-color: #1a1a2e; border-radius: 16px 16px 0 0; padding: 40px; border-bottom: 1px solid rgba(255,255,255,0.06);">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    <p style="margin: 0 0 6px; font-size: 12px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: #62c9ff;">Lesson 1 of 5</p>
                    <h1 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px;">Getting Started with the Basics</h1>
                  </td>
                </tr>
                <tr>
                  <td style="padding-top: 20px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                      <tr>
                        <td style="background-color: rgba(255,255,255,0.06); border-radius: 4px; height: 6px;">
                          <div style="width: 20%; height: 6px; background: linear-gradient(90deg, #62c9ff, #4facfe); border-radius: 4px;"></div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #1a1a2e; padding: 40px;">
              <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.7; color: #c8cdd5;">Hi {{first_name}},</p>
              <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.7; color: #c8cdd5;">Welcome to your first lesson! Over the next five days, we&#39;ll walk you through everything you need to know to get up and running.</p>
              <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 600; color: #ffffff;">Today&#39;s Key Takeaways</h2>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                <tr>
                  <td style="padding: 16px 20px; background-color: rgba(98,201,255,0.06); border-radius: 8px; margin-bottom: 8px;">
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #c8cdd5;"><strong style="color: #62c9ff;">Concept One:</strong> Start with the foundation. Understanding the core principles will make everything else click.</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 12px;">
                <tr>
                  <td style="padding: 16px 20px; background-color: rgba(255,255,255,0.03); border-radius: 8px;">
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #c8cdd5;"><strong style="color: #ffffff;">Concept Two:</strong> Practice builds confidence. Try applying what you learn before moving to the next lesson.</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                <tr>
                  <td style="padding: 16px 20px; background-color: rgba(255,255,255,0.03); border-radius: 8px;">
                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #c8cdd5;"><strong style="color: #ffffff;">Concept Three:</strong> Don&#39;t rush. Take your time to absorb each lesson fully.</p>
                  </td>
                </tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="background: linear-gradient(135deg, #62c9ff, #4facfe); border-radius: 8px;">
                    <a href="#" style="display: inline-block; padding: 16px 44px; font-size: 15px; font-weight: 700; color: #0f0f14; text-decoration: none;">Start the Exercise</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="background-color: #12121a; border-radius: 0 0 16px 16px; padding: 28px 40px; text-align: center;">
              <p style="margin: 0 0 4px; font-size: 12px; color: #555e6e;">Lesson 2 arrives tomorrow at the same time.</p>
              <p style="margin: 0 0 8px; font-size: 12px; color: #555e6e;">Reply any time if you have questions.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #62c9ff; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Nurture Touch',
				'description' => 'Soft-toned drip email for relationship building with personal narrative',
				'category'    => 'drip',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check In</title>
</head>
<body style="margin: 0; padding: 0; background-color: #faf8f5; font-family: Georgia, \'Times New Roman\', serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #faf8f5;">
    <tr>
      <td align="center" style="padding: 48px 16px;">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width: 560px; width: 100%;">
          <tr>
            <td style="text-align: center; padding-bottom: 32px;">
              <p style="margin: 0; font-size: 12px; font-weight: 600; letter-spacing: 3px; text-transform: uppercase; color: #b08d57; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif;">Day 3 of Your Journey</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; border-radius: 4px; padding: 48px 44px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
              <p style="margin: 0 0 24px; font-size: 18px; line-height: 1.8; color: #3d3d3d;">Hi {{first_name}},</p>
              <p style="margin: 0 0 24px; font-size: 18px; line-height: 1.8; color: #3d3d3d;">I wanted to check in and see how things are going. By now, you&#39;ve had a chance to explore, and I imagine you might have a few questions.</p>
              <p style="margin: 0 0 24px; font-size: 18px; line-height: 1.8; color: #3d3d3d;">Here&#39;s something I wish I knew when I was starting out:</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 28px;">
                <tr>
                  <td style="border-left: 3px solid #b08d57; padding: 16px 24px; background-color: #faf8f5; border-radius: 0 4px 4px 0;">
                    <p style="margin: 0; font-size: 17px; line-height: 1.7; color: #555; font-style: italic;">The best results come from consistency, not intensity. Small steps every day will take you further than a single sprint.</p>
                  </td>
                </tr>
              </table>
              <p style="margin: 0 0 32px; font-size: 18px; line-height: 1.8; color: #3d3d3d;">If you&#39;re feeling stuck, here&#39;s a resource that might help:</p>
              <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                  <td style="border: 2px solid #1a1a2e; border-radius: 4px;">
                    <a href="#" style="display: inline-block; padding: 14px 36px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', sans-serif; font-size: 13px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: #1a1a2e; text-decoration: none;">View the Guide</a>
                  </td>
                </tr>
              </table>
              <p style="margin: 32px 0 0; font-size: 18px; line-height: 1.8; color: #3d3d3d;">Talk soon,<br><span style="color: #1a1a2e; font-weight: 400;">Your Name</span></p>
            </td>
          </tr>
          <tr>
            <td style="padding: 28px 40px; text-align: center;">
              <p style="margin: 0 0 4px; font-size: 12px; color: #999; font-family: -apple-system, sans-serif;">Part of your welcome series &#183; Day 3 of 7</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #b08d57; text-decoration: underline; font-family: -apple-system, sans-serif;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Midnight Minimal',
				'description' => 'Ultra-clean dark template focused purely on typography and whitespace',
				'category'    => 'custom',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Message</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0a0a12; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0a0a12;">
    <tr>
      <td align="center" style="padding: 56px 16px;">
        <table role="presentation" width="520" cellpadding="0" cellspacing="0" style="max-width: 520px; width: 100%;">
          <tr>
            <td style="padding: 0 0 40px;">
              <p style="margin: 0 0 28px; font-size: 17px; line-height: 1.8; color: #c8cdd5;">Hi {{first_name}},</p>
              <p style="margin: 0 0 24px; font-size: 17px; line-height: 1.8; color: #c8cdd5;">Your message goes here. No distractions, no clutter. Just you and your reader, having a conversation.</p>
              <p style="margin: 0 0 24px; font-size: 17px; line-height: 1.8; color: #c8cdd5;">Sometimes the simplest format is the most powerful. Let your words do the work.</p>
              <p style="margin: 0; font-size: 17px; line-height: 1.8; color: #c8cdd5;">&#8212;<br><span style="color: #ffffff;">Your Name</span></p>
            </td>
          </tr>
          <tr>
            <td style="border-top: 1px solid rgba(255,255,255,0.06); padding-top: 24px;">
              <p style="margin: 0; font-size: 12px; color: #555e6e;">
                <a href="{{unsubscribe_url}}" style="color: #555e6e; text-decoration: underline;">Unsubscribe</a>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

			array(
				'name'        => 'Canvas Blank',
				'description' => 'Light minimal starter template with clean structure for custom builds',
				'category'    => 'custom',
				'content'     => '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8f9fa;">
    <tr>
      <td align="center" style="padding: 40px 16px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
          <tr>
            <td style="padding: 24px 0; text-align: center;">
              <p style="margin: 0; font-size: 14px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: #1a1a2e;">Your Brand</p>
            </td>
          </tr>
          <tr>
            <td style="background-color: #ffffff; border-radius: 8px; padding: 44px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
              <h1 style="margin: 0 0 24px; font-size: 24px; font-weight: 700; color: #1a1a2e; line-height: 1.3;">Your Headline Goes Here</h1>
              <p style="margin: 0 0 20px; font-size: 16px; line-height: 1.7; color: #4a5568;">Hello {{first_name}}, start writing your email content here. This template is designed as a clean starting point that you can customize to match your brand.</p>
              <p style="margin: 0 0 32px; font-size: 16px; line-height: 1.7; color: #4a5568;">Add sections, images, buttons, and more to build the perfect email for your audience.</p>
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="background-color: #1a1a2e; border-radius: 6px;">
                    <a href="#" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none;">Call to Action</a>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding: 28px 0; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #a0aec0;">&#169; 2026 Your Company. All rights reserved.</p>
              <a href="{{unsubscribe_url}}" style="font-size: 12px; color: #a0aec0; text-decoration: underline;">Unsubscribe</a>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>',
			),

		);
	}
}
