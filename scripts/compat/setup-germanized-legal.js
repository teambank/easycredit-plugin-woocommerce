const { runWpCli } = require("./wp-cli");

const SETUP_PHP = `
function wc_easycredit_ensure_gzd_checkout_checkboxes_block() {
	$checkout_id = (int) wc_get_page_id( 'checkout' );
	if ( $checkout_id <= 0 ) {
		return 'no_checkout_page';
	}

	$content = (string) get_post_field( 'post_content', $checkout_id );
	if ( '' === $content ) {
		return 'empty_checkout_content';
	}

	if ( false !== strpos( $content, 'woocommerce-germanized/checkout-checkboxes' ) ) {
		return 'already_present';
	}

	if ( class_exists( 'WC_GZD_Install' ) ) {
		WC_GZD_Install::adjust_checkout_block();
		$content = (string) get_post_field( 'post_content', $checkout_id );
		if ( false !== strpos( $content, 'woocommerce-germanized/checkout-checkboxes' ) ) {
			return 'adjusted_by_gzd_install';
		}
	}

	$germanized_block = "<!-- wp:woocommerce-germanized/checkout-checkboxes -->\\n"
		. '<div class="wp-block-woocommerce-germanized-checkout-checkboxes"></div>' . "\\n"
		. '<!-- /wp:woocommerce-germanized/checkout-checkboxes -->';

	$terms_pattern = '#<!-- wp:woocommerce/checkout-terms-block(?:\\s+\\{[^}]*\\})? -->\\s*<div class="wp-block-woocommerce-checkout-terms-block"></div>\\s*<!-- /wp:woocommerce/checkout-terms-block -->#';
	$new_content   = preg_replace( $terms_pattern, $germanized_block, $content, 1, $count );

	if ( $count > 0 ) {
		wp_update_post(
			array(
				'ID'           => $checkout_id,
				'post_content' => $new_content,
			)
		);
		return 'replaced_terms_block';
	}

	$actions_pattern = '#(<!-- wp:woocommerce/checkout-actions-block -->)#';
	$new_content     = preg_replace( $actions_pattern, $germanized_block . "\\n\\n$1", $content, 1, $count );

	if ( $count > 0 ) {
		wp_update_post(
			array(
				'ID'           => $checkout_id,
				'post_content' => $new_content,
			)
		);
		return 'inserted_before_actions';
	}

	return 'checkout_block_not_updated';
}

if ( function_exists( 'wc_create_page' ) ) {
	$terms_page_id = (int) get_option( 'woocommerce_terms_page_id', 0 );
	if ( $terms_page_id <= 0 ) {
		$terms_page_id = (int) wc_create_page( 'agb', 'woocommerce_terms_page_id', 'AGB', 'Test AGB.' );
	}
	if ( $terms_page_id > 0 ) {
		update_option( 'woocommerce_terms_page_id', $terms_page_id );
	}
}
if ( class_exists( 'WC_GZD_Install' ) ) {
	WC_GZD_Install::create_pages();
}
$options = get_option( 'woocommerce_gzd_legal_checkboxes_settings', array() );
if ( ! is_array( $options ) ) {
	$options = array();
}
if ( ! isset( $options['terms'] ) || ! is_array( $options['terms'] ) ) {
	$options['terms'] = array();
}
$options['terms']['is_enabled'] = 'yes';
$options['terms']['is_mandatory'] = 'yes';
$options['terms']['hide_input'] = 'no';
update_option( 'woocommerce_gzd_legal_checkboxes_settings', $options );
if ( class_exists( 'WC_GZD_Legal_Checkbox_Manager' ) ) {
	WC_GZD_Legal_Checkbox_Manager::instance()->get_options( true );
}
echo wc_easycredit_ensure_gzd_checkout_checkboxes_block();
`.trim();

function setupGermanizedLegal() {
	console.log("=== Germanized + native terms setup ===");

	const encoded = Buffer.from(SETUP_PHP).toString("base64");
	runWpCli(`wp eval 'eval(base64_decode("${encoded}"));'`);

	console.log("=== Germanized + native terms ready ===");
}

module.exports = { setupGermanizedLegal };
