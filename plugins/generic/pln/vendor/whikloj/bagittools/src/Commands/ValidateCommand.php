<?php

namespace whikloj\BagItTools\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use whikloj\BagItTools\Bag;
use whikloj\BagItTools\BagItException;

/**
 * Command to validate a bag.
 * @package whikloj\BagItTools\Commands
 */
class ValidateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('validate')
            ->setDescription('Validate a BagIt bag.')
            ->setHelp("Point at a bag file or directory, increase verbosity for more information.")
            ->addArgument(
                'bag-path',
                InputArgument::REQUIRED,
                'Path to the bag directory or file'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        // TODO: This is simplified in Console 5.0, remove this then.
        if ($output instanceof ConsoleOutputInterface) {
            $error_io = new SymfonyStyle($input, $output->getErrorOutput());
        } else {
            $error_io = $io;
        }

        $path = $input->getArgument('bag-path');
        if ($path[0] !== DIRECTORY_SEPARATOR) {
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
            $realpath = realpath($path);
        }
        if ((isset($realpath) && $realpath === false) || !file_exists($path)) {
            $error_io->error("Path {$path} does not exist, cannot validate.");
        } else {
            try {
                if (isset($realpath) && $realpath !== false) {
                    $path = $realpath;
                }
                $bag = Bag::load($path);
                $valid = $bag->validate();
                $verbose = $output->getVerbosity();
                if ($verbose >= OutputInterface::VERBOSITY_VERBOSE) {
                    // Print warnings
                    $warnings = $bag->getWarnings();
                    foreach ($warnings as $warning) {
                        $error_io->warning("{$warning['message']} -- file: {$warning['file']}");
                    }
                }
                if ($verbose >= OutputInterface::VERBOSITY_VERBOSE) {
                    // Print errors
                    $errors = $bag->getErrors();
                    foreach ($errors as $error) {
                        $error_io->error("{$error['message']} -- file: {$error['file']}");
                    }
                }
                if ($verbose >= OutputInterface::VERBOSITY_NORMAL) {
                    if ($valid) {
                        $io->success("Bag is valid");
                    } else {
                        $io->warning("Bag is NOT valid");
                    }
                }
                return($valid ? 0 : 1);
            } catch (BagItException $e) {
                $error_io->error("Exception: {$e->getMessage()}");
            }
        }
        return(1);
    }
}
