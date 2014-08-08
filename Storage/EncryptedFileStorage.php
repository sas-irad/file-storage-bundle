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
    public function __construct($path, array $params) {

        if ( !isset($params['keys']) ) {
            throw new \Exception("Required option keys for EncryptedFileStorage not passed");
        }
        
        foreach ( array('public', 'private') as $key_type ) {
            if ( !isset($params['keys'][$key_type]) ) {
                throw new \Exception("Required option keys.\"$key_type\" for EncryptedFileStorage not passed");
            }
            if ( !is_readable($params['keys'][$key_type]) ) {
                throw new \Exception("Key file \"{$params['keys'][$key_type]}\" is not readable");
            }            
        }
        
        $private_key = file_get_contents($params['keys']['private']);
        $this->privateKey = openssl_pkey_get_private($private_key);
        if ( !is_resource($this->privateKey) ) {
            throw new \Exception("Unable to parse private key: {$params['keys']['private']}");
        }
        
        $public_key = file_get_contents($params['keys']['public']);
        $this->publicKey = openssl_pkey_get_public($public_key);
        if ( !is_resource($this->publicKey) ) {
            throw new \Exception("Unable to parse public key: {$params['keys']['public']}");
        }
        
        $this->storage = new FileStorage($path);
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