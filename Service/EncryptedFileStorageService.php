<?php

namespace SAS\IRAD\FileStorageBundle\Service;

use SAS\IRAD\FileStorageBundle\Storage\EncryptedFileStorage;

class EncryptedFileStorageService {
    
    private $params;
    
    public function __construct($params) {
        $this->params = $params;
    }
    
    public function init($path) {
        return new EncryptedFileStorage($path, $this->params);
    }
}