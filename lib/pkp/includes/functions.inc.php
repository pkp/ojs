<?php

/**
 * @file includes/functions.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup index
 *
 * @brief Contains definitions for common functions used system-wide.
 * Any frequently-used functions that cannot be put into an appropriate class should be added here.
 */


/**
 * Emulate a Java-style import statement.
 * Simply includes the associated PHP file (using require_once so multiple calls to include the same file have no effect).
 * @param $class string the complete name of the class to be imported (e.g. 'lib.pkp.classes.core.Core')
 */
if (!function_exists('import')) {
	function import($class) {
		$filePath = str_replace('.', '/', $class) . '.inc.php';
		require_once(BASE_SYS_DIR.'/'.$filePath);
	}
}

/**
 * Wrapper around die() to pretty-print an error message with an optional stack trace.
 */
function fatalError($reason) {
	// Because this method may be called when checking the value of the show_stacktrace
	// configuration string, we need to ensure that we don't get stuck in an infinite loop.
	static $isErrorCondition = null;
	static $showStackTrace = false;

	if ($isErrorCondition === null) {
		$isErrorCondition = true;
		$showStackTrace = Config::getVar('debug', 'show_stacktrace');
		$isErrorCondition = false;
	}

	echo "<h1>" . htmlspecialchars($reason) . "</h1>";

	if ($showStackTrace) {
		echo "<h4>Stack Trace:</h4>\n";
		$trace = debug_backtrace();

		// Remove the call to fatalError from the call trace.
		array_shift($trace);

		// Back-trace pretty-printer adapted from the following URL:
		// http://ca3.php.net/manual/en/function.debug-backtrace.php
		// Thanks to diz at ysagoon dot com

		// FIXME: Is there any way to localize this when the localization
		// functions may have caused the failure in the first place?
		foreach ($trace as $bt) {
			$args = '';
			if (isset($bt['args'])) foreach ($bt['args'] as $a) {
				if (!empty($args)) {
					$args .= ', ';
				}
				switch (gettype($a)) {
					case 'integer':
					case 'double':
						$args .= $a;
						break;
					case 'string':
						$a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
						$args .= "\"$a\"";
						break;
					case 'array':
						$args .= 'Array('.count($a).')';
						break;
					case 'object':
						$args .= 'Object('.get_class($a).')';
						break;
					case 'resource':
						$args .= 'Resource('.strstr($a, '#').')';
						break;
					case 'boolean':
						$args .= $a ? 'True' : 'False';
						break;
					case 'NULL':
						$args .= 'Null';
						break;
					default:
						$args .= 'Unknown';
				}
			}
			$class = isset($bt['class'])?$bt['class']:'';
			$type = isset($bt['type'])?$bt['type']:'';
			$function = isset($bt['function'])?$bt['function']:'';
			$file = isset($bt['file'])?$bt['file']:'(unknown)';
			$line = isset($bt['line'])?$bt['line']:'(unknown)';

			echo "<strong>File:</strong> {$file} line {$line}<br />\n";
			echo "<strong>Function:</strong> {$class}{$type}{$function}($args)<br />\n";
			echo "<br/>\n";
		}
	}

	// Determine the application name. Use defensive code so that we
	// can handle errors during early application initialization.
	$application = null;
	if (class_exists('Registry')) {
		$application = Registry::get('application', true, null);
	}
	$applicationName = '';
	if (!is_null($application)) {
		$applicationName = $application->getName().': ';
	}

	error_log($applicationName.$reason);

	if (defined('DONT_DIE_ON_ERROR') && DONT_DIE_ON_ERROR == true) {
		// trigger an error to be catched outside the application
		trigger_error($reason);
		return;
	}

	die();
}

/**
 * Check to see if the server meets a minimum version requirement for PHP.
 * @param $version Name of version (see version_compare documentation)
 * @return boolean
 */
function checkPhpVersion($version) {
	return (version_compare(PHP_VERSION, $version) !== -1);
}

/**
 * Instantiates an object for a given fully qualified
 * class name after executing several checks on the class.
 *
 * The checks prevent certain vulnerabilities when
 * instantiating classes generically.
 *
 * NB: We currently only support one constructor
 * argument. If we need arbitrary arguments later
 * we can do that via func_get_args() which allows us
 * to handle an arbitrary number of optional
 * constructor arguments. The $constructorArg
 * parameter needs to be last in the parameter list
 * to be forward compatible with this potential use
 * case.
 *
 * @param $fullyQualifiedClassName string
 * @param $expectedTypes string|array the class
 * 	must conform to at least one of the given types.
 * @param $expectedPackages string|array the class
 *  must be part of at least one of the given packages.
 * @param $expectedMethods string|array names of methods
 *  that must all be present for the requested class.
 * @param $constructorArg mixed constructor argument
 *
 * @return object|boolean the instantiated object or false
 *  if the class instantiation didn't result in the expected
 *  type.
 */
function &instantiate($fullyQualifiedClassName, $expectedTypes = null, $expectedPackages = null, $expectedMethods = null, $constructorArg = null) {
	$errorFlag = false;

	// Validate the class name
	if (!preg_match('/^[a-zA-Z0-9.]+$/', $fullyQualifiedClassName)) {
		return $errorFlag;
	}

	// Validate the class package
	if (!is_null($expectedPackages)) {
		if (is_scalar($expectedPackages)) $expectedPackages = array($expectedPackages);
		$validPackage = false;
		foreach ($expectedPackages as $expectedPackage) {
			// No need to use String class here as class names are always US-ASCII
			if (substr($fullyQualifiedClassName, 0, strlen($expectedPackage)+1) == $expectedPackage.'.') {
				$validPackage = true;
				break;
			}
		}

		// Raise a fatal error if the class does not belong
		// to any of the expected packages. This is to prevent
		// certain types of code inclusion attacks.
		if (!$validPackage) {
			// Construct meaningful error message.
			$expectedPackageCount = count($expectedPackages);
			$separator = '';
			foreach($expectedPackages as $expectedPackageIndex => $expectedPackage) {
				if ($expectedPackageIndex > 0) {
					$separator = ($expectedPackageIndex == $expectedPackageCount-1 ? ' or ' : ', ' );
				}
				$expectedPackageString .= $separator.'"'.$expectedPackage.'"';
			}
			fatalError('Trying to instantiate class "'.$fullyQualifiedClassName.'" which is not in any of the expected packages '.$expectedPackageString.'.');
		}
	}

	// Import the requested class
	import($fullyQualifiedClassName);

	// Identify the class name
	$fullyQualifiedClassNameParts = explode('.', $fullyQualifiedClassName);
	$className = array_pop($fullyQualifiedClassNameParts);

	// Type check I: The requested class should be declared by now.
	if (!class_exists($className)) {
		fatalError('Cannot instantiate class. Class "'.$className.'" is not declared in "'.$fullyQualifiedClassName.'".');
	}

	// Ensure all expected methods are declared.
	$expectedMethods = (array) $expectedMethods; // Possibly scalar or null; ensure array
	$declaredMethods = get_class_methods($className);
	if (count(array_intersect($expectedMethods, $declaredMethods)) != count($expectedMethods)) {
		return $errorFlag;
	}

	// Instantiate the requested class
	if (is_null($constructorArg)) {
		$classInstance = new $className();
	} else {
		$classInstance = new $className($constructorArg);
	}

	// Type check II: The object must conform to the given interface (if any).
	if (!is_null($expectedTypes)) {
		if (is_scalar($expectedTypes)) $expectedTypes = array($expectedTypes);
		$validType = false;
		foreach($expectedTypes as $expectedType) {
			if (is_a($classInstance, $expectedType)) {
				$validType = true;
				break;
			}
		}
		if (!$validType) return $errorFlag;
	}

	return $classInstance;
}

/**
 * Remove empty elements from an array
 * @param $array array
 * @return array
 */
function arrayClean(&$array) {
	if (!is_array($array)) return null;
	return array_filter($array, create_function('$o', 'return !empty($o);'));
}


/**
 * Recursively strip HTML from a (multidimensional) array.
 * @param $values array
 * @return array the cleansed array
 */
function stripAssocArray($values) {
	foreach ($values as $key => $value) {
		if (is_scalar($value)) {
			$values[$key] = strip_tags($values[$key]);
		} else {
			$values[$key] = stripAssocArray($values[$key]);
		}
	}
	return $values;
}

/**
 * Perform a code-safe strtolower, i.e. one that doesn't behave differently
 * based on different locales. (tr_TR, I'm looking at you.)
 * @param $str string Input string
 * @return string
 */
function strtolower_codesafe($str) {
	return strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
}

/**
 * Convert a Windows path to a cygwin path.
 * @param string $path Windows path
 * @return string Cygwin path.
 */
function cygwinConversion($path) {
	$path = str_replace('\\', '/', $path);
	$matches = null;
	PKPString::regexp_match_get('/^([A-Z]):/i', $path, $matches);
	if (isset($matches[1]) && strlen($matches[1]) === 1) {
		$path = PKPString::regexp_replace('/^[A-Z]:/i', '/cygdrive/' . strtolower($matches[1]), $path);
	}
	return $path;
}
?>
