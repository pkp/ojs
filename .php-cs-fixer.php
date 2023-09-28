<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    // The next two rules are enabled by default, kept for clarity
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    // The pattern is matched against each found filename, thus:
    // - The "/" is needed to avoid having "vendor" match "Newsvendor.php"
    // - The presence of "node_modules" here doesn't prevent the Finder from recursing on it, so we merge these paths below at the "exclude()"
    ->notPath($ignoredDirectories = ['cypress/', 'js/', 'locale/', 'node_modules/', 'styles/', 'templates/', 'vendor/'])
    // Ignore root based directories
    ->exclude(array_merge($ignoredDirectories, ['cache', 'dbscripts', 'docs', 'lib', 'public', 'registry', 'schemas']))
    // Ignores Git folders
    ->notPath((function () {
        $recursiveIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/plugins', FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS | FilesystemIterator::CURRENT_AS_FILEINFO),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $recursiveIterator->setMaxDepth(1);
        $gitFolders = new CallbackFilterIterator(
            $recursiveIterator,
            fn (SplFileInfo $file) => $recursiveIterator->getDepth() === $recursiveIterator->getMaxDepth()
                && $file->isDir()
                // Covers submodules (.git file) and external repositories (.git directory)
                && file_exists("{$file}/.git")
        );
        $folders = [];
        foreach ($gitFolders as $folder) {
            $folders[] = str_replace(__DIR__ . '/', '', $folder);
        }
        return $folders;
    })());

$rules = include  __DIR__ . '/lib/pkp/.php_cs_rules';
require(__DIR__ . '/lib/pkp/classes/dev/fixers/bootstrap.php');
$config = new PhpCsFixer\Config();
return $config->setRules($rules)
    ->registerCustomFixers(new PKP\dev\fixers\Fixers())
    ->setFinder($finder);
