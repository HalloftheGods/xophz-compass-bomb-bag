=== Xophz Bomb Bag News Drip ===
Contributors: hallofthegods, xophz
Donate link: https://worldwidewebwork.com/
Tags: email marketing, drip campaigns, newsletters, email blasts, lead scoring, suppression lists
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 26.8.29-1423
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send email blasts, schedule automated Newsletter drip campaigns, manage subscriber lists, and track campaign analytics with the COMPASS suite.

== Description ==

**Xophz Bomb Bag News Drip** is an email marketing and automated drip sequence system engineered for WordPress and the COMPASS platform. It provides broadcast campaign management, subscriber list segmentation, automated drip sequences, real-time open/click engagement analytics, reusable visual email templates, and suppression list filtering.

### Key Features

* **Broadcast Campaigns**: Compose rich HTML emails, test deliverability with 1-click test sends, queue immediate blasts, or schedule campaigns for future automated dispatch.
* **Automated Drip Sequences**: Build multi-step nurture funnels with custom trigger conditions (list subscription, manual, tag assignment), step-by-step delays, and sequence-level engagement analytics.
* **Audience Segmentation & Lead Scoring**: Group contacts with custom lists, visual tag chips, and lead temperature indicators (Hot, Warm, Cold).
* **Suppression Lists**: Protect deliverability by designating lists as exclusion/suppression targets to automatically bypass unsubscribed or sensitive addresses.
* **List Merging & Duplication**: Easily consolidate subscriber lists with optional source deletion or clone audiences with subscriber cloning.
* **Batch Operations**: Multi-row selection bar supporting batch tagging, list assignment/removal, status updates, client-side CSV export, and bulk deletion.
* **Flexible Import & Sync**: CSV import with visual column header mapping, custom field detection, and WordPress user directory synchronization.
* **Multi-Provider Dispatch Engine**: Native integration with WordPress `wp_mail()`, SendGrid HTTP API, Mailgun HTTP API, or custom SMTP configurations.
* **Real-time Analytics**: Built-in 1x1 transparent tracking pixel for open tracking, link redirect rewriting for click tracking, and compliant one-click unsubscribe links.

== Installation ==

1. Upload the `xophz-compass-bomb-bag` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress or via WP-CLI (`wp plugin activate xophz-compass-bomb-bag`).
3. Configure your outbound email delivery credentials under **COMPASS > Bomb Bag > Settings** (or via WordPress Connectors API).
4. Access the Bomb Bag dashboard directly within the COMPASS SPA interface or via REST API.

== Frequently Asked Questions ==

= How are scheduled campaigns and drip emails dispatched? =

Bomb Bag registers background WP-Cron intervals. A 5-minute task checks for scheduled broadcast campaigns and queues them for delivery, while an hourly job evaluates pending drip sequence steps and dispatches due emails in configurable batches (default: 50 emails per batch).

= Which email delivery providers are supported? =

Bomb Bag supports:
1. Standard WordPress Mail (`wp_mail`)
2. SendGrid (HTTP API with Bearer Token)
3. Mailgun (HTTP API with Basic Auth and domain routing)
4. Custom SMTP via PHPMailer hook

= How do suppression lists work? =

When creating or editing a list, check the "Suppression List" option. Any subscribers present on an active suppression list are automatically filtered out during campaign batch queuing, ensuring they never receive broadcast emails.

== REST API Endpoints ==

Bomb Bag provides a REST API namespace under `/wp-json/xophz-compass/v1/bomb-bag/`:

* `GET /stats` - Overall statistics and metrics
* `GET /campaigns`, `POST /campaigns` - List and create campaigns
* `POST /campaigns/:id/send` - Queue campaign for immediate batch sending
* `POST /campaigns/:id/schedule` - Schedule campaign for future dispatch
* `GET /subscribers`, `POST /subscribers` - Subscriber management
* `POST /subscribers/import` - CSV subscriber ingestion
* `GET /lists`, `POST /lists` - List management
* `POST /lists/:id/merge`, `POST /lists/:id/duplicate` - List merge and clone
* `GET /drips`, `POST /drips` - Drip sequence pipelines
* `GET /templates`, `POST /templates` - Reusable email layout templates

== Changelog ==

= 26.8.29-1423 =
* Added multi-row subscriber selection with floating glassmorphic bulk action bar for batch list assignment, tag manipulation, status updating, and CSV export.
* Added List Merging (with optional source cleanup) and List Duplication tools.
* Added Suppression List support (`is_suppression`) across database schema, REST API, list editor, and campaign queueing engine.
* Added CSV column mapping interface with automatic header detection for subscriber imports.
* Added automatic database migration check on plugin initialization.
* Added Lead Scoring indicators (Hot, Warm, Cold) and instant list filter chips to subscriber directory.

= 1.0.0 =
* Initial release of Xophz Bomb Bag News Drip system for COMPASS.