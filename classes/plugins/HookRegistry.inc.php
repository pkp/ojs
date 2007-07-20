<?php

/**
 * HookRegistry.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Class for linking core functionality with plugins
 *
 * $Id$
 */

class HookRegistry {
	/**
	 * Get the current set of hook registrations.
	 */
	function &getHooks() {
		static $hooks = array();
		return $hooks;
	}

	/**
	 * Set the hooks table for the given hook name to the supplied array
	 * of callbacks.
	 * @param $hookName string Name of hook to set
	 * @param $hooks array Array of callbacks for this hook
	 */
	function setHooks($hookName, $hooks) {
		$hooks = &HookRegistry::getHooks();
		$hooks[$hookName] =& $hooks;
	}

	/**
	 * Clear hooks registered against the given name.
	 * @param $hookName string Name of hook
	 */
	function clear($hookName) {
		$hooks = &HookRegistry::getHooks();
		unset($hooks[$hookName]);
		return $hooks;
	}

	/**
	 * Register a hook against the given hook name.
	 * @param $hookName string Name of hook to register against
	 * @param $callback object Callback pseudotype
	 */
	function register($hookName, $callback) {
		$hooks = &HookRegistry::getHooks();
		if (!isset($hooks[$hookName])) {
			$hooks[$hookName] = array();
		}
		$hooks[$hookName][] =& $callback;
	}

	/**
	 * Call each callback registered against $hookName in sequence.
	 * The first callback that returns a value that evaluates to true
	 * will interrupt processing and this function will return its return
	 * value; otherwise, all callbacks will be called in sequence and the
	 * return value of this call will be the value returned by the last
	 * callback.
	 * @param $hookName string The name of the hook to register against
	 * @param $args string Hooks are called with this as the second param
	 * @return mixed
	 */
	function call($hookName, $args = null) {
		$hooks = &HookRegistry::getHooks();
		if (!isset($hooks[$hookName])) {
			return false;
		}

		foreach ($hooks[$hookName] as $hook) {
			if ($result = call_user_func($hook, $hookName, $args)) {
				break;
			}
		}

		return $result;
	}
}

?>
