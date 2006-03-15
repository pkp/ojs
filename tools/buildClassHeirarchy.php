<?php

function scanDir($path, $omitList) {
	$returner = array();

	if (in_array($path, $omitList)) return $returner;

	$fd = opendir($path);
	while (($fn = readdir($fd)) !== false) {
		if ($fn === '.' || $fn === '..') {
		} elseif (is_dir($path . DIRECTORY_SEPARATOR . $fn)) {
			$returner = array_merge($returner, scanDir($path . DIRECTORY_SEPARATOR . $fn, $omitList));
		} elseif (substr($fn, -4, 4) === '.php') {
			$returner[] = $path . DIRECTORY_SEPARATOR . $fn;
		}

	}
	closedir($fd);
	return $returner;
}

function getClassNames($fn) {
	$returner = array();
	foreach (file($fn) as $line) {
		$matches = array();
		if (preg_match_all('/class ([^ ]+) (extends ([^ ]+) )?{/', $line, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				if (isset($match[3]) && !empty($match[3])) {
					array_push($returner, array($match[1], $match[3]));
				} else {
					array_push($returner, $match[1]);
				}
			}
		}
	}
	return $returner;
}

function printTree($thisClass, $classes, $level = 0) {
	$thisBatch = array();
	foreach ($classes as $class) {
		if (is_array($class) && $class[1] === $thisClass) {
			array_push($thisBatch, $class[0]);
		}
	}
	asort($thisBatch);
	for ($i=0; $i<$level; $i++) echo "\t";
	echo $thisClass . "\n";
	foreach ($thisBatch as $subclass) {
		printTree($subclass, $classes, $level + 1);
	}
}

echo "Finding files...\n";
$phpList = scanDir('.', array(
	'./cache',
	'./lib'
));

echo "Reading classes...\n";
$classes = array();
foreach ($phpList as $item) {
	$classes = array_merge($classes, getClassNames($item));
}

echo "Buildling class list...\n";
$definedClasses = array();
foreach ($classes as $class) {
	if (is_array($class)) {
		array_push($definedClasses, $class[0]);
	} else {
		array_push($definedClasses, $class);
	}
}
$definedClasses = array_unique($definedClasses);

$rootClasses = $definedClasses;
foreach ($classes as $class) {
	if (is_array($class)) {
		if (in_array($class[1], $definedClasses)) {
			if (($i = array_search($class[0], $rootClasses)) !== false) {
				array_splice($rootClasses, $i, 1);
			}
		}
	}
}

asort($rootClasses);

foreach ($rootClasses as $class) {
	printTree($class, $classes);
}
?>
