const ENABLE_TERMS_CHECKBOX_PHP = `
$checkout_id = (int) get_option( 'woocommerce_checkout_page_id' );
if ( $checkout_id <= 0 ) {
	return;
}

$content = (string) get_post_field( 'post_content', $checkout_id );
if ( '' === $content ) {
	return;
}

$replacement = '<!-- wp:woocommerce/checkout-terms-block {"checkbox":true} -->' . "\\n" .
	'<div class="wp-block-woocommerce-checkout-terms-block"></div>' . "\\n" .
	'<!-- /wp:woocommerce/checkout-terms-block -->';
$pattern     = '#<!-- wp:woocommerce/checkout-terms-block(?:\\s+\\{[^}]*\\})? -->\\s*<div class="wp-block-woocommerce-checkout-terms-block"></div>\\s*<!-- /wp:woocommerce/checkout-terms-block -->#';

$new_content = preg_replace( $pattern, $replacement, $content, 1, $count );
if ( $count > 0 ) {
	wp_update_post(
		array(
			'ID'           => $checkout_id,
			'post_content' => $new_content,
		)
	);
	echo 'updated';
} elseif ( false !== strpos( $content, '"checkbox":true' ) ) {
	echo 'already_enabled';
} else {
	echo 'terms_block_not_found';
}
`.trim();

function usesBlocksCheckout(version) {
	if (!version) {
		return false;
	}

	return (
		version.localeCompare("8.3", undefined, {
			numeric: true,
			sensitivity: "base",
		}) >= 0
	);
}

function enableBlocksCheckoutTermsCheckbox(run) {
	const encoded = Buffer.from(ENABLE_TERMS_CHECKBOX_PHP).toString("base64");
	run(`wp eval 'eval(base64_decode("${encoded}"));'`);
}

module.exports = {
	enableBlocksCheckoutTermsCheckbox,
	usesBlocksCheckout,
};
