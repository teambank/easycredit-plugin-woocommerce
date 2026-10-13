<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Admin;

use Teambank\EasyCreditApiV3\ApiException;
use Teambank\EasyCreditApiV3\Model\CaptureRequest;
use Teambank\EasyCreditApiV3\Model\RefundRequest;
use Netzkollektiv\EasyCredit\Plugin;


class RestApi
{
    protected $plugin;

    protected $integration;
    
    public function __construct(
        Plugin $plugin, 
        $integration 
    ) {
        $this->plugin = $plugin;
        $this->integration = $integration;

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Checked on every request. Registering the routes only for the current
     * user is not enough: WordPress drops that user when the REST nonce is
     * missing and would still run a callback that allows everyone.
     */
    public function merchant_permissions_check()
    {
        if (current_user_can('manage_woocommerce')) {
            return true;
        }

        return new \WP_Error(
            'rest_forbidden',
            __('Sorry, you are not allowed to manage easyCredit transactions.', 'wc-easycredit'),
            ['status' => rest_authorization_required_code()]
        );
    }

    public function register_routes()
    {
        $routeArgs = [
            'permission_callback' => [$this, 'merchant_permissions_check'],
        ];

        register_rest_route('easycredit/v1', '/transactions', $routeArgs + [
            'methods' => 'GET',
            'callback' => [$this, 'get_transactions'],
        ]);

        register_rest_route('easycredit/v1', '/transaction', $routeArgs + [
            'methods' => 'GET',
            'callback' => [$this, 'get_transaction'],
        ]);

        register_rest_route('easycredit/v1', '/capture', $routeArgs + [
            'methods' => 'POST',
            'callback' => [$this, 'capture'],
        ]);

        register_rest_route('easycredit/v1', '/refund', $routeArgs + [
            'methods' => 'POST',
            'callback' => [$this, 'refund'],
        ]);
    }

    public function get_transactions(\WP_REST_Request $request)
    {
        $transactionIds = $request->get_param('ids');

        $response = $this->integration->merchant_client()
            ->apiMerchantV3TransactionGet(null, null, null, 100, null, null, null, null, [
                'tId' => $transactionIds,
            ]);

        return $this->respondWithJson($response);
    }

    public function get_transaction(\WP_REST_Request $request)
    {
        $transactionId = $request->get_param('id');

        $response = $this->integration->merchant_client()
            ->apiMerchantV3TransactionTransactionIdGet($transactionId);

        return $this->respondWithJson($response);
    }

    public function capture(\WP_REST_Request $request)
    {
        try {
            $transactionId = $request->get_param('id');
            $requestData = json_decode($request->get_body());

            $response = $this->integration->merchant_client()
                ->apiMerchantV3TransactionTransactionIdCapturePost(
                    $transactionId,
                    new CaptureRequest([
                        'trackingNumber' => $request->get_json_params()['trackingNumber'],
                    ])
                );

            return $this->respondWithJson($response);
        } catch (ApiException $e) {
            return $this->respondWithJson($e->getResponseBody(), $e->getCode());
        } catch (\Throwable $e) {
            return $this->respondWithJson([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function refund(\WP_REST_Request $request)
    {
        try {
            $transactionId = $request->get_param('id');
            $requestData = json_decode($request->get_body());

            $response = $this->integration->merchant_client()
                ->apiMerchantV3TransactionTransactionIdRefundPost(
                    $transactionId,
                    new RefundRequest([
                        'value' => $request->get_json_params()['value'],
                    ])
                );

            return $this->respondWithJson($response);
        } catch (ApiException $e) {
            return $this->respondWithJson($e->getResponseBody(), $e->getCode());
        } catch (\Throwable $e) {
            return $this->respondWithJson([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function respondWithJson($content, $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($content);
        exit;
    }
}
