<?php

/**
 * @file classes/plugins/HookRegistry.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HookRegistry
 * @ingroup plugins
 *
 * @brief Class for linking core functionality with plugins
 */


class HookRegistry {
	/**
	 * Get the current set of hook registrations.
	 * @param $hookName string Name of hook to optionally return
	 * @return mixed Array of all hooks or just those attached to $hookName, or
	 *   null if nothing has been attached to $hookName
	 */
	static function &getHooks($hookName = null) {
		$hooks =& Registry::get('hooks', true, array());

		if ($hookName) {
			if (isset($hooks[$hookName])) {
				$hook =& $hooks[$hookName];
			} else {
				$hook = null;
			}
			return $hook;
		}

		return $hooks;
	}

	/**
	 * Set the hooks table for the given hook name to the supplied array
	 * of callbacks.
	 * @param $hookName string Name of hook to set
	 * @param $callbacks array Array of callbacks for this hook
	 */
	static function setHooks($hookName, $callbacks) {
		$hooks =& HookRegistry::getHooks();
		$hooks[$hookName] =& $callbacks;
	}

	/**
	 * Clear hooks registered against the given name.
	 * @param $hookName string Name of hook
	 */
	static function clear($hookName) {
		$hooks =& HookRegistry::getHooks();
		unset($hooks[$hookName]);
		return $hooks;
	}

	/**
	 * Register a hook against the given hook name.
	 * @param $hookName string Name of hook to register against
	 * @param $callback object Callback pseudotype
	 */
	static function register($hookName, $callback) {
		$hooks =& HookRegistry::getHooks();
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
	static function call($hookName, $args = null) {
		// Called only by Unit Test
		// The implementation is a bit quirky as this has to work when
		// executed statically.
		if (self::rememberCalledHooks(true)) {
			// Remember the called hooks for testing.
			$calledHooks =& HookRegistry::getCalledHooks();
			$calledHooks[] = array(
				$hookName, $args
			);
		}

		$hooks =& HookRegistry::getHooks();
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


	//
	// Methods required for testing only.
	//
	/**
	 * Set/query the flag that triggers storing of
	 * called hooks.
	 * @param $askOnly boolean When set to true, the flag will not
	 *   be changed but only returned.
	 * @param $updateTo boolean When $askOnly is set to 'true' then
	 *   this parameter defines the value of the flag.
	 * @return boolean The current value of the flag.
	 */
	static function rememberCalledHooks($askOnly = false, $updateTo = true) {
		static $rememberCalledHooks = false;
		if (!$askOnly) {
			$rememberCalledHooks = $updateTo;
		}
		return $rememberCalledHooks;
	}

	/**
	 * Switch off the function to store hooks and delete all stored hooks.
	 * Always call this after using otherwise we get a severe memory.
	 * @param $leaveAlive boolean Set this to true if you only want to
	 *   delete hooks stored so far but if you want to record future
	 *   hook calls, too.
	 */
	static function resetCalledHooks($leaveAlive = false) {
		if (!$leaveAlive) HookRegistry::rememberCalledHooks(false, false);
		$calledHooks =& HookRegistry::getCalledHooks();
		$calledHooks = array();
	}

	/**
	 * Return a reference to the stored hooks.
	 * @return array
	 */
	static function &getCalledHooks() {
		static $calledHooks = array();
		return $calledHooks;
	}
}

?>
