<?php

namespace SAS\IRAD\FileStorageBundle\Service;

use SAS\IRAD\FileStorageBundle\Storage\FileDecrypter;

class FileDecrypterService {
    
    public function init($path) {
        return new FileDecrypter($path);
    }
}