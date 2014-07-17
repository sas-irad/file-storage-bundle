<?php

namespace SAS\IRAD\FileStorageBundle\Storage;


class EncryptedFileStorage {

    private $publicKey;
    private $privateKey;
    private $storage;

    /**
     * Constructor
     * @param string $path the full file path for the storage file
     */
    public function __construct($path, Array $params) {

        $this->storage = new FileStorage($path);

        foreach ( array('public_key', 'private_key') as $key ) {
            if ( !isset($params[$key]) ) {
                throw new \Exception("Required option \"$key\" for EncryptedFileStorage not passed");
            }
            if ( !is_readable($params[$key]) ) {
                throw new \Exception("Key file \"$key\" is not readable");
            }            
        }
        
        $private_key = file_get_contents($params['private_key']);
        $this->privateKey = openssl_pkey_get_private($private_key);
        if ( !is_resource($this->privateKey) ) {
            throw new \Exception("Unable to parse private key: {$params['private_key']}");
        }
        
        $public_key = file_get_contents($params['public_key']);
        $this->publicKey = openssl_pkey_get_public($public_key);
        if ( !is_resource($this->publicKey) ) {
            throw new \Exception("Unable to parse public key: {$params['public_key']}");
        }
    }
    
    public function get() {
        return $this->decryptData($this->storage->get());
    }
    
    public function getAndHold() {
        return $this->decryptData($this->storage->getAndHold());
    }
    
    public function save($data) {
        return $this->storage->save($this->encryptData($data));
    }
    
    public function saveAndRelease($data) {
        return $this->storage->saveAndRelease($this->encryptData($data));
    }
    
    public function encryptData($data) {
        $encrypted = null;
        openssl_public_encrypt($data, $encrypted, $this->publicKey);
        return base64_encode($encrypted);
    }

    public function decryptData($data) {
        $decrypted = null;
        openssl_private_decrypt(base64_decode($data), $decrypted, $this->privateKey);
        return $decrypted;
    }
    
}