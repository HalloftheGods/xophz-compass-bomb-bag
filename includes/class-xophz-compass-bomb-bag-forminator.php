<?php

/**
 * Xophz COMPASS - Bomb Bag Forminator Integration
 * 
 * Captures Forminator form submissions and automatically maps them to Bomb Bag subscribers and lists.
 */
class Xophz_Compass_Bomb_Bag_Forminator {

	public function init() {
		// Hook into Forminator submission before fields are set to database
		add_action( 'forminator_custom_form_submit_before_set_fields', array( $this, 'capture_submission' ), 10, 3 );
	}

	/**
	 * Intercept Forminator form submissions.
	 *
	 * @param Forminator_Form_Entry_Model $entry
	 * @param int $form_id
	 * @param array $field_data_array
	 */
	public function capture_submission( $entry, $form_id, $field_data_array ) {
		$email      = '';
		$first_name = '';
		$last_name  = '';
		$list_id    = 0; // Default to 0, will auto-resolve/create if empty

		// Parse field data
		foreach ( $field_data_array as $field ) {
			$name  = isset( $field['name'] ) ? $field['name'] : '';
			$value = isset( $field['value'] ) ? $field['value'] : '';

			if ( empty( $value ) ) {
				continue;
			}

			// Identify Email (usually email-1, email-2, etc.)
			if ( strpos( $name, 'email-' ) === 0 ) {
				$email = sanitize_email( $value );
			}

			// Identify Name (usually name-1 or text-1)
			if ( strpos( $name, 'name-' ) === 0 ) {
				// Forminator name fields can be arrays or strings
				if ( is_array( $value ) ) {
					$first_name = isset( $value['first-name'] ) ? sanitize_text_field( $value['first-name'] ) : '';
					$last_name  = isset( $value['last-name'] ) ? sanitize_text_field( $value['last-name'] ) : '';
				} else {
					// Fallback if it's a simple name string
					$parts      = explode( ' ', sanitize_text_field( $value ), 2 );
					$first_name = $parts[0];
					$last_name  = isset( $parts[1] ) ? $parts[1] : '';
				}
			}

			// Check for hidden field mapping to Bomb Bag List ID
			if ( strpos( $name, 'hidden-' ) === 0 || $name === 'bomb_bag_list_id' ) {
				if ( is_numeric( $value ) && strpos( strtolower( $field['field_array']['field_label'] ?? '' ), 'bomb bag' ) !== false ) {
					$list_id = absint( $value );
				} elseif ( $name === 'bomb_bag_list_id' || (isset($field['field_array']['custom_value']) && $field['field_array']['custom_value'] === 'bomb_bag_list_id') ) {
					$list_id = absint( $value );
				}
			}
		}

		// Check for specific hidden field named 'bomb_bag_list_id' in case we missed it
		if ( ! $list_id ) {
			foreach ( $field_data_array as $field ) {
				if ( isset($field['name']) && strpos( strtolower( $field['name'] ), 'bomb_bag_list' ) !== false ) {
					$list_id = absint( $field['value'] );
				}
			}
		}

		// --- GDPR / COMPLIANCE CONSENT CHECK ---
		$requires_consent = false;
		$has_consent      = false;

		// Check if the form model itself has a consent field (in case it was submitted unchecked and omitted from field_data)
		if ( class_exists( 'Forminator_Base_Form_Model' ) ) {
			$model = Forminator_Base_Form_Model::get_model( $form_id );
			if ( $model instanceof Forminator_Form_Model ) {
				$fields = $model->get_fields();
				foreach ( $fields as $f ) {
					$f_name = isset( $f->slug ) ? strtolower( $f->slug ) : '';
					$f_class = isset( $f->raw['custom-class'] ) ? strtolower( $f->raw['custom-class'] ) : '';
					if ( strpos( $f_name, 'bomb_bag_consent' ) !== false || strpos( $f_class, 'bomb-bag-consent' ) !== false || strpos( $f_class, 'bomb_bag_consent' ) !== false ) {
						$requires_consent = true;
						break;
					}
				}
			}
		}

		// Look through submitted data for the consent field
		foreach ( $field_data_array as $field ) {
			$name  = isset( $field['name'] ) ? strtolower( $field['name'] ) : '';
			$value = isset( $field['value'] ) ? $field['value'] : '';
			$custom_class = isset( $field['field_array']['custom-class'] ) ? strtolower( $field['field_array']['custom-class'] ) : '';

			if ( strpos( $name, 'bomb_bag_consent' ) !== false || strpos( $custom_class, 'bomb-bag-consent' ) !== false || strpos( $custom_class, 'bomb_bag_consent' ) !== false ) {
				$requires_consent = true;
				// If value is not empty, '0', or 'false', they consented
				if ( ! empty( $value ) && $value !== 'false' && $value !== '0' ) {
					$has_consent = true;
				}
			}
		}

		// If this form has a consent field, but they didn't check it, abort the marketing subscription
		if ( $requires_consent && ! $has_consent ) {
			return; 
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			return; // No valid email found, abort.
		}

		global $wpdb;
		$sub_table             = $wpdb->prefix . 'bomb_bag_subscribers';
		$lists_table           = $wpdb->prefix . 'bomb_bag_lists';
		$list_sub_table        = $wpdb->prefix . 'bomb_bag_list_subscribers';
		$tags_table            = $wpdb->prefix . 'bomb_bag_tags';
		$subscriber_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		// Resolve Form Title for Auto-List & Tag Creation
		$form_title = '';
		if ( class_exists( 'Forminator_API' ) ) {
			$form_model = Forminator_API::get_form( $form_id );
			if ( $form_model && isset( $form_model->name ) ) {
				$form_title = sanitize_text_field( $form_model->name );
			}
		}
		if ( empty( $form_title ) ) {
			$form_post = get_post( $form_id );
			if ( $form_post && ! empty( $form_post->post_title ) ) {
				$form_title = sanitize_text_field( $form_post->post_title );
			} else {
				$form_title = 'Form #' . $form_id;
			}
		}

		// Auto-Create/Find List if list_id is not explicitly provided
		if ( ! $list_id ) {
			$auto_list_name = 'Form: ' . $form_title;
			$auto_list_slug = sanitize_title( 'form-' . $form_id . '-' . $form_title );

			$existing_list = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $lists_table WHERE slug = %s OR name = %s LIMIT 1",
				$auto_list_slug, $auto_list_name
			) );

			if ( $existing_list ) {
				$list_id = absint( $existing_list->id );
			} else {
				$wpdb->insert( $lists_table, array(
					'name'        => $auto_list_name,
					'slug'        => $auto_list_slug,
					'description' => 'Automated list for submissions received from Forminator form: ' . $form_title,
					'created_at'  => current_time( 'mysql' ),
					'updated_at'  => current_time( 'mysql' ),
				) );
				$list_id = absint( $wpdb->insert_id );
			}
		}

		// Check if subscriber exists
		$subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $sub_table WHERE email = %s", $email ) );

		if ( $subscriber ) {
			$subscriber_id = absint( $subscriber->id );
			// Update name if we found new non-empty values
			$update_data = array();
			if ( $first_name ) {
				$update_data['first_name'] = $first_name;
			}
			if ( $last_name ) {
				$update_data['last_name'] = $last_name;
			}
			
			if ( ! empty( $update_data ) ) {
				$wpdb->update( $sub_table, $update_data, array( 'id' => $subscriber_id ) );
			}
		} else {
			// Insert new subscriber
			$wpdb->insert( $sub_table, array(
				'email'         => $email,
				'first_name'    => $first_name,
				'last_name'     => $last_name,
				'status'        => 'active',
				'source'        => 'forminator',
				'score'         => 10,
				'lead_status'   => 'warm',
				'subscribed_at' => current_time( 'mysql' ),
				'created_at'    => current_time( 'mysql' ),
			) );
			$subscriber_id = absint( $wpdb->insert_id );
		}

		// Assign subscriber to list
		if ( $list_id > 0 && $subscriber_id > 0 ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $list_sub_table WHERE subscriber_id = %d AND list_id = %d",
				$subscriber_id, $list_id
			) );

			if ( ! $exists ) {
				$wpdb->insert( $list_sub_table, array(
					'subscriber_id' => $subscriber_id,
					'list_id'       => $list_id,
					'status'        => 'subscribed',
					'subscribed_at' => current_time( 'mysql' ),
					'created_at'    => current_time( 'mysql' ),
				) );
			}
		}

		// Auto-Create / Assign Form Tag
		$tag_name = 'Forminator: ' . $form_title;
		$tag_slug = sanitize_title( 'forminator-' . $form_id );
		$existing_tag = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM $tags_table WHERE slug = %s OR name = %s LIMIT 1",
			$tag_slug, $tag_name
		) );

		$tag_id = 0;
		if ( $existing_tag ) {
			$tag_id = absint( $existing_tag->id );
		} else {
			$wpdb->insert( $tags_table, array(
				'name'       => $tag_name,
				'slug'       => $tag_slug,
				'color'      => '#62c9ff',
				'created_at' => current_time( 'mysql' ),
			) );
			$tag_id = absint( $wpdb->insert_id );
		}

		if ( $tag_id > 0 && $subscriber_id > 0 ) {
			$tag_map_exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $subscriber_tags_table WHERE subscriber_id = %d AND tag_id = %d",
				$subscriber_id, $tag_id
			) );

			if ( ! $tag_map_exists ) {
				$wpdb->insert( $subscriber_tags_table, array(
					'subscriber_id' => $subscriber_id,
					'tag_id'        => $tag_id,
					'created_at'    => current_time( 'mysql' ),
				) );
			}
		}

		// Fire subscription hook so Journeys, Webhooks, and Automations trigger seamlessly
		do_action( 'bomb_bag_subscriber_created', $subscriber_id, $list_id );
		do_action( 'bomb_bag_forminator_submission', $subscriber_id, $form_id, $list_id );
	}
}
