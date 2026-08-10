<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Build the canonical request string and sign it with the merchant RSA key.
 */
function sign($request)
{
    $excludeFields = array('sign', 'sign_type', 'header', 'refund_info', 'openType', 'raw_request');
    $data = $request;
    ksort($data);

    $parts = array();

    foreach ($data as $key => $values) {
        if (in_array($key, $excludeFields, true)) {
            continue;
        }

        if ('biz_content' === $key && is_array($values)) {
            foreach ($values as $field => $value) {
                $parts[] = $field . '=' . $value;
            }
        } else {
            $parts[] = $key . '=' . $values;
        }
    }

    sort($parts, SORT_STRING);

    return SignWithRSA(implode('&', $parts));
}

/**
 * Retained for backwards compatibility with the original sample API.
 */
function sortedString($stringApplet)
{
    $parts = explode('&', $stringApplet);
    sort($parts, SORT_STRING);

    return implode('&', $parts);
}

/**
 * Generate a SHA-256 RSA signature using a private key supplied at runtime.
 *
 * TELEBIRR_PRIVATE_KEY_PATH must point to a PEM private key outside the
 * repository, for example /run/secrets/telebirr_private_key.pem.
 */
function SignWithRSA($data)
{
    $privateKeyPath = getenv('TELEBIRR_PRIVATE_KEY_PATH');

    if (!$privateKeyPath || !is_readable($privateKeyPath)) {
        throw new RuntimeException(
            'TELEBIRR_PRIVATE_KEY_PATH must point to a readable merchant private key.'
        );
    }

    $privateKey = file_get_contents($privateKeyPath);
    if (false === $privateKey || '' === trim($privateKey)) {
        throw new RuntimeException('Unable to read the Telebirr merchant private key.');
    }

    $rsa = new Crypt_RSA();

    if (true !== $rsa->loadKey($privateKey)) {
        throw new RuntimeException('Unable to load the Telebirr merchant private key.');
    }

    $rsa->setHash('sha256');
    $rsa->setMGFHash('sha256');

    return base64_encode($rsa->sign($data));
}

/**
 * Kept for compatibility with code that may still call the original helper.
 */
function trimPrivateKey($stringData)
{
    return explode('-----', (string) $stringData);
}

function createMerchantOrderId()
{
    return (string) time();
}

function createTimeStamp()
{
    return (string) time();
}

/**
 * Generate a cryptographically secure 32-character hexadecimal nonce.
 */
function createNonceStr()
{
    return bin2hex(random_bytes(16));
}
