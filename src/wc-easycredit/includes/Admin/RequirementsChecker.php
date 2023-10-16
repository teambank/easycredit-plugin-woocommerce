<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Admin;

use Netzkollektiv\EasyCredit\Plugin;

class RequirementsChecker
{
    protected $plugin;

    public function __construct(
        Plugin $plugin
    ) {
        $this->plugin = $plugin;

        add_action('admin_notices', [$this, 'auto_check_credentials']);
        add_action('admin_notices', [$this, 'auto_check_requirements']);
        add_action('admin_notices', [$this, 'check_review_page_exists']);        
    }

    public function auto_check_requirements()
    {
        if (!filter_var(ini_get('allow_url_fopen'), \FILTER_VALIDATE_BOOLEAN)) {
            echo $this->_display_settings_error(__('To use easyCredit-Ratenkauf the php.ini setting "allow_url_fopen" must be enabled.', 'wc-easycredit'));
        }
    }

    public function auto_check_credentials()
    {
        if (
            get_current_screen()->parent_base !== 'woocommerce' ||
            $this->plugin->get_transient('easycredit-settings-checked')
        ) {
            return;
        }

        $apiKey = $this->plugin->get_option('api_key');
        $apiToken = $this->plugin->get_option('api_token');
        $apiSignature = $this->plugin->get_option('api_signature');

        $error = $this->plugin->check_credentials($apiKey, $apiToken, $apiSignature);
        if ($error) {
            echo $this->_display_settings_error($error);
            return;
        }
        set_transient('easycredit-settings-checked', true, DAY_IN_SECONDS);
    }

    public function check_review_page_exists()
    {
        if (get_current_screen()->parent_base !== 'woocommerce') {
            return;
        }

        $page_path = current($this->plugin->get_review_page_data())['name'];
        if (get_page_by_path($page_path, OBJECT)) {
            return;
        }

        echo $this->_display_settings_error(
            __('The "easyCredit-Ratenkauf" review page does not exist. Probably it was deleted by mistake. The page is necessary to confirm "easyCredit-Ratenkauf" payments after being returned from the payment terminal. To restore the page, please restore it from the trash under "Pages", or deactivate and activate the plugin in the <a href="%s">plugin administration</a>.', 'wc-easycredit'),
            is_multisite() ? admin_url('network/plugins.php?s=easycredit') : admin_url('plugins.php?s=easycredit')
        );
        return;
    }

    protected function _display_settings_error($msg, $uri = null)
    {
        if (is_array($msg)) {
            $msg = implode(' ', $msg);
        }

        if ($uri === null) {
            $uri = admin_url('admin.php?page=wc-settings&tab=checkout&section=easycredit');
        }
        return implode([
            '<div class="error"><p>',
            sprintf($msg, $uri),
            '</p></div>',
        ]);
    }
}