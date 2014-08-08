## SAS/IRAD File Storage Symfony bundle ##

This bundle provides simple file storage with locking. This is useful
if you need to read/write to a file store and you want to avoid multiple
processes (i.e., web sessions) from stomping on each other. The file store
can be saved in plaintext or encrypted. (**This encryption prevents 
casual viewing of the file contents.** It is not intended as absolute
protection. A determined person with access to the application directory
will be able decrypt the file).

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

* If you are using the encrypted file service, you'll need to configure
your public/private keys in your app/config/parameters.yml file. The parameter
format and default values are shown below:
````
parameters:
    file_storage:
        keys:
            public:   "%kernel.root_dir%/config/keys/public.pem"
            private:  "%kernel.root_dir%/config/keys/private.pem"
````
To generate public/private keys for your application, use the console command:
`````
php app/console storage:generate-keys [--path=/path/to/key/dir]
`````

There's also a console command to encrypt a password:
`````
php app/console storage:encrypt-pw [--path=/path/to/encrypted/pw/file]
`````
If no --path option is specified, the encrypted password is stored 
in app/config/pw.txt

### Symfony 2 Services ###

This bundle provides to services: 

* storage.file (plaintext storage)
* storage.encrypted (encrypted storage)

Both work in pretty much the same way:
````
// example: single user read/write process
$storage = $this->get('storage.file');
$storage->init("/path/to/file/store");

$data = $storage->get();
[ manipulate $data ]
$storage->save();
`````
`````
// example: multi-user read/write process
$storage = $this->get('storage.file');
$storage->init("/path/to/file/store");

// getAndHold() retrieves the file contents, but leaves the file
// locked so no one else can make changes
$data = $storage->getAndHold();

[ manipulate $data ]

// write the data back out and release the file lock
$storage->saveAndRelease();
`````