<?php

namespace Lightning\CLI;

use Lightning\Tools\Security\Encryption;

class Security extends CLI {
    public function executeGenerateKeyPair() {
        $keys = Encryption::generateKeyPair(2048);
        echo 'Public: ' . Encryption::shortenKey($keys['public']) . "\n\n";
        echo $keys['public'] . "\n\n";
        echo 'Private: ' . Encryption::shortenKey($keys['private']) . "\n\n";
        echo $keys['private'] . "\n\n";
    }

    public function executeGenerateAesKey() {
        echo 'Key: ' . Encryption::generateAesKey() . "\n\n";
    }
}
