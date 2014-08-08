<?php

use SAS\IRAD\FileStorageBundle\Storage\EncryptedFileStorage;

class EncryptedFileStorageTest extends PHPUnit_Framework_TestCase {
    
    private $test_root;
    private $keys;
    
    public function setup() {
        
        $this->test_root = dirname(__DIR__);
        
        $this->keys = array('keys' => 
                        array('public'  => $this->test_root . "/Resources/public.pem",
                              'private' => $this->test_root . "/Resources/private.pem"));
    }
    
    /**
     * Simple read test
     */
    public function testGetFile() {
        
        $testReadPath = $this->test_root . "/Resources/encrypted-text.txt";
        $fileContents = "This is the content in my file!"; 
        
        $storage = new EncryptedFileStorage($testReadPath, $this->keys);
        
        $this->assertEquals($fileContents, $storage->get($fileContents));
    }

    /**
     * Simple write test
     */
    public function testSaveFile() {
    
        $testWritePath = "/tmp/encrypted-write-test.txt";
        $fileContents = "Write encrypted content to file";
    
        $storage = new EncryptedFileStorage($testWritePath, $this->keys);
        $storage->save($fileContents);
        
        $this->assertEquals($fileContents, $storage->get($fileContents));
        
        unlink($testWritePath);
    }
    
    
}