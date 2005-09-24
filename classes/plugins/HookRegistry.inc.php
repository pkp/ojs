<?php

/**
 * HookRegistry.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Class for linking core functionality with plugins
 *
 * $Id$
 */

class HookRegistry {

	var $hooks;

	/**
	 * Constructor.
	 */
	function HookRegistry() {
		$this->hooks = array();
	}

	/**
	 * Return an instance of the hook registry.
	 * @return HookRegistry the hook registry object
	 */
	function &getRegistry() {
		static $instance;
		
		if (!isset($instance)) {
			$instance = new HookRegistry();
		}
		return $instance;
	}

	function register($hookName, $callback) {
		if (!isset($this->hooks[$hookName])) {
			$this->hooks[$hookName] = array();
		}
		$this->hooks[$hookName][] = &$callback;
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
		static $hooksActive = array();
		if (!isset($this->hooks[$hookName]) || isset($hooksActive[$hookName])) {
			return false;
		}

		$hooksActive[$hookName] = true;

		foreach ($this->hooks[$hookName] as $hook) {
			if ($result = call_user_func($hook, $hookName, $args)) {
				break;
			}
		}

		unset($hooksActive[$hookName]);

		return $result;
	}
}

?>
