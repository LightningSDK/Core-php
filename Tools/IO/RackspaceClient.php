<?php

namespace Lightning\Tools\IO;

include HOME_PATH . '/Source/Vendor/rackspace_client/vendor/autoload.php';

use \Exception;
use Lightning\Tools\Configuration;
use OpenCloud\Rackspace;

class RackspaceClient {
    protected $client;

    /**
     * @var \OpenCloud\ObjectStore\Resource\DataObject
     */
    protected $object;

    /**
     * @var \OpenCloud\ObjectStore\Service
     */
    protected $service;
    protected $root;

    public function __construct($root) {
        $this->root = $root;
    }

    protected function connect() {
        if (empty($this->client)) {
            $configuration = Configuration::get('rackspace');
            $this->client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
                'username' => $configuration['username'],
                'apiKey'   => $configuration['key']
            ));
            $this->service = $this->client->objectStoreService(null, 'DFW');
        }
    }

    public function exists($file) {
        $this->connect();
        $remoteName = self::getRemoteName($this->root . '/' . $file);
        $container = $this->service->getContainer($remoteName[0]);
        return $container->objectExists($remoteName[1]);
    }

    public function read($file) {
        $this->connect();
        $remoteName = $this->getRemoteName($this->root . '/' . $file);
        $container = $this->service->getContainer($remoteName[0]);
        $this->object = $container->getObject($remoteName[1]);
        return $this->object->getContent();
    }

    public function write($file, $contents) {
        $this->connect();
        $remoteName = self::getRemoteName($this->root . '/' . $file);
        $container = $this->service->getContainer($remoteName[0]);
        $this->object = $container->uploadObject($remoteName[1], $contents);
    }

    public function getWebURL($file) {
        $remoteName = self::getRemoteName($this->root . '/' . $file);
        $remoteName[0] = Configuration::get('containers.' . $remoteName[0] . '.url');
        return $remoteName[0] . (preg_match('|^/|', $remoteName[1]) ? '' : '/') . $remoteName[1];
    }

    public function relativeFilename($absoluteUrl) {
        $remoteName = self::getRemoteName($this->root);
        $remoteName[0] = Configuration::get('containers.' . $remoteName[0] . '.url');
        return str_replace($remoteName[0] . '/' . $remoteName[1] . '/', '', $absoluteUrl);
    }


    protected static function getRemoteName($remoteName) {
        $remoteName = explode(':', $remoteName);
        preg_replace('|^/|', '', $remoteName[1]);
        return $remoteName;
    }

    /**
     * @TODO: Can this be renamed to "copyFile"
     */
    public function uploadFile($file, $remoteName) {
        $this->connect();
        $remoteName = $this->getRemoteName($this->root . '/' . $remoteName);
        $container = $this->service->getContainer($remoteName[0]);
        $fh = fopen($file, 'r');
        if ($fh) {
            $this->object = $container->uploadObject($remoteName[1], $fh);
        } else {
            throw new Exception('File not found.');
        }
        return !empty($this->object);
    }

    public function getURL($remoteName) {
        $remoteName = $this->getRemoteName($this->root . '/' . $remoteName);
        $container = $this->service->getContainer($remoteName[0]);
        $this->object = $container->getPartialObject($remoteName[1]);
        return $this->object->getPublicUrl();
    }

    public function getFileContents($remoteName) {
        $this->connect();
        $remoteName = $this->getRemoteName($this->root . '/' . $remoteName);
        $container = $this->service->getContainer($remoteName[0]);
        $this->object = $container->getObject($remoteName[1]);
        return $this->object->getContent();
    }

    public function getFileSize($remoteName) {
        $this->connect();
        $remoteName = $this->getRemoteName($this->root . '/' . $remoteName);
        $container = $this->service->getContainer($remoteName[0]);
        $this->object = $container->getPartialObject($remoteName[1]);
        return $this->object->getContentLength();
    }

    public function getModificationDate() {
        return $this->object->getLastModified();
    }
}
