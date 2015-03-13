<?php

namespace Lightning\Tools\Security;

class Encryption {
    public static function generateKeyPair($bits = 1024, $type = OPENSSL_KEYTYPE_RSA, $digest = 'sha512') {
        $config = array(
            'digest_alg' => $digest,
            'private_key_bits' => $bits,
            'private_key_type' => $type,
        );

        // Create the private and public key
        $res = openssl_pkey_new($config);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey['key'];

        return (array('public' => $pubKey, 'private' => $privKey));
    }

    public static function shortenKey($key) {
        $key = preg_replace('|-----.*-----|', '', $key);
        $key = str_replace("\n", '', $key);
        return $key;
    }

    public static function lengthenPublicKey($key) {
        return "-----BEGIN PUBLIC KEY-----\n"
        . wordwrap($key, 65, "\n")
        . "\n-----END PUBLIC KEY-----";
    }

    public static function lengthenPrivateKey($key) {
        return "-----BEGIN PRIVATE KEY-----\n"
            . wordwrap($key, 65, "\n")
            . "-----END PRIVATE KEY-----";
    }

    public static function publicEncrypt($pubKey, $data) {
        if (!strstr($pubKey, 'BEGIN PUBLIC KEY')) {
            $pubKey = self::lengthenPublicKey($pubKey);
        }
        $key_resource = openssl_get_publickey($pubKey);
        openssl_public_encrypt($data, $crypttext, $key_resource);
        return base64_encode($crypttext);
    }

    public static function publicDecrypt($pubKey, $data) {
        if (!strstr($pubKey, 'BEGIN PUBLIC KEY')) {
            $pubKey = self::lengthenPublicKey($pubKey);
        }
        $key_resource = openssl_get_publickey($pubKey);
        openssl_public_decrypt(base64_decode($data), $cleartext, $key_resource);
        return $cleartext;
    }

    public static function privateEncrypt($privateKey, $data) {
        if (!strstr($privateKey, 'BEGIN PRIVATE KEY')) {
            $privateKey = self::lengthenPrivateKey($privateKey);
        }
        $key_resource = openssl_get_privatekey($privateKey);
        openssl_private_encrypt($data, $crypttext, $key_resource);
        return base64_encode($crypttext);
    }

    public static function privateDecrypt($privateKey, $data) {
        if (!strstr($privateKey, 'BEGIN PRIVATE KEY')) {
            $privateKey = self::lengthenPrivateKey($privateKey);
        }
        $key_resource = openssl_get_privatekey($privateKey);
        openssl_private_decrypt(base64_decode($data), $cleartext, $key_resource);
        return $cleartext;
    }

    public static function generateAesKey() {
        return Random::getInstance()->get(32, Random::BASE64);
    }

    /**
     * Encrypt some data with AES 256
     *
     * @param string $data
     *   The data to encrypt.
     * @param string $key
     *   A base64 encoded encryption key.
     * @param string $iv64
     *   A base64 encoded 16 byte IV.
     *
     * @return string
     *   An IV and cypher text, both base64 encoded.
     */
    public static function aesEncrypt($data, $key, $iv64 = null) {
        if (!empty($iv64)) {
            $iv = base64_decode($iv64);
        } else {
            $iv = Random::getInstance()->get(16, Random::BIN);
            $iv64 = base64_encode($iv);
        }
        $key = base64_decode($key);
        return $iv64 . ':' . base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, true, $iv));
    }

    /**
     * Decrypt AES encrypted cyphertext.
     *
     * @param string $data
     *   The base64 encoded IV and cypertext separated by ':'
     * @param string $key
     *   Te base64 encoded key.
     *
     * @return null|string
     */
    public static function aesDecrypt($data, $key) {
        if (strpos($data, ':') === false) {
            return null;
        }
        list($iv, $data) = explode(':', $data);
        $iv = base64_decode($iv);
        $data = base64_decode($data);
        $key = base64_decode($key);
        return openssl_decrypt($data, 'AES-256-CBC', $key, true, $iv);
    }

    public static function checkSaltHash($data, $salt_hash) {
        list($salt, $hash) = explode(':', $salt_hash);
        return $hash == hash('sha256', $data . pack('H*', $salt));
    }
}
