<?php

/**
 * Xophz COMPASS - Bomb Bag Forminator Integration
 * 
 * Captures Forminator form submissions and maps them to Bomb Bag subscribers.
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
		$list_id    = 0; // Default to no list unless mapped

		// Parse field data
		foreach ( $field_data_array as $field ) {
			$name  = $field['name'];
			$value = $field['value'];

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
				// If the hidden field has a value that looks like an integer, use it as list_id
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
		$sub_table      = $wpdb->prefix . 'bombbag_subscribers';
		$list_map_table = $wpdb->prefix . 'bombbag_subscriber_lists';

		// Check if subscriber exists
		$subscriber = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $sub_table WHERE email = %s", $email ) );

		if ( $subscriber ) {
			$subscriber_id = $subscriber->id;
			// Update name if we found a new one
			$update_data = array();
			if ( $first_name ) $update_data['first_name'] = $first_name;
			if ( $last_name )  $update_data['last_name'] = $last_name;
			
			if ( ! empty( $update_data ) ) {
				$wpdb->update( $sub_table, $update_data, array( 'id' => $subscriber_id ) );
			}
		} else {
			// Insert new subscriber
			$wpdb->insert( $sub_table, array(
				'email'      => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'status'     => 'active',
				'created_at' => current_time('mysql')
			) );
			$subscriber_id = $wpdb->insert_id;
		}

		// Assign to list if a list ID was mapped via hidden field
		if ( $list_id > 0 ) {
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $list_map_table WHERE subscriber_id = %d AND list_id = %d",
				$subscriber_id, $list_id
			) );

			if ( ! $exists ) {
				$wpdb->insert( $list_map_table, array(
					'subscriber_id' => $subscriber_id,
					'list_id'       => $list_id,
					'status'        => 'subscribed',
					'subscribed_at' => current_time('mysql')
				) );
			}
		}

		// Fire subscription hook so Journeys can trigger
		do_action( 'bomb_bag_subscriber_created', $subscriber_id, $list_id );
		do_action( 'bomb_bag_forminator_submission', $subscriber_id, $form_id );
	}
}
