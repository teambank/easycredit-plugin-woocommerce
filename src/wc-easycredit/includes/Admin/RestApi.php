<?php
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Admin;

use Teambank\RatenkaufByEasyCreditApiV3\ApiException;
use Teambank\RatenkaufByEasyCreditApiV3\Model\CaptureRequest;
use Teambank\RatenkaufByEasyCreditApiV3\Model\RefundRequest;
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

        add_action('rest_api_init', function() {
            if (is_user_logged_in() && (current_user_can('shop_manager') ||
                current_user_can('administrator')
            )) {
                $this->register_routes();
            }
        });
    }
    
    public function register_routes()
    {
        register_rest_route('easycredit/v1', '/transactions', [
            'methods' => 'GET',
            'callback' => [$this, 'get_transactions'],
            'permission_callback' => '__return_true', // // allow for anybody as routes are only registered in admin
        ]);

        register_rest_route('easycredit/v1', '/transaction', [
            'methods' => 'GET',
            'callback' => [$this, 'get_transaction'],
            'permission_callback' => '__return_true', // // allow for anybody as routes are only registered in admin
        ]);

        register_rest_route('easycredit/v1', '/capture', [
            'methods' => 'POST',
            'callback' => [$this, 'capture'],
            'permission_callback' => '__return_true', // allow for anybody as routes are only registered in admin
        ]);

        register_rest_route('easycredit/v1', '/refund', [
            'methods' => 'POST',
            'callback' => [$this, 'refund'],
            'permission_callback' => '__return_true', // allow for anybody as routes are only registered in admin
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
