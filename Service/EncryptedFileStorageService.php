<?php

namespace SAS\IRAD\FileStorageBundle\Service;

use SAS\IRAD\FileStorageBundle\Storage\EncryptedFileStorage;

class EncryptedFileStorageService {
    
    public function init($path, $params) {
        return new EncryptedFileStorage($path, $params);
    }
}