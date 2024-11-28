const { execSync } = require('child_process');

const run = (cmd) => {
  const output = execSync('wp-env run cli -- ' + cmd, { stdio: 'pipe', encoding: 'utf8' })
  console.log(output.toString());
  return output;
}

console.log(`setup woocommerce @ ${process.env.VERSION}`);
try {
    // Install and activate WooCommerce plugin
    run(`wp plugin install woocommerce --version="${process.env.VERSION}" --activate --force`);
    run('wp plugin activate wc-easycredit');

    // Install and activate Storefront theme
    run('wp theme install storefront --activate');

    // Install German language packs
    run('wp language core install de_DE');
    run('wp language theme install storefront de_DE');
    run('wp language plugin install woocommerce de_DE');

    // Switch site language to German
    run('wp site switch-language de_DE');

    // Update WooCommerce settings and options
    run('wp option update woocommerce_onboarding_profile \'{"skipped": true}\' --json');
    run('wp option update woocommerce_task_list_reminder_bar_hidden "yes"');
    run('wp option update woocommerce_task_list_prompt_shown 1');
    run('wp option update woocommerce_show_marketplace_suggestions "no"');
    run('wp option update woocommerce_allow_tracking "no"');
    run('wp option update woocommerce_task_list_complete "yes"');
    run('wp option update woocommerce_task_list_welcome_modal_dismissed "yes"');
    run('wp option update woocommerce_default_country "DE:DE-BY"');
    run('wp option update woocommerce_currency "EUR"');
    run('wp rewrite structure "/index.php/%postname%/"');

    // Create simple products
    run('wp wc product create --name="Regular" --slug="regular" --type="simple" --sku="regular" --regular_price="201" --status="publish" --user="admin"');
    run('wp wc product create --name="Below50" --slug="below50" --type="simple" --sku="below50" --regular_price="49" --status="publish" --user="admin"');
    run('wp wc product create --name="Below200" --slug="below200" --type="simple" --sku="below200" --regular_price="199" --status="publish" --user="admin"');
    run('wp wc product create --name="Above5000" --slug="above5000" --type="simple" --sku="above5000" --regular_price="6000" --status="publish" --user="admin"');
    run('wp wc product create --name="Above10000" --slug="above10000" --type="simple" --sku="above10000" --regular_price="11000" --status="publish" --user="admin"');

    // Create a variable product
    const PID = run('wp wc product create --name="Variable" --slug="variable" --type="variable" --sku="variable" --status="publish" --user="admin" --attributes=\'[{"name": "Size", "options": ["small", "medium", "large"], "visible": true, "variation": true}]\' --porcelain').toString().trim();

    // Check if PID is not empty and create variations
    if (PID) {
        run(`wp wc product_variation create ${PID} --attributes='[{"name": "Size", "option": "small"}]' --regular_price="201" --user="admin"`);
        run(`wp wc product_variation create ${PID} --attributes='[{"name": "Size", "option": "medium"}]' --regular_price="21" --user="admin"`);
        run(`wp wc product_variation create ${PID} --attributes='[{"name": "Size", "option": "large"}]' --regular_price="21" --user="admin"`);
    }

} catch (error) {
    console.error(`Error executing command: ${error.message}`);
}
