<?php
/**
 * Adapted and simplified from Google_Cache_File
 * robertom@sas.upenn.edu
 */

/*
 * Copyright 2008 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace SAS\IRAD\FileStorageBundle\Storage;

class FileStorage {
    
    const MAX_LOCK_RETRIES = 10;
    private $data = false;
    private $path;
    private $temp_path;
    private $fh;
    
    /**
     * Constructor
     * @param string $path the full file path for the storage file
     */
    public function __construct($path) {

        $this->path = $path;
        
        // can we write to the file or its directory?
        if ( !is_writable($this->path) && !is_writable(dirname($this->path)) ) {
            throw new \Exception("Storage file {$this->path} is not writable.");
        }        
    }
    
    /**
     * Retrieve the contents of the storage file
     * @return string
     */
    public function get() {
        
        if ( $this->data ) {
            return $this->data;
        }
        
        if ( file_exists($this->path) ) {
            if ( $this->acquireReadLock() ) {
                $this->data = fread($this->fh, filesize($this->path));
                $this->close();
            }
        }
        
        return $this->data;
    }
    
    /**
     * Acquire an exclusive lock on the storage file, retrieve the contents,
     * and leave the file locked for a later update.
     * @return string
     */
    public function getAndHold() {
    
        if ( file_exists($this->path) ) {
            if ( $this->acquireExclusiveLock() ) {
                $this->data = fread($this->fh, filesize($this->path));
            }
        }
    
        return $this->data;
    }
    
    
    /**
     * Save $data to the storage file
     * @param unknown $data
     */
    public function save($data) {
        
        $this->data = $data;
        
        if ( $this->acquireWriteLock() ) {
            $result = fwrite($this->fh, $data);
            if ( $result != strlen($data) ) {
                throw new \Exception("Error writing to storage file: {$this->path}, data may be incomplete.");
            }
            $this->close();
        }
    }

    /**
     * Assumes you have already opened and read the file with getAndHold(). This
     * method writes back to the file and releases the lock.
     * @param string $data
     * @throws \Exception
     */
    public function saveAndRelease($data) {

        $this->data = $data;
        
        // write updates to our temp file
        $temp = $this->openTempFile();

        $result = fwrite($temp, $data);
        if ( $result != strlen($data) ) {
            throw new \Exception("Error writing to storage file: {$this->path}, data may be incomplete.");
        }
        fclose($temp);
        
        if ( !rename($this->temp_path, $this->path) ) {
            throw new \Exception("Error renaming tmp file");
        }        
        
        // close and remove lock
        $this->close();
        
        return true;
    }
    
    /**
     * Delete the storage file
     */
    public function delete() {
        $this->data = false;
        unlink($this->path);
    }
    
    
    /**
     * Open $this->path with a read lock
     * @return boolean
     */    
    private function acquireReadLock() {
        $rc = $this->acquireLock(LOCK_SH, 'r');
        if (!$rc) {
            throw new \Exception("Unable to lock storage file for reading: {$this->path}");
        }
        // should always return true
        return $rc;
    }
    
    /**
     * Open $this->path with a write lock
     * @return boolean
     * @throws \Exception
     */
    private function acquireWriteLock() {
        $rc = $this->acquireLock(LOCK_EX, 'w');
        if (!$rc) {
            throw new \Exception("Unable to lock storage file for writing: {$this->path}");
        }
        // should always return true
        return $rc;
    }
    
    /**
     * Open $this->path with exclusive lock for reading and writing
     * @return boolean
     * @throws \Exception
     */
    private function acquireExclusiveLock() {
        $rc = $this->acquireLock(LOCK_EX, 'r+');
        if (!$rc) {
            throw new \Exception("Unable to lock storage file for read/write: {$this->path}");
        }
        // should always return true
        return $rc;
    }    
    
    /**
     * Open $this->path with either a READ or WRITE lock. Return true on success.
     * @param CONST $type (LOCK_EX|LOCK_SH)
     * @return boolean
     */
    private function acquireLock($type, $mode) {
        
        if ( !file_exists($this->path) ) {
            touch($this->path);
            chmod($this->path, 0660);
        }
        
        $this->fh = fopen($this->path, $mode);
        $count = 0;
        while (!flock($this->fh, $type | LOCK_NB)) {
            // Sleep for 10ms.
            usleep(10000);
            if (++$count < self::MAX_LOCK_RETRIES) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Close and unlock the storage file
     */
    private function close() {
        if ($this->fh) {
            flock($this->fh, LOCK_UN);
            fclose($this->fh);
        }
    }
    
    /**
     * Open a temp file for writing
     * @return resource
     */
    private function openTempFile() {
		
        $file = basename($this->path);
        $dir  = dirname($this->path);
        
        $this->temp_path = tempnam($dir, $file . '.');
        
		$fhw = fopen($this->temp_path, 'w');
		chmod($this->temp_path, 0660);
		
		if ( !$fhw ) {
		    throw new \Exception("Error opening file '$this->temp_path'");
		}
		return $fhw;
	}
}
