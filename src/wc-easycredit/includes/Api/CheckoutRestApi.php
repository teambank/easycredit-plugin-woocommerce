<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Netzkollektiv\EasyCredit\Integration;

class CheckoutRestApi
{
    protected Integration $integration;

    public function __construct(Integration $integration)
    {
        $this->integration = $integration;

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route('easycredit/v1', '/checkout-validation', [
            'methods' => 'POST',
            'callback' => [$this, 'get_checkout_validation'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function get_checkout_validation(\WP_REST_Request $request): \WP_REST_Response
    {
        $params = $request->get_json_params();
        $billing = isset($params['billing']) && is_array($params['billing'])
            ? $params['billing']
            : null;
        $shipping = isset($params['shipping']) && is_array($params['shipping'])
            ? $params['shipping']
            : null;

        return rest_ensure_response([
            'message' => $this->integration->getCheckoutValidationMessage($billing, $shipping),
        ]);
    }
}
