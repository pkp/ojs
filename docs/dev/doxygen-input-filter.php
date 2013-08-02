#!/usr/bin/php
<?php
$source = file_get_contents($argv[1]);

$regexp = '#\@var\s+([^\s]+)([^/]+)/\s+(var|public|protected|private)\s+(\$[^\s;=]+)#';
$replac = '${2} */ ${3} ${1} ${4}';
$source = preg_replace($regexp, $replac, $source);

echo $source;
