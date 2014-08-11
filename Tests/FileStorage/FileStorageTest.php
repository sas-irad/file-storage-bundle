<?php

use SAS\IRAD\FileStorageBundle\Storage\FileStorage;

class FileStorageTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Simple read test
     */
    public function testGetFile() {
        
        $testReadPath = "/tmp/file-storage-test-read";
        $fileContents = "This is the content in my file!"; 
        
        if ( !file_put_contents($testReadPath, $fileContents) ) {
            throw new Exception("Error creating test file: $testReadPath");
        }
        
        $storage = new FileStorage($testReadPath);
        
        $this->assertEquals($fileContents, $storage->get());

        unlink($testReadPath);
    }
    
    
    /**
     * Simple write test
     */
    public function testSaveFile() {

        $testWritePath = "/tmp/file-storage-test-read";
        $fileContents  = "This is the content in my file!";
        
        $storage = new FileStorage($testWritePath);
        $storage->save($fileContents);
        
        $this->assertEquals($fileContents, file_get_contents($testWritePath));
        
        unlink($testWritePath);        
    }
    
    /**
     * Test files/paths which are not writable
     */
    public function testFilePathException() {
        
        $testPath = "/etc/file-storage-test.txt";
        $storage = new FileStorage($testPath);
        
        try {
            $storage->save("This should raise an exception");
        } catch (Exception $e) {
            return;
        }
        $this->fail("Expected an exception initializing object to $testPath (are you running as root?)");
    }
    
    /**
     * Test save() exception when file is locked for write
     */
    public function testSaveFileLockWrite() {
        
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
        
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }        
        
        $fh = fopen($testWritePath, 'w');
        if ( !$fh ) {
            throw new Exception("Error opening test file for write: $testWritePath");
        }
        
        if ( !flock($fh, LOCK_EX) ) {
            throw new Exception("Error locking test file for write: $testWritePath");
        }
        
        $storage = new FileStorage($testWritePath);
        
        try {
            $storage->save($fileContents);
        } catch (Exception $e) {
            return;
        }
        
        fclose($fh);
        unlink($testWritePath);
        $this->fail("Expected an exception trying to save to a locked file");
    }

    /**
     * Test save() exception when file is locked for read
     */
    public function testSaveFileLockRead() {
    
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
    
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }
    
        $fh = fopen($testWritePath, 'r');
        if ( !$fh ) {
            throw new Exception("Error opening test file for read: $testWritePath");
        }
    
        if ( !flock($fh, LOCK_SH) ) {
            throw new Exception("Error locking test file for share: $testWritePath");
        }
    
        $storage = new FileStorage($testWritePath);
    
        try {
            $storage->save($fileContents);
        } catch (Exception $e) {
            return;
        }
    
        fclose($fh);
        unlink($testWritePath);
        $this->fail("Expected an exception trying to save to a locked file");
    }    
    
    
    /**
     * Test get() exception when file is locked for write
     */
    public function testGetFileLockWrite() {
        
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
        
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }        
        
        $fh = fopen($testWritePath, 'w');
        if ( !$fh ) {
            throw new Exception("Error opening test file for write: $testWritePath");
        }
        
        if ( !flock($fh, LOCK_EX) ) {
            throw new Exception("Error locking test file for write: $testWritePath");
        }
        
        $storage = new FileStorage($testWritePath);
        
        try {
            $data = $storage->get();
        } catch (Exception $e) {
            return;
        }
        
        fclose($fh);
        unlink($testWritePath);
        $this->fail("Expected an exception trying to get() a locked file");
    }

    /**
     * Test get() when file is locked for read
     */
    public function testGetFileLockRead() {
    
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
    
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }
    
        $fh = fopen($testWritePath, 'r');
        if ( !$fh ) {
            throw new Exception("Error opening test file for read: $testWritePath");
        }
    
        if ( !flock($fh, LOCK_SH) ) {
            throw new Exception("Error locking test file for share: $testWritePath");
        }
    
        $storage = new FileStorage($testWritePath);
        $this->assertEquals($fileContents, $storage->get());
    
        fclose($fh);
        unlink($testWritePath);
    }    
    
    
    /**
     * Test getAndHold() to ensure file is properly locked
     */
    public function testGetAndHold() {
    
        $testWritePath = "/tmp/file-storage-test-write";
        $fileContents  = "This is the content in my file!";
    
        if ( !file_put_contents($testWritePath, $fileContents) ) {
            throw new Exception("Error creating test file: $testWritePath");
        }
    
        $storage = new FileStorage($testWritePath);
        
        // did we read the file correctly
        $this->assertEquals($fileContents, $storage->getAndHold());
        
        // the file should have an exclusive lock on it now
        $fh = fopen($testWritePath, 'r');
        if ( !$fh ) {
            throw new Exception("Error opening test file for read: $testWritePath");
        }
    
        // try to get a non-blocking lock (without non_block, we'll wait forever!)
        if ( flock($fh, LOCK_SH | LOCK_NB) ) {
            throw new Exception("Shouldn't be able to lock test file for share: $testWritePath");
        }

        if ( flock($fh, LOCK_EX | LOCK_NB) ) {
            throw new Exception("Shouldn't be able to lock test file for write: $testWritePath");
        }
        
        // update the file
        $fileContents  = "This is the updated content in my file!";
        $storage->saveAndRelease($fileContents);
        
        // file on disk should be updated
        $this->assertEquals($fileContents, file_get_contents($testWritePath));
        
        // file locks should now work again
            if ( !flock($fh, LOCK_SH) ) {
            throw new Exception("Error locking test file for share: $testWritePath");
        }
                
        if ( !flock($fh, LOCK_EX) ) {
            throw new Exception("Error locking test file for write: $testWritePath");
        }
                
    
        fclose($fh);
        unlink($testWritePath);
    }    
    

}