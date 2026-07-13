const { runWpCli } = require("./wp-cli");
const { ensureClassicCheckoutPage } = require("../ensure-classic-checkout-page");

const SETUP_PHP = `
if ( ! function_exists( 'wc_get_page_id' ) ) {
	echo 'woocommerce_not_loaded';
	return;
}

function wc_easycredit_ensure_gm_checkout_checkboxes_block() {
	$checkout_id = (int) wc_get_page_id( 'checkout' );
	if ( $checkout_id <= 0 ) {
		return 'no_checkout_page';
	}

	$content = (string) get_post_field( 'post_content', $checkout_id );
	if ( '' === $content ) {
		return 'empty_checkout_content';
	}

	if ( false !== strpos( $content, 'german-market/checkout-checkboxes' ) ) {
		return 'already_present';
	}

	$gm_block = "<!-- wp:german-market/checkout-checkboxes -->\\n"
		. '<div class="wp-block-german-market-checkout-checkboxes"></div>' . "\\n"
		. '<!-- /wp:german-market/checkout-checkboxes -->';

	$terms_pattern = '#<!-- wp:woocommerce/checkout-terms-block(?:\\s+\\{[^}]*\\})? -->\\s*<div class="wp-block-woocommerce-checkout-terms-block"></div>\\s*<!-- /wp:woocommerce/checkout-terms-block -->#';
	$new_content   = preg_replace( $terms_pattern, $gm_block, $content, 1, $count );

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
	$new_content     = preg_replace( $actions_pattern, $gm_block . "\\n\\n$1", $content, 1, $count );

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

function wc_easycredit_ensure_gm_legal_page( $option, $title, $slug, $content ) {
	$page_id = (int) get_option( $option, 0 );
	if ( $page_id > 0 && get_post( $page_id ) ) {
		return $option . '_exists';
	}

	if ( ! function_exists( 'wc_create_page' ) ) {
		return $option . '_skipped';
	}

	$new_id = (int) wc_create_page( $slug, $option, $title, $content );
	if ( $new_id > 0 ) {
		update_option( $option, $new_id );
		return $option . '_created';
	}

	return $option . '_failed';
}

foreach ( array( 'woocommerce-germanized', 'woocommerce-germanized-pro' ) as $plugin ) {
	if ( is_plugin_active( $plugin . '/' . $plugin . '.php' ) ) {
		deactivate_plugins( $plugin . '/' . $plugin . '.php' );
	}
}

if ( class_exists( 'WGM_Helper' ) && class_exists( 'WGM_Installation' ) ) {
	WGM_Helper::load_installation_language_textdomain();
	if ( ! get_option( WGM_Helper::get_wgm_option( 'woocommerce_options_installed' ), 0 ) ) {
		WGM_Installation::install_de_options();
		WGM_Installation::install_default_pages( false, 'de' );
		update_option( WGM_Helper::get_wgm_option( 'woocommerce_options_installed' ), 1 );
	}
}

wc_easycredit_ensure_gm_legal_page(
	'woocommerce_terms_page_id',
	'AGB',
	'agb',
	'Test AGB.'
);
wc_easycredit_ensure_gm_legal_page(
	'woocommerce_datenschutz_page_id',
	'Datenschutz',
	'datenschutz',
	'Test Datenschutz.'
);
wc_easycredit_ensure_gm_legal_page(
	'woocommerce_widerruf_page_id',
	'Widerruf',
	'widerruf',
	'Test Widerruf.'
);

update_option( 'german_market_checkbox_1_tac_pd_rp_activation', 'on' );
update_option( 'german_market_checkbox_1_tac_pd_rp_opt_in', 'on' );
update_option( 'german_market_checkbox_3_shipping_service_provider_activation', 'on' );
update_option( 'german_market_checkbox_3_shipping_service_provider_opt_in', 'on' );
update_option( 'gm_force_term_template', 'on' );

function wc_easycredit_ensure_classic_checkout_page() {
	$slug = 'classic-checkout';
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $page instanceof WP_Post ) {
		if (
			'publish' !== $page->post_status
			|| '[woocommerce_checkout]' !== trim( (string) $page->post_content )
		) {
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => '[woocommerce_checkout]',
					'post_status'  => 'publish',
				)
			);
		}

		update_option( 'easycredit_classic_checkout_page_id', $page->ID );
		flush_rewrite_rules( false );
		return 'classic_checkout_exists';
	}

	if ( ! function_exists( 'wc_create_page' ) ) {
		return 'classic_checkout_failed';
	}

	$page_id = (int) wc_create_page(
		$slug,
		'easycredit_classic_checkout_page_id',
		'Klassische Kasse',
		'[woocommerce_checkout]'
	);

	if ( $page_id <= 0 ) {
		return 'classic_checkout_failed';
	}

	flush_rewrite_rules( false );
	return 'classic_checkout_created';
}

echo wc_easycredit_ensure_gm_checkout_checkboxes_block();
echo ' ';
echo wc_easycredit_ensure_classic_checkout_page();
`.trim();

function setupGermanMarketLegal() {
	console.log("=== German Market legal checkboxes setup ===");

	const classicCheckoutResult = ensureClassicCheckoutPage((command) =>
		runWpCli(command, { capture: true }),
	);
	console.log(`Classic checkout page: ${classicCheckoutResult}`);

	if (classicCheckoutResult === "classic_checkout_failed") {
		throw new Error("Failed to ensure classic checkout page for German Market tests");
	}

	const encoded = Buffer.from(SETUP_PHP).toString("base64");
	const setupResult = runWpCli(`wp eval 'eval(base64_decode("${encoded}"));'`, {
		capture: true,
	});
	console.log(`German Market setup: ${setupResult}`);

	if (
		setupResult &&
		(setupResult.includes("classic_checkout_failed") ||
			setupResult.includes("no_checkout_page") ||
			setupResult.includes("woocommerce_not_loaded"))
	) {
		throw new Error(
			`German Market legal setup failed (${setupResult || "empty response"})`,
		);
	}

	console.log("=== German Market legal checkboxes ready ===");
}

module.exports = { setupGermanMarketLegal };
