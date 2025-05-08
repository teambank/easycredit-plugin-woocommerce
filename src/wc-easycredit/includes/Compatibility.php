<?php

namespace Netzkollektiv\EasyCredit;

class Compatibility {
    private const OLD_PLUGIN_PATH = 'woocommerce-gateway-ratenkaufbyeasycredit/woocommerce-gateway-ratenkaufbyeasycredit.php';

    public function __construct() {
        add_action('admin_init', [$this, 'checkOldPlugin']);
        add_action('admin_notices', [$this, 'displayOldPluginNotice']);
    }

    /**
     * Check if old plugin is active and deactivate it
     */
    public function checkOldPlugin(): void {
        if (is_plugin_active(self::OLD_PLUGIN_PATH)) {
            deactivate_plugins(self::OLD_PLUGIN_PATH);
        }
    }

    /**
     * Display admin notice if old plugin files are still present
     */
    public function displayOldPluginNotice(): void {
        $old_plugin_path = WP_PLUGIN_DIR . '/' . self::OLD_PLUGIN_PATH;

        if (file_exists($old_plugin_path)) {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo sprintf(
                __('The old version of the easyCredit-Ratenkauf plugin is still present in %s. Please delete it from your plugins folder to avoid conflicts.', 'wc-easycredit'),
                '<code>' . esc_html(basename(dirname($old_plugin_path))) . '</code>'
            );
            echo '</p></div>';
        }
    }
} 