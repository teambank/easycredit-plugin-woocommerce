<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('ABSPATH')) {
    exit;
}

use Teambank\RatenkaufByEasyCreditApiV3 as ApiV3;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class WC_Gateway_Ratenkaufbyeasycredit_Plugin
{
    public $id;
    public $file;
    public $plugin_path;
    public $plugin_url;
    public $includes_path;
    public $gateway;
    public $rewrite_rules = [
        'easycredit/(cancel)/?' => 'index.php?easycredit[action]=$matches[1]',
        'easycredit/(express)/?' => 'index.php?easycredit[action]=$matches[1]'
    ];

    public function __construct($file)
    {
        $this->id = WC_RATENKAUFBYEASYCREDIT_ID;
        $this->file = $file;
        
        $this->plugin_path = trailingslashit(plugin_dir_path($this->file));
        $this->plugin_url = trailingslashit(plugin_dir_url($this->file));
        $this->includes_path = $this->plugin_path . trailingslashit('includes');
    }

    public function run()
    {
        require_once $this->includes_path . 'class-wc-gateway-ratenkaufbyeasycredit-loader.php';
        $loader = new WC_Gateway_Ratenkaufbyeasycredit_Loader($this);

        if (!is_admin()) {
            new WC_Gateway_Ratenkaufbyeasycredit_Widget_Product($this);
            new WC_Gateway_Ratenkaufbyeasycredit_Widget_Cart($this);
            new WC_Gateway_Ratenkaufbyeasycredit_Express_Checkout($this);
            new WC_Gateway_Ratenkaufbyeasycredit_Marketing($this);
        }

        if (is_admin()) {
            new WC_Gateway_Ratenkaufbyeasycredit_Order_Management($this);
            new WC_Gateway_Ratenkaufbyeasycredit_Marketing_Blocks($this);
        }

        add_action('rest_api_init', [$this, 'init_api']);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_backend_resources']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_resources']);
        
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'prevent_shipping_address_change'], 10, 1);

        add_action('admin_post_wc_ratenkaufbyeasycredit_verify_credentials', [$this, 'verify_credentials']);
        add_filter('plugin_action_links_' . plugin_basename($this->file), [$this, 'plugin_links']);

        add_action('template_redirect', [$this->get_gateway(), 'payment_review_before']);
        add_shortcode($this->get_review_shortcode(), [$this->get_gateway(), 'payment_review']);

        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('admin_init', [$this, 'check_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_controller']);
    }

    public function init_api()
    {
        new WC_Gateway_Ratenkaufbyeasycredit_RestApi(
            $this,
            new WC_Gateway_Ratenkaufbyeasycredit_Order_Management($this)
        );
    }

    public function get_gateway()
    {
        if (!isset($this->gateway)) {
            $this->gateway = new WC_Gateway_RatenkaufByEasyCredit();
        }
        return $this->gateway;
    }

    public function maybe_run()
    {
        add_action('plugins_loaded', [$this, 'run']);
        add_action('init', [$this, 'load_textdomain']);

        add_action('admin_init', [$this, 'brand_relaunch_update']);

        register_activation_hook($this->file, [$this, 'activate']);
        register_deactivation_hook($this->file, [$this, 'deactivate']);
        register_uninstall_hook(__FILE__, 'uninstall');
        add_action('wpmu_new_blog', [$this, 'activate_new_blog'], 10, 6);
    }

    public function activate_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta)
    {
        if (!function_exists('is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }
        if (is_plugin_active_for_network(plugin_basename($this->file))) {
            switch_to_blog($blog_id);
            $this->activate_single_site();
            restore_current_blog();
        }
    }


    public function activate($network_wide)
    {
        $this->add_rewrite_rules();
        flush_rewrite_rules();

        if (is_multisite() && $network_wide) {
            global $wpdb;

            foreach ($wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}") as $blog_id) {
                switch_to_blog($blog_id);
                $this->activate_single_site();
                restore_current_blog();
            }
        } else {
            $this->activate_single_site();
        }
    }
 
    public function deactivate()
    {
        // nothing to do here currently
    }

    public static function uninstall()
    {
        // nothing to do here currently
    }

    public function add_rewrite_rules()
    {
        add_rewrite_tag('%easycredit%', '([^/]+)');
        foreach ($this->rewrite_rules as $regex => $query) {
            add_rewrite_rule($regex, $query, 'top');
        }
    }

    public function check_rewrite_rules()
    {
        $flush = false;
        $rules = get_option('rewrite_rules');
        foreach ($this->rewrite_rules as $regex => $query) {
            if (!isset($rules[$regex])) {
                $flush = true;
            }
        }

        if ($flush) {
            flush_rewrite_rules();
        }
    }

    public function handle_controller()
    {
        global $wp_query;

        $params = $wp_query->get('easycredit');
        if (!empty($params['action'])) {
            if (method_exists($this, $params['action'] . 'Action')) {
                $this->{$params['action'] . 'Action'}($params);
            }
        }
    }

    public function expressAction()
    {
        try {
            try {
                $this->gateway->get_storage()
                    ->set('express', true);

                $quote = $this->gateway->get_quote_builder()->build(
                    $this->gateway->get_tmp_order()
                );

                $checkout = $this->gateway->get_checkout();
                $checkout->start($quote);

                wp_redirect($checkout->getRedirectUrl());
                exit;
            } catch (ApiV3\ApiException $e) {
                if ($e->getResponseObject() instanceof ApiV3\Model\PaymentConstraintViolation) {
                    $error = 'easyCredit-Ratenkauf: ';
                    foreach ($e->getResponseObject()->getViolations() as $violation) {
                        $error .= $violation->getMessageDE() ? $violation->getMessageDE() :  $violation->getMessage();
                    }
                    throw new \Exception($error);
                }
                throw $e;
            }
        } catch (\Exception $e) {
            $this->gateway->get_storage()
                ->set('express', false);

            $this->get_gateway()->handleError($e->getMessage());
        }
    }

    public function activate_single_site()
    {
        require_once(\WC_ABSPATH . 'includes/admin/wc-admin-functions.php');

        $pages = $this->get_review_page_data();
        foreach ($pages as $key => $page) {
            $id = wc_create_page(
                esc_sql($page['name']),
                $key,
                $page['title'],
                $page['content']
            );
        }
        delete_transient('woocommerce_cache_excluded_uris');
    }

    public function get_review_page_data()
    {
        return [
            'woocommerce_easycredit_checkout_review_page_id' => [
                'name' => _x('easycredit-checkout-review', 'Page slug', 'woocommerce'),
                'title' => _x('Review Order', 'Page title', 'woocommerce'),
                'content' => '[' . $this->get_review_shortcode() . ']',
            ],
            'woocommerce_easycredit_infopage_page_id' => [
                'name' => _x('easycredit-infopage', 'Page slug', 'woocommerce'),
                'title' => _x('easyCredit-Ratenkauf - Der einfachste Ratenkauf Deutschlands.', 'Page title', 'woocommerce'),
                'content' => '<easycredit-infopage></easycredit-infopage>',
            ],
        ];
    }

    public function get_review_page_uri()
    {
        $pageId = get_option(
            key($this->get_review_page_data())
        );
        return get_permalink($pageId);
    }

    public function get_review_shortcode()
    {
        return 'woocommerce_' . $this->id . '_checkout_review';
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            'woocommerce-gateway-ratenkaufbyeasycredit',
            false,
            basename(dirname($this->file)) . '/languages/'
        );
    }

    public function load_template($tpl, $data = [])
    {
        foreach ($data as $k => $v) {
            set_query_var($k, $v);
        }

        $template = $this->plugin_path . '/templates/' . $tpl . '.php';
        $_template = locate_template($tpl . '.php');
        if ($_template) {
            $template = $_template;
        }
        load_template($template, false);
    }

    public function add_module_nomodule_attribute($tag, $handle, $src)
    {
        if ($handle === 'easycredit-components-module') {
            $src = remove_query_arg('ver', $src);
            return '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        if ($handle === 'easycredit-components-nomodule') {
            $src = remove_query_arg('ver', $src);
            return '<script nomodule src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }

    public function enqueue_easycredit_components()
    {
        wp_register_script('easycredit-components-module', 'https://ratenkauf.easycredit.de/api/resource/webcomponents/v3/easycredit-components/easycredit-components.esm.js', [], '1.0', ['strategy'  => 'async', 'in_footer' => true]);
        wp_enqueue_script('easycredit-components-module');
        add_filter('script_loader_tag', [$this, 'add_module_nomodule_attribute'], 10, 3);
    }

    public function enqueue_frontend_resources($hook)
    {
        $this->enqueue_easycredit_components();

        wp_enqueue_script(
            'wc_ratenkaufbyeasycredit_js',
            $this->plugin_url . 'modules/frontend/build/index.js',
            ['jquery'],
            '2.1'
        );
        wp_enqueue_style(
            'wc_ratenkaufbyeasycredit_css',
            $this->plugin_url . 'modules/frontend/build/styles.css'
        );
    }

    public function enqueue_backend_resources($hook)
    {
        $this->enqueue_easycredit_components();

        wp_enqueue_script(
            'wc_ratenkaufbyeasycredit_js',
            $this->plugin_url . 'modules/backend/build/index.js',
            ['jquery'],
            '1.0'
        );

        wp_localize_script('wc_ratenkaufbyeasycredit_js', 'wc_ratenkaufbyeasycredit_config', [
            'url' => admin_url('admin-post.php'),
        ]);

        wp_enqueue_style(
            'wc_ratenkaufbyeasycredit_css',
            $this->plugin_url . 'modules/backend/build/styles.css'
        );
        wp_enqueue_style(
            'wc_ratenkaufbyeasycredit_marketing_css',
            $this->plugin_url . 'modules/marketing/build/styles.css'
        );

        wp_enqueue_media();
    }

    public function prevent_shipping_address_change($order) {
        if ($order->get_payment_method() != $this->id) {
            return;
        }
        echo "
            <p>Die Versandadresse kann bei easyCredit-Ratenkauf nicht nachträglich verändert werden.</p>
            <script>
            jQuery('#order_data .order_data_column_container .order_data_column h3:contains(\"". esc_html__('Shipping', 'woocommerce') ."\") a.edit_address').hide();
            </script>
        ";
    }
    
    public function verify_credentials()
    {
        $status = [
            'status' => true,
            'msg' => __('Credentials valid!', 'woocommerce-gateway-ratenkaufbyeasycredit'),
        ];

        $payment = new WC_Gateway_RatenkaufByEasyCredit();
        $error = $payment->check_credentials($_REQUEST['api_key'], $_REQUEST['api_token'], $_REQUEST['api_signature']);
        if ($error) {
            $status = [
                'status' => false,
                'msg' => strip_tags($error),
            ];
        }

        wp_send_json($status);
    }

    public function plugin_links($links)
    {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=ratenkaufbyeasycredit') . '">' . __('Settings', 'wc-gateway-ratenkaufbyeasycredit') . '</a>',
        ];
        return array_merge($plugin_links, $links);
    }

    public function brand_relaunch_update()
    {

        $transient = $this->id . '-brand-relaunch-updated';
        if (!get_transient($transient)) {
            $option_key = 'woocommerce_ratenkaufbyeasycredit_settings';
            $option = get_option($option_key);
            if (isset($option['title'])) {
                $option['title'] = str_ireplace('ratenkauf by easyCredit', 'easyCredit-Ratenkauf', $option['title']);
            }
            update_option($option_key, $option);
            set_transient($transient, true);
        }

        $transient = $this->id . '-brand-relaunch-page-updated';
        if (!get_transient($transient)) {
            $old_slogan = 'ratenkauf by easyCredit – Einfach. Fair. In Raten zahlen';
            $new_slogan = 'easyCredit-Ratenkauf - Ganz entspannt in Raten zahlen.';

            $page_id = get_option('woocommerce_easycredit_infopage_page_id');

            $post = get_post($page_id);
            wp_update_post([
              'ID' => $post->ID,
              'post_title' => str_ireplace($old_slogan, $new_slogan, $post->post_title)
            ]);

            $query = new WP_Query([
                'post_type' => 'nav_menu_item',
                'meta_key' => '_menu_item_object_id',
                'meta_value' => $page_id
            ]);
            if ($query->have_posts()) {
                $post = $query->posts[0];
                wp_update_post([
                    'ID' => $post->ID,
                    'post_title' => str_ireplace($old_slogan, $new_slogan, $post->post_title)
                ]);
            }
            set_transient($transient, true);
        }
    }
}
