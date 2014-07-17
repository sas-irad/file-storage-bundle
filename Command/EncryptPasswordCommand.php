<?php

namespace SAS\IRAD\FileStorageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EncryptPasswordCommand extends ContainerAwareCommand {

    protected function configure() {

        $this
        ->setName('storage:encrypt-pw')
        ->setDescription('Generate an encrypted password file.')
        ->addOption('keys', null, InputOption::VALUE_OPTIONAL, "Path for key files")
        ->addOption('path', null, InputOption::VALUE_OPTIONAL, "Path for resulting password file")
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $root_dir = $this->getContainer()->get('kernel')->getRootDir();
        $key_path = $input->getOption('keys');
        
        if ( !$key_path ) {
            $key_path = "$root_dir/config/keys";
        }

        $pw_file = $input->getOption('path');
        
        if ( !$pw_file ) {
            $pw_file = "$root_dir/config/pw.txt"; 
        }
        
        // create our "keys" directory if it doesn't exist
        if ( !is_dir(dirname($pw_file)) ) {
            mkdir($key_path, 0770, true);
        }
        
        // path for public and private keys
        $public_key_path  = "$key_path/public.pem";
        $private_key_path = "$key_path/private.pem";

        // don't overwrite password file if it already exists
        if ( !file_exists($public_key_path) || !file_exists($private_key_path) ) {
            throw new \Exception("Public/private key not found.");
        }
        
        if ( file_exists($pw_file) ) {
            throw new \Exception("Password file \"$pw_file\" already exists.");
        }
        
        $dialog    = $this->getHelperSet()->get('dialog');
        $password1 = $dialog->askHiddenResponse($output, 'Enter password to encrypt: ');
        $password2 = $dialog->askHiddenResponse($output, 'Retype password to confirm: ');
        
        if ( $password1 !== $password2 ) {
            throw new \Exception("Password entries did not match!");
        }
        
        $encrypted = $this->getContainer()->get('file.encrypted_storage');
        
        $keys = array('public_key'  => $public_key_path, 
                      'private_key' => $private_key_path);
        
        $storage = $encrypted->init($pw_file, $keys); 
        $storage->save($password);
        
        chmod($pw_file, 0660);
        
        $output->writeln("Done.");
    }

}