<?php

namespace SAS\IRAD\FileStorageBundle\Service;

use SAS\IRAD\FileStorageBundle\Storage\FileStorage;

class FileStorageService {
    
    public function init($path) {
        return new FileStorage($path);
    }
}