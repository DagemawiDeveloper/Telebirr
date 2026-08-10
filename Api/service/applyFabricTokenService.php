<?php

class ApplyFabricToken
{
    public $BASE_URL;
    public $fabricAppId;
    public $appSecret;
    public $merchantAppId;

    public function __construct($BASE_URL, $fabricAppId, $appSecret, $merchantAppId)
    {
        $this->BASE_URL = rtrim($BASE_URL, '/');
        $this->fabricAppId = $fabricAppId;
        $this->appSecret = $appSecret;
        $this->merchantAppId = $merchantAppId;
    }

    /**
     * Request a fabric token from the payment gateway.
     */
    public function applyFabricToken()
    {
        $ch = curl_init();

        $headers = array(
            'Content-Type: application/json',
            'X-APP-Key: ' . $this->fabricAppId,
        );

        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->BASE_URL . '/payment/v1/token',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(array('appSecret' => $this->appSecret)),
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
            throw new RuntimeException('Unable to request Telebirr fabric token: ' . $message);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Telebirr fabric token request failed with HTTP ' . $status . '.');
        }

        return $response;
    }
}
