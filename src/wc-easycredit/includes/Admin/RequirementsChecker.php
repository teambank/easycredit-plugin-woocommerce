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
    }

    public function auto_check_requirements()
    {
        if (!filter_var(ini_get('allow_url_fopen'), \FILTER_VALIDATE_BOOLEAN)) {
            echo $this->_display_settings_error(__('To use easyCredit payment the php.ini setting "allow_url_fopen" must be enabled.', 'wc-easycredit'));
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
