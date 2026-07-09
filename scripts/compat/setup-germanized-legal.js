const { runWpCli } = require("./wp-cli");

const SETUP_PHP = `
if ( function_exists( "wc_create_page" ) ) {
	$terms_page_id = (int) get_option( "woocommerce_terms_page_id", 0 );
	if ( $terms_page_id <= 0 ) {
		$terms_page_id = (int) wc_create_page( "agb", "woocommerce_terms_page_id", "AGB", "Test AGB." );
	}
	if ( $terms_page_id > 0 ) {
		update_option( "woocommerce_terms_page_id", $terms_page_id );
	}
}
if ( class_exists( "WC_GZD_Install" ) ) {
	WC_GZD_Install::create_pages();
	WC_GZD_Install::adjust_checkout_block();
}
$options = get_option( "woocommerce_gzd_legal_checkboxes_settings", array() );
if ( ! is_array( $options ) ) {
	$options = array();
}
if ( ! isset( $options["terms"] ) || ! is_array( $options["terms"] ) ) {
	$options["terms"] = array();
}
$options["terms"]["is_enabled"] = "yes";
$options["terms"]["is_mandatory"] = "yes";
$options["terms"]["hide_input"] = "no";
update_option( "woocommerce_gzd_legal_checkboxes_settings", $options );
if ( class_exists( "WC_GZD_Legal_Checkbox_Manager" ) ) {
	WC_GZD_Legal_Checkbox_Manager::instance()->get_options( true );
}
`.trim();

function setupGermanizedLegal() {
	console.log("=== Germanized + native terms setup ===");

	const encoded = Buffer.from(SETUP_PHP).toString("base64");
	runWpCli(`wp eval 'eval(base64_decode("${encoded}"));'`);

	console.log("=== Germanized + native terms ready ===");
}

module.exports = { setupGermanizedLegal };
