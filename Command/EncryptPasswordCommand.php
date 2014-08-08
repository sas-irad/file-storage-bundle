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
        ->addOption('path', null, InputOption::VALUE_OPTIONAL, "Path for resulting password file")
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output) {

        $root_dir = $this->getContainer()->get('kernel')->getRootDir();
        
        $pw_file = $input->getOption('path');
        
        if ( !$pw_file ) {
            $pw_file = "$root_dir/config/pw.txt"; 
        } else {
            if ( strpos($pw_file, '~') !== false ) {
                throw new \Exception("Tilde characters in a path are not supported by this script.");
            }
        }
        
        // create our password directory if it doesn't exist
        $dir = dirname($pw_file);
        if ( !is_dir(dirname($pw_file)) ) {
            if ( !mkdir($dir, 0770, true) ) {
                throw new \Exception("Unable to create directory $dir");
            }            
        }

        $pw_file = realpath($dir) . '/' . basename($pw_file);
        
        if ( file_exists($pw_file) ) {
            throw new \Exception("Password file \"$pw_file\" already exists.");
        }
        
        $dialog    = $this->getHelperSet()->get('dialog');
        $password1 = null;
        while ( !$password1 ) {
            $password1 = $dialog->askHiddenResponse($output, 'Enter password to encrypt: ');
            if ( !$password1 ) {
                echo "** Nothing entered **\n";
            }
        }
        $password2 = $dialog->askHiddenResponse($output, 'Retype password to confirm: ');
        
        if ( $password1 !== $password2 ) {
            throw new \Exception("Password entries did not match!");
        }
        
        $encrypted = $this->getContainer()->get('storage.encrypted');
        
        $storage = $encrypted->init($pw_file); 
        $storage->save($password1);
        
        $output->writeln("Generated file: $pw_file");
    }

}