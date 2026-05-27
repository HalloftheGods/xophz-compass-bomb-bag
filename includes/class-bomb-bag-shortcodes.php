<?php
/**
 * Shortcodes for Bomb Bag
 */
class Xophz_Compass_Bomb_Bag_Shortcodes {

	public function register() {
		add_shortcode( 'bomb_bag_form', array( $this, 'render_form' ) );
	}

	public function render_form( $atts ) {
		$atts = shortcode_atts( array(
			'list'        => 0,
			'button_text' => 'Subscribe',
			'show_name'   => 'false',
		), $atts, 'bomb_bag_form' );

		$list_id     = absint( $atts['list'] );
		$button_text = esc_html( $atts['button_text'] );
		$show_name   = filter_var( $atts['show_name'], FILTER_VALIDATE_BOOLEAN );
		$form_id     = 'bomb-bag-form-' . uniqid();
		$api_url     = esc_url_raw( rest_url( 'bomb-bag/v1/subscribe' ) );

		ob_start();
		?>
		<div class="bomb-bag-subscription-wrapper" style="max-width: 400px; margin: 1rem 0; padding: 1.5rem; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; backdrop-filter: blur(10px);">
			<form id="<?php echo esc_attr( $form_id ); ?>" class="bomb-bag-form" onsubmit="handleBombBagSubmit(event, '<?php echo esc_attr( $form_id ); ?>', '<?php echo $api_url; ?>', <?php echo $list_id; ?>)">
				
				<?php if ( $show_name ) : ?>
					<div style="margin-bottom: 1rem;">
						<label for="<?php echo esc_attr( $form_id ); ?>-first-name" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; opacity: 0.8;">First Name</label>
						<input type="text" id="<?php echo esc_attr( $form_id ); ?>-first-name" name="first_name" style="width: 100%; padding: 0.5rem; border-radius: 4px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: inherit;" />
					</div>
				<?php endif; ?>

				<div style="margin-bottom: 1rem;">
					<label for="<?php echo esc_attr( $form_id ); ?>-email" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; opacity: 0.8;">Email Address *</label>
					<input type="email" id="<?php echo esc_attr( $form_id ); ?>-email" name="email" required style="width: 100%; padding: 0.5rem; border-radius: 4px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.2); color: inherit;" />
				</div>

				<button type="submit" style="width: 100%; padding: 0.75rem; border: none; border-radius: 4px; background: #62c9ff; color: #000; font-weight: bold; cursor: pointer; transition: opacity 0.2s;">
					<?php echo $button_text; ?>
				</button>

				<div class="bomb-bag-form-message" style="margin-top: 1rem; font-size: 0.9rem; display: none;"></div>
			</form>
		</div>

		<script>
			if (typeof handleBombBagSubmit !== 'function') {
				function handleBombBagSubmit(event, formId, apiUrl, listId) {
					event.preventDefault();
					var form = document.getElementById(formId);
					var messageDiv = form.querySelector('.bomb-bag-form-message');
					var btn = form.querySelector('button[type="submit"]');
					var formData = new FormData(form);
					
					var data = {
						email: formData.get('email'),
						list_id: listId
					};
					if (formData.get('first_name')) {
						data.first_name = formData.get('first_name');
					}

					btn.style.opacity = '0.5';
					btn.disabled = true;
					messageDiv.style.display = 'none';

					fetch(apiUrl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify(data)
					})
					.then(function(response) {
						return response.json();
					})
					.then(function(result) {
						btn.style.opacity = '1';
						btn.disabled = false;
						messageDiv.style.display = 'block';

						if (result.success) {
							messageDiv.style.color = '#4ade80'; // green
							messageDiv.textContent = result.message || 'Successfully subscribed!';
							form.reset();
						} else {
							messageDiv.style.color = '#f87171'; // red
							messageDiv.textContent = result.message || 'An error occurred. Please try again.';
						}
					})
					.catch(function(error) {
						btn.style.opacity = '1';
						btn.disabled = false;
						messageDiv.style.display = 'block';
						messageDiv.style.color = '#f87171';
						messageDiv.textContent = 'Network error. Please try again.';
					});
				}
			}
		</script>
		<?php
		return ob_get_clean();
	}
}
