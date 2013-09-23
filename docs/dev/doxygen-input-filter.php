#!/usr/bin/php
<?php
$source = file_get_contents($argv[1]);

/**
 * For var declarations; adapted from:
 *  http://stackoverflow.com/questions/4325224/doxygen-how-to-describe-class-member-variables-in-php
 */
// @var (one)( notslash)/ (var|public|protected|private) ($symbolic)
$source = preg_replace(
	// (@var)   (type)  (descr*)/   (access flag?)                     ($variableName)
	'#(\@var)\s+([^\s]+)(.*?)\*/\s+(var|public|protected|private|static)\s+(\$[^\s;=]+)#s',
	// @var type variableName descr
	'
	 *${3}
	 * ${1} ${2} ${5}
	 */
	${4} ${5}',
	$source
);

/* For @covers declarations (not supported in Doxygen?) */
$source = preg_replace(
	'#\@covers#',
	'@see',
	$source
);

echo $source;
