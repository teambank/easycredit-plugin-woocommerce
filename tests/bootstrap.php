<?php

declare(strict_types=1);

$autoload_candidates = [
    dirname(__DIR__) . '/src/wc-easycredit/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];

foreach ($autoload_candidates as $autoload) {
    if (is_readable($autoload)) {
        require $autoload;
        break;
    }
}

$wp_load_candidates = array_filter([
    getenv('WP_LOAD_PATH') ?: null,
    '/var/www/html/wp-load.php',
    dirname(__DIR__) . '/wordpress/wp-load.php',
]);

foreach ($wp_load_candidates as $wp_load) {
    if (is_readable($wp_load)) {
        if (!defined('ABSPATH')) {
            require $wp_load;
        }
        break;
    }
}
