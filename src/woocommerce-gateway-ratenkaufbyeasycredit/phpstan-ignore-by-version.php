<?php
declare(strict_types = 1);

$config = [];

if (version_compare(getenv('PHP_VERSION'), '7.4', '<=')) {
  $config['parameters']['ignoreErrors'] = [];
  $config['parameters']['ignoreErrors'][] = [
    'messages' => [
      '#Call to method get.. on an unknown class Psr.Container.ContainerInterface.#',
      '#Function wc_get_page_screen_id not found.#'
    ],
    'path' => 'includes/order-management.php'
  ];
}
return $config;
