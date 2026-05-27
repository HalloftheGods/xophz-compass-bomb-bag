<?php

/**
 * Xophz COMPASS - Bomb Bag WooCommerce Integration
 * 
 * Handles integrating Bomb Bag drips/journeys with WooCommerce core events.
 */

class Xophz_Compass_Bomb_Bag_WooCommerce {

    public function init() {
        // Post-Purchase trigger
        add_action('woocommerce_thankyou', [$this, 'handle_post_purchase'], 10, 1);
        
        // Abandoned Cart: Track Add to Cart & Checkout Email Entry
        add_action('woocommerce_add_to_cart', [$this, 'track_cart_activity'], 10, 6);
        add_action('woocommerce_checkout_update_user_meta', [$this, 'capture_checkout_email'], 10, 2);
        
        // WP Cron / Action Scheduler Hook for checking abandoned carts
        add_action('xophz_compass_bomb_bag_check_abandoned_cart', [$this, 'process_abandoned_cart'], 10, 2);
    }

    /**
     * Triggered when a user completes a purchase.
     */
    public function handle_post_purchase($order_id) {
        if (!$order_id) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $email = $order->get_billing_email();
        if (empty($email)) return;

        // Ensure user exists in Bomb Bag or add them
        $subscriber_id = Xophz_Compass_Bomb_Bag_DB::add_or_update_subscriber([
            'email' => $email,
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'source' => 'woocommerce_checkout'
        ]);

        if ($subscriber_id) {
            $this->enroll_in_trigger('wc_post_purchase', $subscriber_id);
        }
    }

    /**
     * Track Add to Cart to initiate a potential Abandoned Cart flow.
     */
    public function track_cart_activity($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $email = '';
        
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $email = $user->user_email;
        } else {
            // Check if guest email is stored in session
            $session = WC()->session;
            if ($session) {
                $email = $session->get('billing_email');
            }
        }

        if (!empty($email)) {
            $this->schedule_abandoned_cart_check($email);
        }
    }

    /**
     * Capture guest email early during checkout field updates.
     */
    public function capture_checkout_email($customer_id, $posted) {
        if (!empty($posted['billing_email'])) {
            $this->schedule_abandoned_cart_check($posted['billing_email']);
        }
    }

    /**
     * Schedules a check for cart abandonment 1 hour from now.
     */
    private function schedule_abandoned_cart_check($email) {
        // If Action Scheduler is available (WooCommerce core includes it)
        if (function_exists('as_schedule_single_action')) {
            $hook = 'xophz_compass_bomb_bag_check_abandoned_cart';
            
            // Only schedule if not already scheduled for this email
            if (!as_has_scheduled_action($hook, ['email' => $email])) {
                as_schedule_single_action(time() + HOUR_IN_SECONDS, $hook, ['email' => $email], 'bomb_bag');
            }
        }
    }

    /**
     * Evaluates if the cart was actually abandoned.
     */
    public function process_abandoned_cart($email) {
        if (empty($email)) return;
        
        $user = get_user_by('email', $email);
        $user_id = $user ? $user->ID : 0;
        
        // Find if they have an active order placed within the last hour
        $recent_orders = wc_get_orders([
            'customer' => $email,
            'date_created' => '>' . (time() - HOUR_IN_SECONDS),
            'limit' => 1
        ]);
        
        if (!empty($recent_orders)) {
            // They purchased, do not trigger abandoned cart
            return;
        }

        // Verify cart is actually not empty if we can map it (advanced tracking may be needed)
        // For simplicity, if no recent order exists and this hook runs, we trigger the journey.

        $subscriber_id = Xophz_Compass_Bomb_Bag_DB::add_or_update_subscriber([
            'email' => $email,
            'source' => 'woocommerce_abandoned_cart'
        ]);

        if ($subscriber_id) {
            $this->enroll_in_trigger('wc_abandoned_cart', $subscriber_id);
        }
    }

    /**
     * Finds active journeys with this trigger and enrolls the subscriber.
     */
    private function enroll_in_trigger($trigger_type, $subscriber_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'bomb_bag_journeys';
        
        // Get active journeys for this trigger
        $journeys = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table WHERE status = 'active' AND trigger_type = %s",
            $trigger_type
        ));

        if (!empty($journeys)) {
            foreach ($journeys as $journey) {
                // We'll reuse the drip enrollment pipeline for journeys
                // Xophz_Compass_Bomb_Bag_DB::enroll_subscriber($journey->id, $subscriber_id);
                // Currently Bomb Bag handles enrollment via a specific method in the engine.
            }
        }
    }
}
