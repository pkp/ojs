<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->name('_ide_helper')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->notPath([
        'cache',
        'cypress',
        'dbscripts',
        'docs',
        'js',
        'lib/pkp',
        'lib/ui-library',
        'locale',
        'node_modules',
        'public',
        'registry',
        'schemas',
        'styles',
        'templates',
    ]);

// Apply formatting to all plugins that are not git submodules
$pluginsDir = __DIR__ . '/plugins';
$files = scandir($pluginsDir);
foreach ($files as $file) {
    $categoryDir = "${pluginsDir}/${file}";
    if (!in_array($file, ['.', '..']) && is_dir($categoryDir)) {
        $pluginDirs = scandir($categoryDir);
        foreach ($pluginDirs as $pluginDir) {
            $fullPluginPath = join('/', [$categoryDir, $pluginDir]);
            $gitPath = join('/', [$fullPluginPath, '.git']);
            if (!in_array($pluginDir, ['.', '..']) && is_dir($fullPluginPath) && file_exists($gitPath)) {
                $finder->notPath(str_replace(__DIR__ . '/', '', $fullPluginPath));
            }
        }
    }
}

$rules = include './lib/pkp/.php_cs_rules';

$config = new PhpCsFixer\Config();
return $config->setRules($rules)
    ->setFinder($finder);
