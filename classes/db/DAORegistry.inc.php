<?php

/**
 * DAORegistry.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package db
 *
 * Class for retrieving DAO objects.
 * Maintains a static list of DAO objects so each DAO is instantiated only once. 
 *
 * $Id$
 */

class DAORegistry {

	/**
	 * Retrieve a reference to the specified DAO.
	 * @param $name string the class name of the requested DAO
	 * @return DAO
	 */
	function &getDAO($name) {
		static $daos;
		
		if (!isset($daos)) {
			$daos = array();
		}
		
		if (!isset($daos[$name])) {
			// Only instantiate each class of DAO a single time
			$daos[$name] = &new $name();
		}
		
		return $daos[$name];
	}
	
}

?>
