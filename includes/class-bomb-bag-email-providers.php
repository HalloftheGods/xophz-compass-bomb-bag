<?php

class Xophz_Compass_Bomb_Bag_Email_Providers {

	public static function send( $to, $subject, $content, $headers = array() ) {
		$settings = get_option( 'bomb_bag_settings', array() );
		$provider  = $settings['email_provider'] ?? 'wordpress';

		switch ( $provider ) {
			case 'sendgrid':
				return self::send_via_sendgrid( $to, $subject, $content, $settings );
			case 'mailgun':
				return self::send_via_mailgun( $to, $subject, $content, $settings );
			case 'smtp':
				return self::send_via_smtp( $to, $subject, $content, $settings, $headers );
			default:
				return self::send_via_wordpress( $to, $subject, $content, $settings, $headers );
		}
	}

	private static function send_via_wordpress( $to, $subject, $content, $settings, $headers = array() ) {
		$from_name  = $settings['from_name'] ?? get_bloginfo( 'name' );
		$from_email = $settings['from_email'] ?? get_option( 'admin_email' );

		$default_headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>'
		);

		$merged_headers = array_merge( $default_headers, $headers );
		return wp_mail( $to, $subject, $content, $merged_headers );
	}

	private static function send_via_sendgrid( $to, $subject, $content, $settings ) {
		$api_key = $settings['sendgrid_api_key'] ?? '';
		if ( empty( $api_key ) ) {
			return false;
		}

		$from_name  = $settings['from_name'] ?? get_bloginfo( 'name' );
		$from_email = $settings['from_email'] ?? get_option( 'admin_email' );

		$payload = array(
			'personalizations' => array(
				array(
					'to' => array( array( 'email' => $to ) )
				)
			),
			'from'    => array(
				'email' => $from_email,
				'name'  => $from_name
			),
			'subject' => $subject,
			'content' => array(
				array(
					'type'  => 'text/html',
					'value' => $content
				)
			)
		);

		$response = wp_remote_post( 'https://api.sendgrid.com/v3/mail/send', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json'
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 30
		));

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}

	private static function send_via_mailgun( $to, $subject, $content, $settings ) {
		$api_key = $settings['mailgun_api_key'] ?? '';
		$domain  = $settings['mailgun_domain'] ?? '';

		if ( empty( $api_key ) || empty( $domain ) ) {
			return false;
		}

		$from_name  = $settings['from_name'] ?? get_bloginfo( 'name' );
		$from_email = $settings['from_email'] ?? get_option( 'admin_email' );

		$response = wp_remote_post( "https://api.mailgun.net/v3/{$domain}/messages", array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key )
			),
			'body'    => array(
				'from'    => $from_name . ' <' . $from_email . '>',
				'to'      => $to,
				'subject' => $subject,
				'html'    => $content
			),
			'timeout' => 30
		));

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}

	private static function send_via_smtp( $to, $subject, $content, $settings, $headers = array() ) {
		$host       = $settings['smtp_host'] ?? '';
		$port       = $settings['smtp_port'] ?? 587;
		$username   = $settings['smtp_username'] ?? '';
		$password   = $settings['smtp_password'] ?? '';
		$encryption = $settings['smtp_encryption'] ?? 'tls';

		if ( empty( $host ) ) {
			return false;
		}

		add_action( 'phpmailer_init', function( $phpmailer ) use ( $host, $port, $username, $password, $encryption ) {
			$phpmailer->isSMTP();
			$phpmailer->Host       = $host;
			$phpmailer->Port       = $port;
			$phpmailer->SMTPAuth   = ! empty( $username );
			$phpmailer->Username   = $username;
			$phpmailer->Password   = $password;
			$phpmailer->SMTPSecure = $encryption === 'none' ? '' : $encryption;
		});

		$from_name  = $settings['from_name'] ?? get_bloginfo( 'name' );
		$from_email = $settings['from_email'] ?? get_option( 'admin_email' );

		$default_headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $from_name . ' <' . $from_email . '>'
		);

		$merged_headers = array_merge( $default_headers, $headers );
		return wp_mail( $to, $subject, $content, $merged_headers );
	}
}
