SAS/IRAD File Storage Symfony bundle

This bundle provides file locking and file decryption services.

* To use this bundle, add the repo to your composer.json file:
````
    "repositories" : [{
            "type" : "vcs",
            "url" : "https://github.com/sas-irad/file-storage-bundle"
        }
    ],
````

* Add the bundle to your "require" section:
````
    "require": {
        "sas-irad/file-storage-bundle": "[your desired version]"
    },
````
    
* Add/register FileStorageBundle in app/AppKernel.php
````
public function registerBundles()
{
    $bundles = array(
        ...
        new SAS\IRAD\FileStorageBundle\FileStorageBundle(),
````