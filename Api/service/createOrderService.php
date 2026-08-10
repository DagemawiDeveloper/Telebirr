<?php

require_once __DIR__ . '/applyFabricTokenService.php';
require_once dirname(__DIR__) . '/utils/tool.php';
require_once dirname(__DIR__) . '/config/env.php';

class CreateOrderService
{
    public $req;
    public $BASE_URL;
    public $fabricAppId;
    public $appSecret;
    public $merchantAppId;
    public $merchantCode;
    public $notify_path;

    public function __construct($baseUrl, $req, $fabricAppId, $appSecret, $merchantAppId, $merchantCode)
    {
        $this->BASE_URL = rtrim($baseUrl, '/');
        $this->req = $req;
        $this->fabricAppId = $fabricAppId;
        $this->appSecret = $appSecret;
        $this->merchantAppId = $merchantAppId;
        $this->merchantCode = $merchantCode;

        $configuredNotifyUrl = getenv('TELEBIRR_NOTIFY_URL');
        if ($configuredNotifyUrl) {
            $this->notify_path = $configuredNotifyUrl;
        } else {
            $serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
            $this->notify_path = 'https://' . $serverName . '/api/payment.php';
        }
    }

    /**
     * Create a payment order and return the raw request expected by the client.
     */
    public function createOrder()
    {
        $title = $this->req->title;
        $amount = $this->req->amount;

        $tokenService = new ApplyFabricToken(
            $this->BASE_URL,
            $this->fabricAppId,
            $this->appSecret,
            $this->merchantAppId
        );

        $tokenResponse = json_decode($tokenService->applyFabricToken());

        if (!isset($tokenResponse->token) || !$tokenResponse->token) {
            throw new RuntimeException('Telebirr token response did not contain a token.');
        }

        $createOrderResponse = json_decode(
            $this->requestCreateOrder($tokenResponse->token, $title, $amount)
        );

        if (!isset($createOrderResponse->biz_content->prepay_id)) {
            throw new RuntimeException('Telebirr pre-order response did not contain a prepay_id.');
        }

        echo trim((string) $this->createRawRequest($createOrderResponse->biz_content->prepay_id));
    }

    /**
     * Send the signed pre-order request to the Telebirr gateway.
     */
    public function requestCreateOrder($fabricToken, $title, $amount)
    {
        $ch = curl_init();

        $headers = array(
            'Content-Type: application/json',
            'X-APP-Key: ' . $this->fabricAppId,
            'Authorization: ' . $fabricToken,
        );

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->BASE_URL . '/payment/v1/merchant/preOrder',
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $this->createRequestObject($title, $amount),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ));

        $response = curl_exec($ch);

        if (false === $response) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Unable to create Telebirr order: ' . $message);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Telebirr order request failed with HTTP ' . $status . '.');
        }

        return $response;
    }

    public function createMerchantOrderId_()
    {
        return (string) time();
    }

    /**
     * Build and sign the Telebirr pre-order object.
     */
    public function createRequestObject($title, $amount)
    {
        $request = array(
            'nonce_str' => createNonceStr(),
            'method' => 'payment.preorder',
            'timestamp' => createTimeStamp(),
            'version' => '1.0',
            'biz_content' => array(),
        );

        $request['biz_content'] = array(
            'notify_url' => $this->notify_path,
            'business_type' => getenv('TELEBIRR_BUSINESS_TYPE') ?: 'BuyGoods',
            'trade_type' => getenv('TELEBIRR_TRADE_TYPE') ?: 'InApp',
            'appid' => $this->merchantAppId,
            'merch_code' => $this->merchantCode,
            'merch_order_id' => $this->createMerchantOrderId_(),
            'title' => $title,
            'total_amount' => $amount,
            'trans_currency' => getenv('TELEBIRR_CURRENCY') ?: 'ETB',
            'timeout_express' => getenv('TELEBIRR_TIMEOUT_EXPRESS') ?: '120m',
            'payee_identifier' => getenv('TELEBIRR_PAYEE_IDENTIFIER') ?: '',
            'payee_identifier_type' => getenv('TELEBIRR_PAYEE_IDENTIFIER_TYPE') ?: '',
            'payee_type' => getenv('TELEBIRR_PAYEE_TYPE') ?: '',
        );

        $request['sign_type'] = 'SHA256WithRSA';
        $request['sign'] = sign($request);

        return json_encode($request);
    }

    /**
     * Build the raw request passed to the payment client/H5 flow.
     */
    public function createRawRequest($prepayId)
    {
        $values = array(
            'appid' => $this->merchantAppId,
            'merch_code' => $this->merchantCode,
            'nonce_str' => createNonceStr(),
            'prepay_id' => $prepayId,
            'timestamp' => createTimeStamp(),
            'sign_type' => 'SHA256WithRSA',
        );

        $parts = array();
        foreach ($values as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        return implode('&', $parts) . '&sign=' . sign($values);
    }
}
