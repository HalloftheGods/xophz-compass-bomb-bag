<?php

/**
 * Bomb Bag Segment Compiler
 * 
 * Compiles dynamic JSON rules into SQL queries and executes them against subscribers.
 */
class Xophz_Compass_Bomb_Bag_Segment_Compiler {

	/**
	 * Get an array of subscriber IDs that match the segment rules.
	 *
	 * @param int $segment_id The segment ID.
	 * @return int[] Array of subscriber IDs.
	 */
	public function get_subscribers_for_segment( $segment_id ) {
		global $wpdb;
		$segments_table = $wpdb->prefix . 'bomb_bag_segments';
		
		$segment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $segments_table WHERE id = %d", $segment_id ) );
		if ( ! $segment ) {
			return array();
		}

		$rules = json_decode( $segment->rules_json, true );
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return array();
		}

		return $this->compile_and_execute( $rules );
	}

	/**
	 * Compile a JSON rules configuration into a SQL query and return subscriber IDs.
	 *
	 * Expected format for $rules_config:
	 * {
	 *   "match": "all", // or "any"
	 *   "rules": [
	 *     { "type": "tag", "operator": "has_any", "value": [1, 2, 3] },
	 *     { "type": "status", "operator": "is", "value": "active" }
	 *   ]
	 * }
	 *
	 * @param array $rules_config The decoded JSON rules.
	 * @return int[] Array of subscriber IDs.
	 */
	public function compile_and_execute( $rules_config ) {
		global $wpdb;
		$subscribers_table = $wpdb->prefix . 'bomb_bag_subscribers';
		$sub_tags_table = $wpdb->prefix . 'bomb_bag_subscriber_tags';

		// Extract match type and rules array
		$match_type = $rules_config['match'] ?? 'all';
		$rules = $rules_config['rules'] ?? $rules_config; // fallback if rules_config is just an array

		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return array();
		}

		$sql = "SELECT DISTINCT s.id FROM $subscribers_table s";
		$where_clauses = array();

		foreach ( $rules as $index => $rule ) {
			$type = $rule['type'] ?? '';
			$operator = $rule['operator'] ?? '';
			$value = $rule['value'] ?? array();
			
			if ( $type === 'tag' ) {
				if ( empty( $value ) || ! is_array( $value ) ) continue;

				$tag_ids = implode( ',', array_map( 'intval', $value ) );
				
				if ( $operator === 'has_any' ) {
					$where_clauses[] = "s.id IN (SELECT subscriber_id FROM $sub_tags_table WHERE tag_id IN ($tag_ids))";
				} elseif ( $operator === 'has_all' ) {
					$count = count( $value );
					$where_clauses[] = "s.id IN (SELECT subscriber_id FROM $sub_tags_table WHERE tag_id IN ($tag_ids) GROUP BY subscriber_id HAVING COUNT(DISTINCT tag_id) = $count)";
				} elseif ( $operator === 'not_has' ) {
					$where_clauses[] = "s.id NOT IN (SELECT subscriber_id FROM $sub_tags_table WHERE tag_id IN ($tag_ids))";
				}
			} elseif ( $type === 'status' ) {
				if ( is_string( $value ) ) {
					$status = esc_sql( $value );
					if ( $operator === 'is' ) {
						$where_clauses[] = "s.status = '$status'";
					} elseif ( $operator === 'is_not' ) {
						$where_clauses[] = "s.status != '$status'";
					}
				}
			}
		}

		$joiner = ( $match_type === 'any' ) ? ' OR ' : ' AND ';

		if ( ! empty( $where_clauses ) ) {
			$sql .= " WHERE (" . implode( ") $joiner (", $where_clauses ) . ")";
		}

		$results = $wpdb->get_col( $sql );
		return array_map( 'intval', $results );
	}
}
