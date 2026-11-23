<?php
/**
 * Bomb Bag default email template library.
 *
 * Every default design is generated from one mobile-first shell (fluid 600px
 * container, stacking columns under 620px, bulletproof button, hidden
 * preheader) plus a theme and a list of content blocks. Keep the placeholders
 * {MESSAGE} and {{unsubscribe_url}} in every design: the composer and the
 * sender both rely on them.
 *
 * @package Xophz_Compass_Bomb_Bag
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bomb_Bag_Template_Library {

	/**
	 * All default templates: name, description, category, content.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function all() {
		$out = array();
		foreach ( self::specs() as $spec ) {
			$out[] = array(
				'name'        => $spec['name'],
				'description' => $spec['description'],
				'category'    => $spec['category'],
				'content'     => self::render( $spec ),
			);
		}
		return $out;
	}

	/**
	 * Theme tokens shared by the renderer.
	 */
	private static function theme( array $over ) {
		return array_merge(
			array(
				'page'    => '#0b0f19',
				'card'    => '#131a2b',
				'panel'   => '#1a2338',
				'text'    => '#e8edf7',
				'muted'   => '#94a0b8',
				'line'    => '#26314a',
				'accent'  => '#62c9ff',
				'onaccent' => '#06101c',
				'hero'    => 'linear-gradient(160deg,#16213e 0%,#0f1730 100%)',
				'font'    => "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif",
				'radius'  => '20px',
				'align'   => 'center',
				'scheme'  => 'dark',
			),
			$over
		);
	}

	private static function specs() {
		$lorem_story = 'Share your top story or announcement here. Give readers a clear reason to tap through.';

		return array(
			array(
				'name'        => 'Obsidian Digest',
				'description' => 'Dark newsletter with a featured story, quick updates and one clear call to action',
				'category'    => 'newsletter',
				'preheader'   => 'Your weekly digest is here',
				'theme'       => self::theme( array() ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Weekly digest',
				'title'       => 'Your Weekly Digest',
				'sub'         => 'Fresh insights, delivered.',
				'blocks'      => array(
					array( 'message' ),
					array( 'callout', 'Featured story', $lorem_story ),
					array( 'list', 'Quick updates', array( 'First update or news item', 'Second update or news item', 'Third update or news item' ) ),
					array( 'button', 'Read the full issue' ),
				),
			),
			array(
				'name'        => 'Ivory Editorial',
				'description' => 'Light, serif-led newsletter with generous whitespace and an editorial feel',
				'category'    => 'newsletter',
				'preheader'   => 'This issue: stories worth your time',
				'theme'       => self::theme( array(
					'page' => '#f3efe7', 'card' => '#fffdf8', 'panel' => '#f6f0e3', 'text' => '#25211b', 'muted' => '#7a7164',
					'line' => '#e5dccb', 'accent' => '#b4572f', 'onaccent' => '#ffffff', 'hero' => '#fffdf8', 'scheme' => 'light',
					'font' => "Georgia,'Times New Roman',serif", 'align' => 'left', 'radius' => '14px',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'The Issue',
				'title'       => 'Stories worth your time',
				'sub'         => 'A short, considered read for the week ahead.',
				'blocks'      => array(
					array( 'message' ),
					array( 'callout', 'In this issue', $lorem_story ),
					array( 'list', 'Also worth a look', array( 'A second article or link', 'A third article or link' ) ),
					array( 'button', 'Continue reading' ),
				),
			),
			array(
				'name'        => 'Neon Welcome',
				'description' => 'High-energy dark welcome with a neon accent and three onboarding steps',
				'category'    => 'welcome',
				'preheader'   => 'You are in. Here is how to get started',
				'theme'       => self::theme( array(
					'page' => '#07090d', 'card' => '#0e1218', 'panel' => '#151b24', 'accent' => '#b6ff3b', 'onaccent' => '#0b1200',
					'hero' => 'linear-gradient(160deg,#0f1a05 0%,#0e1218 70%)', 'line' => '#222b38',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Welcome aboard',
				'title'       => 'You are in.',
				'sub'         => 'Three quick steps to get the most out of day one.',
				'blocks'      => array(
					array( 'message' ),
					array( 'steps', array(
						array( 'Set up your profile', 'Tell us what you care about so we send the right things.' ),
						array( 'Explore the essentials', 'Start with the guides most people find useful first.' ),
						array( 'Say hello', 'Reply to this email. A real person reads every one.' ),
					) ),
					array( 'button', 'Get started' ),
				),
			),
			array(
				'name'        => 'Warm Handshake',
				'description' => 'Friendly light welcome with warm tones and a personal note',
				'category'    => 'welcome',
				'preheader'   => 'So glad you are here',
				'theme'       => self::theme( array(
					'page' => '#fbeee1', 'card' => '#ffffff', 'panel' => '#fff4e8', 'text' => '#33261c', 'muted' => '#86705f',
					'line' => '#f1dcc6', 'accent' => '#e8742c', 'onaccent' => '#ffffff', 'hero' => 'linear-gradient(160deg,#ffe3c8 0%,#ffd0a8 100%)',
					'scheme' => 'light', 'radius' => '24px',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Hello, friend',
				'title'       => 'So glad you are here',
				'sub'         => 'Pull up a chair. Here is what to expect from us.',
				'blocks'      => array(
					array( 'message' ),
					array( 'callout', 'A note from us', 'Write a short, personal welcome here. Mention what you send, how often, and why it matters to you.' ),
					array( 'list', 'What to expect', array( 'Helpful tips, never noise', 'Early access to news and offers', 'A reply when you write back' ) ),
					array( 'button', 'Say hello' ),
				),
			),
			array(
				'name'        => 'Flash Sale',
				'description' => 'Bold promotion with an urgency banner, a big offer and three reasons to buy',
				'category'    => 'promotional',
				'preheader'   => 'Limited time. Ends Sunday',
				'theme'       => self::theme( array(
					'page' => '#0a0e1a', 'card' => '#111830', 'panel' => '#18213f', 'accent' => '#ff6a3d', 'onaccent' => '#ffffff',
					'hero' => 'linear-gradient(160deg,#2a1530 0%,#111830 70%)',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Limited time offer',
				'title'       => '40% OFF',
				'sub'         => 'Everything. No exclusions. Ends Sunday.',
				'blocks'      => array(
					array( 'message' ),
					array( 'features', array(
						array( 'Free shipping', 'On all orders' ),
						array( 'Secure checkout', '256-bit encrypted' ),
						array( 'Easy returns', '30-day guarantee' ),
					) ),
					array( 'button', 'Shop the sale' ),
				),
			),
			array(
				'name'        => 'Product Launch',
				'description' => 'Premium announcement with a gradient hero and a four-up feature grid',
				'category'    => 'promotional',
				'preheader'   => 'Something new is here',
				'theme'       => self::theme( array(
					'page' => '#0d0b1a', 'card' => '#16132b', 'panel' => '#1f1b3d', 'accent' => '#a78bfa', 'onaccent' => '#120a2b',
					'hero' => 'linear-gradient(135deg,#5b3df5 0%,#9a4dff 55%,#e056c1 100%)', 'line' => '#2c2750',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Introducing',
				'title'       => 'Something Amazing',
				'sub'         => 'Meet the new thing you have been waiting for.',
				'heroText'    => '#ffffff',
				'blocks'      => array(
					array( 'message' ),
					array( 'features', array(
						array( 'Faster', 'Twice the speed' ),
						array( 'Simpler', 'Fewer steps' ),
						array( 'Smarter', 'Learns as you go' ),
						array( 'Safer', 'Private by default' ),
					), 2 ),
					array( 'button', 'See what is new' ),
				),
			),
			array(
				'name'        => 'Order Confirmation',
				'description' => 'Clean transactional receipt with an order summary, line items and a total',
				'category'    => 'transactional',
				'preheader'   => 'We got your order',
				'theme'       => self::theme( array(
					'page' => '#eef2f8', 'card' => '#ffffff', 'panel' => '#f4f7fc', 'text' => '#142033', 'muted' => '#5f6f87',
					'line' => '#dde5f0', 'accent' => '#2563eb', 'onaccent' => '#ffffff', 'hero' => '#ffffff', 'scheme' => 'light',
					'align' => 'left', 'radius' => '16px',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Order confirmed',
				'title'       => 'Thanks for your order',
				'sub'         => 'We will email you again when it ships.',
				'blocks'      => array(
					array( 'message' ),
					array( 'rows', 'Order summary', array(
						array( 'Order number', '#0000' ),
						array( 'Item', 'Product name x 1' ),
						array( 'Shipping', 'Free' ),
						array( 'Total', '$0.00', true ),
					) ),
					array( 'button', 'View your order' ),
				),
			),
			array(
				'name'        => 'System Notification',
				'description' => 'Minimal dark alert with a status pill and a short details table',
				'category'    => 'transactional',
				'preheader'   => 'An update on your account',
				'theme'       => self::theme( array(
					'page' => '#080a0f', 'card' => '#0f1319', 'panel' => '#161c25', 'accent' => '#34d399', 'onaccent' => '#04130d',
					'hero' => '#0f1319', 'line' => '#1f2733', 'align' => 'left', 'radius' => '16px',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Status: OK',
				'title'       => 'Everything is working',
				'sub'         => 'This is an automated message about your account.',
				'blocks'      => array(
					array( 'message' ),
					array( 'rows', 'Details', array(
						array( 'Event', 'What happened' ),
						array( 'Time', 'Date and time' ),
						array( 'Reference', 'ID-0000' ),
					) ),
					array( 'button', 'Open dashboard' ),
				),
			),
			array(
				'name'        => 'Knowledge Series',
				'description' => 'Educational drip email with a lesson counter, progress bar and key takeaways',
				'category'    => 'drip',
				'preheader'   => 'Lesson 3 is ready',
				'theme'       => self::theme( array(
					'page' => '#0c0e1f', 'card' => '#141735', 'panel' => '#1d2148', 'accent' => '#818cf8', 'onaccent' => '#0b0d29',
					'hero' => 'linear-gradient(160deg,#1e2260 0%,#141735 75%)', 'line' => '#272c5a',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Lesson 3 of 8',
				'title'       => 'Today\'s lesson title',
				'sub'         => 'A five-minute read you can use today.',
				'blocks'      => array(
					array( 'progress', 3, 8 ),
					array( 'message' ),
					array( 'steps', array(
						array( 'Key idea', 'State the one thing readers should remember.' ),
						array( 'Try it', 'Give them a small action to do right now.' ),
					) ),
					array( 'button', 'Read the full lesson' ),
				),
			),
			array(
				'name'        => 'Nurture Touch',
				'description' => 'Soft, personal drip email for building a relationship, with a P.S. at the end',
				'category'    => 'drip',
				'preheader'   => 'Just checking in',
				'theme'       => self::theme( array(
					'page' => '#f8eef0', 'card' => '#ffffff', 'panel' => '#fdf3f5', 'text' => '#3a2a2f', 'muted' => '#8c7076',
					'line' => '#f0dade', 'accent' => '#d9728a', 'onaccent' => '#ffffff', 'hero' => '#ffffff', 'scheme' => 'light',
					'font' => "Georgia,'Times New Roman',serif", 'align' => 'left', 'radius' => '22px',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => 'Just checking in',
				'title'       => 'Hi there,',
				'sub'         => '',
				'blocks'      => array(
					array( 'message' ),
					array( 'callout', 'Something I wanted to share', 'Tell a short story or give one useful idea. Keep it warm, specific and in your own voice.' ),
					array( 'button', 'Reply to me' ),
					array( 'ps', 'P.S. Write one helpful line here. People read the P.S. first.' ),
				),
			),
			array(
				'name'        => 'Midnight Minimal',
				'description' => 'Ultra-clean dark layout built on typography and whitespace',
				'category'    => 'custom',
				'preheader'   => '',
				'theme'       => self::theme( array(
					'page' => '#05060a', 'card' => '#05060a', 'panel' => '#0d1018', 'accent' => '#e8edf7', 'onaccent' => '#05060a',
					'hero' => '#05060a', 'line' => '#1a1f2b', 'align' => 'left', 'radius' => '0px',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => '',
				'title'       => 'A quiet headline',
				'sub'         => '',
				'blocks'      => array(
					array( 'message' ),
					array( 'button', 'Continue' ),
				),
			),
			array(
				'name'        => 'Canvas Blank',
				'description' => 'Light, clean starter with a header, body, button and footer for custom builds',
				'category'    => 'custom',
				'preheader'   => '',
				'theme'       => self::theme( array(
					'page' => '#f4f6f9', 'card' => '#ffffff', 'panel' => '#f4f6f9', 'text' => '#1b2230', 'muted' => '#6b7688',
					'line' => '#e4e8ef', 'accent' => '#111827', 'onaccent' => '#ffffff', 'hero' => '#ffffff', 'scheme' => 'light',
					'align' => 'left', 'radius' => '16px',
				) ),
				'brand'       => 'YOUR BRAND',
				'eyebrow'     => '',
				'title'       => 'Your headline goes here',
				'sub'         => 'Hello Firstname, start writing your email here.',
				'blocks'      => array(
					array( 'message' ),
					array( 'button', 'Call to action' ),
				),
			),
		);
	}

	/* ---------------------------------------------------------------- */
	/* Renderer                                                         */
	/* ---------------------------------------------------------------- */

	private static function render( array $spec ) {
		$t     = $spec['theme'];
		$al    = $t['align'];
		$hero  = isset( $spec['heroText'] ) ? $spec['heroText'] : $t['text'];
		$muted = isset( $spec['heroText'] ) ? 'rgba(255,255,255,0.82)' : $t['muted'];

		$css  = 'body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}';
		$css .= 'table{border-collapse:collapse}img{border:0;max-width:100%}';
		$css .= '@media only screen and (max-width:620px){';
		$css .= '.px{padding-left:22px!important;padding-right:22px!important}';
		$css .= '.outer{padding:16px 10px!important}';
		$css .= '.h1{font-size:30px!important;line-height:1.15!important}';
		$css .= '.col{display:block!important;width:100%!important;padding:0 0 12px 0!important}';
		$css .= '.btn a{display:block!important;text-align:center!important}';
		$css .= '.btn{width:100%!important}}';

		$h  = '<!DOCTYPE html>' . "\n" . '<html lang="en">' . "\n" . '<head>' . "\n";
		$h .= '<meta charset="utf-8">' . "\n";
		$h .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
		$h .= '<meta name="color-scheme" content="' . $t['scheme'] . '">' . "\n";
		$h .= '<title>' . $spec['title'] . '</title>' . "\n";
		$h .= '<style>' . $css . '</style>' . "\n</head>\n";
		$h .= '<body style="margin:0;padding:0;background:' . $t['page'] . ';font-family:' . $t['font'] . ';color:' . $t['text'] . ';">' . "\n";

		if ( $spec['preheader'] ) {
			$h .= '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">' . $spec['preheader'] . '</div>' . "\n";
		}

		$h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $t['page'] . ';"><tr>';
		$h .= '<td align="center" class="outer" style="padding:32px 16px;">';
		$h .= '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:' . $t['card'] . ';border-radius:' . $t['radius'] . ';overflow:hidden;border:1px solid ' . $t['line'] . ';">';

		// Brand bar.
		$h .= '<tr><td class="px" align="' . $al . '" style="padding:22px 36px 0;background:' . $t['hero'] . ';">';
		$h .= '<span style="font-size:12px;font-weight:800;letter-spacing:3px;color:' . $t['accent'] . ';">' . $spec['brand'] . '</span></td></tr>';

		// Hero.
		$h .= '<tr><td class="px" align="' . $al . '" style="padding:28px 36px 40px;background:' . $t['hero'] . ';">';
		if ( $spec['eyebrow'] ) {
			$h .= '<p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:' . $t['accent'] . ';">' . $spec['eyebrow'] . '</p>';
		}
		$h .= '<h1 class="h1" style="margin:0;font-size:38px;line-height:1.1;font-weight:800;letter-spacing:-0.5px;color:' . $hero . ';">' . $spec['title'] . '</h1>';
		if ( $spec['sub'] ) {
			$h .= '<p style="margin:14px 0 0;font-size:16px;line-height:1.55;color:' . $muted . ';">' . $spec['sub'] . '</p>';
		}
		$h .= '</td></tr>';

		// Body.
		$h .= '<tr><td class="px" style="padding:36px 36px 8px;">';
		foreach ( $spec['blocks'] as $block ) {
			$h .= self::block( $block, $t );
		}
		$h .= '</td></tr>';

		// Footer.
		$h .= '<tr><td class="px" align="center" style="padding:26px 36px 34px;border-top:1px solid ' . $t['line'] . ';">';
		$h .= '<p style="margin:0 0 8px;font-size:12px;line-height:1.6;color:' . $t['muted'] . ';">You are receiving this because you subscribed.</p>';
		$h .= '<a href="{{unsubscribe_url}}" style="font-size:12px;color:' . $t['accent'] . ';text-decoration:underline;">Unsubscribe</a>';
		$h .= '</td></tr>';

		$h .= '</table></td></tr></table>' . "\n</body>\n</html>";

		return $h;
	}

	private static function block( array $b, array $t ) {
		$type = $b[0];
		$gap  = 'margin:0 0 28px;';

		switch ( $type ) {
			case 'message':
				return '<div style="' . $gap . 'font-size:16px;line-height:1.7;color:' . $t['text'] . ';">{MESSAGE}</div>';

			case 'callout':
				return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $gap . '"><tr><td style="padding:22px 24px;background:' . $t['panel'] . ';border-left:4px solid ' . $t['accent'] . ';border-radius:12px;">'
					. '<h3 style="margin:0 0 8px;font-size:18px;color:' . $t['text'] . ';">' . $b[1] . '</h3>'
					. '<p style="margin:0;font-size:15px;line-height:1.65;color:' . $t['muted'] . ';">' . $b[2] . '</p></td></tr></table>';

			case 'list':
				$items = '';
				foreach ( $b[2] as $item ) {
					$items .= '<p style="margin:0 0 10px;font-size:15px;line-height:1.6;color:' . $t['muted'] . ';"><span style="color:' . $t['accent'] . ';font-weight:800;">&#8226;</span>&nbsp; ' . $item . '</p>';
				}
				return '<div style="' . $gap . '"><h3 style="margin:0 0 12px;font-size:18px;color:' . $t['text'] . ';">' . $b[1] . '</h3>' . $items . '</div>';

			case 'steps':
				$out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $gap . '">';
				foreach ( $b[1] as $i => $step ) {
					$out .= '<tr><td width="44" valign="top" style="padding:0 0 18px;"><div style="width:32px;height:32px;line-height:32px;text-align:center;border-radius:50%;background:' . $t['accent'] . ';color:' . $t['onaccent'] . ';font-weight:800;font-size:14px;">' . ( $i + 1 ) . '</div></td>'
						. '<td valign="top" style="padding:0 0 18px;"><p style="margin:0 0 4px;font-size:16px;font-weight:700;color:' . $t['text'] . ';">' . $step[0] . '</p>'
						. '<p style="margin:0;font-size:14px;line-height:1.6;color:' . $t['muted'] . ';">' . $step[1] . '</p></td></tr>';
				}
				return $out . '</table>';

			case 'features':
				$per   = isset( $b[2] ) ? (int) $b[2] : count( $b[1] );
				$width = (string) floor( 100 / $per ) . '%';
				$out   = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $gap . '">';
				foreach ( array_chunk( $b[1], $per ) as $row ) {
					$out .= '<tr>';
					foreach ( $row as $f ) {
						$out .= '<td class="col" width="' . $width . '" valign="top" style="padding:0 6px 12px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:18px 12px;background:' . $t['panel'] . ';border-radius:14px;">'
							. '<p style="margin:0 0 4px;font-size:15px;font-weight:700;color:' . $t['text'] . ';">' . $f[0] . '</p>'
							. '<p style="margin:0;font-size:13px;color:' . $t['muted'] . ';">' . $f[1] . '</p></td></tr></table></td>';
					}
					$out .= '</tr>';
				}
				return $out . '</table>';

			case 'rows':
				$out = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $gap . 'background:' . $t['panel'] . ';border-radius:14px;"><tr><td style="padding:20px 22px 8px;"><p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:' . $t['muted'] . ';">' . $b[1] . '</p></td></tr>';
				foreach ( $b[2] as $row ) {
					$bold = ! empty( $row[2] );
					$out .= '<tr><td style="padding:0 22px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
						. '<td style="padding:12px 0;border-top:1px solid ' . $t['line'] . ';font-size:14px;color:' . $t['muted'] . ';">' . $row[0] . '</td>'
						. '<td align="right" style="padding:12px 0;border-top:1px solid ' . $t['line'] . ';font-size:' . ( $bold ? '18px' : '14px' ) . ';font-weight:' . ( $bold ? '800' : '600' ) . ';color:' . $t['text'] . ';">' . $row[1] . '</td>'
						. '</tr></table></td></tr>';
				}
				return $out . '<tr><td style="height:10px;"></td></tr></table>';

			case 'progress':
				$pct = max( 0, min( 100, (int) round( $b[1] / $b[2] * 100 ) ) );
				return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $gap . '"><tr><td style="background:' . $t['panel'] . ';border-radius:99px;height:8px;line-height:8px;">'
					. '<table role="presentation" width="' . $pct . '%" cellpadding="0" cellspacing="0"><tr><td style="background:' . $t['accent'] . ';border-radius:99px;height:8px;line-height:8px;font-size:0;">&nbsp;</td></tr></table></td></tr></table>';

			case 'ps':
				return '<p style="margin:8px 0 28px;font-size:15px;line-height:1.65;font-style:italic;color:' . $t['muted'] . ';">' . $b[1] . '</p>';

			case 'button':
				return '<table role="presentation" cellpadding="0" cellspacing="0" class="btn" style="margin:4px auto 32px;"><tr><td align="center" bgcolor="' . $t['accent'] . '" style="border-radius:12px;background:' . $t['accent'] . ';">'
					. '<a href="#" style="display:inline-block;padding:16px 36px;font-size:16px;font-weight:700;color:' . $t['onaccent'] . ';text-decoration:none;border-radius:12px;">' . $b[1] . '</a></td></tr></table>';
		}

		return '';
	}
}
