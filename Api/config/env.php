<?php
/**
 * Telebirr runtime configuration.
 *
 * Supply these values through server/container environment variables.
 * Never commit merchant credentials or private keys to the repository.
 */
$ENV_Variables = array(
    'baseUrl'       => getenv('TELEBIRR_BASE_URL') ?: '',
    'fabricAppId'   => getenv('TELEBIRR_FABRIC_APP_ID') ?: '',
    'appSecret'     => getenv('TELEBIRR_APP_SECRET') ?: '',
    'merchantAppId' => getenv('TELEBIRR_MERCHANT_APP_ID') ?: '',
    'merchantCode'  => getenv('TELEBIRR_MERCHANT_CODE') ?: '',
);
