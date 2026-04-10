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
			custom_fields TEXT,
			subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			unsubscribed_at DATETIME DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY email (email),
			KEY status (status)
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
			drip_step_id BIGINT(20) UNSIGNED DEFAULT NULL,
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
			KEY drip_step_id (drip_step_id),
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

		self::seed_default_list();
		self::seed_default_templates();
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

		$newsletter_template = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 0; background: #f5f5f5;">
  <div style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 40px 20px; text-align: center;">
    <h1 style="color: #fff; margin: 0; font-size: 28px;">Your Newsletter</h1>
  </div>
  <div style="background: #fff; padding: 32px 24px;">
    <p>Hello {{first_name}},</p>
    <p>Your content goes here. Share your latest updates, stories, and insights.</p>
    <p style="text-align: center; margin: 32px 0;">
      <a href="#" style="background: #667eea; color: #fff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600;">Read More</a>
    </p>
    <p>Best regards,<br>Your Team</p>
  </div>
  <div style="padding: 24px; text-align: center; font-size: 12px; color: #999;">
    <p><a href="{{unsubscribe_url}}" style="color: #999;">Unsubscribe</a></p>
  </div>
</body>
</html>';

		$welcome_template = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 0; background: #f0f0f0;">
  <div style="background: #1a1a2e; padding: 48px 24px; text-align: center;">
    <h1 style="color: #ed55a9; margin: 0; font-size: 32px;">Welcome aboard!</h1>
    <p style="color: rgba(255,255,255,0.7); margin-top: 8px;">We&#39;re thrilled you&#39;re here, {{first_name}}.</p>
  </div>
  <div style="background: #fff; padding: 32px 24px;">
    <h2 style="margin-top: 0;">What to expect</h2>
    <p>Here&#39;s what you&#39;ll get as a subscriber:</p>
    <ul style="padding-left: 20px;">
      <li>Weekly insights and tips</li>
      <li>Exclusive content and offers</li>
      <li>Early access to new features</li>
    </ul>
    <p style="text-align: center; margin: 32px 0;">
      <a href="#" style="background: #ed55a9; color: #fff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600;">Get Started</a>
    </p>
  </div>
  <div style="padding: 24px; text-align: center; font-size: 12px; color: #999;">
    <p><a href="{{unsubscribe_url}}" style="color: #999;">Unsubscribe</a></p>
  </div>
</body>
</html>';

		$minimal_template = '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Georgia, serif; line-height: 1.8; color: #2c3e50; max-width: 560px; margin: 0 auto; padding: 40px 20px;">
  <p>Hi {{first_name}},</p>
  <p>Your message goes here. Clean, simple, focused on the content.</p>
  <p>—<br>Your Name</p>
  <hr style="border: none; border-top: 1px solid #eee; margin: 32px 0;">
  <p style="font-size: 12px; color: #999;">
    <a href="{{unsubscribe_url}}" style="color: #999;">Unsubscribe</a>
  </p>
</body>
</html>';

		$wpdb->insert( $table, array(
			'name'        => 'Modern Newsletter',
			'description' => 'Clean gradient header with CTA button',
			'category'    => 'newsletter',
			'content'     => $newsletter_template,
			'is_default'  => 1,
		));

		$wpdb->insert( $table, array(
			'name'        => 'Welcome Series',
			'description' => 'Dark header welcome email for new subscribers',
			'category'    => 'welcome',
			'content'     => $welcome_template,
			'is_default'  => 1,
		));

		$wpdb->insert( $table, array(
			'name'        => 'Minimal Text',
			'description' => 'Simple text-only layout with serif font',
			'category'    => 'newsletter',
			'content'     => $minimal_template,
			'is_default'  => 1,
		));
	}
}
