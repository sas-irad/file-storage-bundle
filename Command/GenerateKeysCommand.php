<?php

namespace SAS\IRAD\FileStorageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class GenerateKeysCommand extends ContainerAwareCommand {

    protected function configure() {

        $this
        ->setName('storage:generate-keys')
        ->setDescription('Generate encryption keys to encrypt data files at rest in the file system.')
        ->addOption('path', null, InputOption::VALUE_OPTIONAL, "Path for key files")
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $key_path = $input->getOption('path');
        
        if ( !$key_path ) {
            $root_dir = $this->getContainer()->get('kernel')->getRootDir();
            $key_path = "$root_dir/app/config/keys";
        }
        
        // create our "keys" directory if it doesn't exist
        if ( !is_dir($key_path) ) {
            mkdir($key_path, 0770, true);
        }
        
        // path for public and private keys
        $public_key_path  = "$key_path/public.pem";
        $private_key_path = "$key_path/private.pem";

        // don't overwrite keys if they already exist
        if ( file_exists($public_key_path) || file_exists($private_key_path) ) {
            echo "Key files already exist. Please remove before generating new keys.\n";
            exit;
        }
        
        // generate keys
        $output->writeln("Generating new key...");
        $keys = openssl_pkey_new(array("private_key_bits" => 2048));
        $output->writeln("Done.");
        
        // export private key
        $private_key = null;
        openssl_pkey_export($keys, $private_key);
        
        // export public key
        $details = openssl_pkey_get_details($keys);
        $public_key = $details["key"];
        
        $output->writeln("Exporting private key to $private_key_path");
        if ( !file_put_contents($private_key_path, $private_key) ) {
            throw new \Exception("Error writing private key file: $private_key_path");
        }
        
        $output->writeln("Exporting public key to $public_key_path");
        if ( !file_put_contents($public_key_path, $public_key) ) {
            throw new \Exception("Error writing public key file: $public_key_path");
        }
        
        chmod($private_key_path, 0660);
        chmod($public_key_path,  0660);
        
        $output->writeln("Done.");
    }

}