<?php

/**
 * @file classes/db/DAORegistry.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DAORegistry
 * @ingroup db
 * @see DAO
 *
 * @brief Maintains a static list of DAO objects so each DAO is instantiated only once.
 */


import('lib.pkp.classes.db.DAO');

class DAORegistry {

	/**
	 * Get the current list of registered DAOs.
	 * This returns a reference to the static hash used to
	 * store all DAOs currently instantiated by the system.
	 * @return array
	 */
	static function &getDAOs() {
		$daos =& Registry::get('daos', true, array());
		return $daos;
	}

	/**
	 * Register a new DAO with the system.
	 * @param $name string The name of the DAO to register
	 * @param $dao object A reference to the DAO to be registered
	 * @return object A reference to previously-registered DAO of the same
	 *    name, if one was already registered; null otherwise
	 */
	static function registerDAO($name, $dao) {
		$daos =& DAORegistry::getDAOs();
		if (isset($daos[$name])) {
			$returner = $daos[$name];
		} else {
			$returner = null;
		}
		$daos[$name] = $dao;
		return $returner;
	}

	/**
	 * Retrieve a reference to the specified DAO.
	 * @param $name string the class name of the requested DAO
	 * @param $dbconn ADONewConnection optional
	 * @return DAO
	 */
	static function &getDAO($name, $dbconn = null) {
		$daos =& DAORegistry::getDAOs();
		if (!isset($daos[$name])) {
			// Import the required DAO class.
			$application = PKPApplication::getApplication();
			$className = $application->getQualifiedDAOName($name);
			if (!$className) {
				fatalError('Unrecognized DAO ' . $name . '!');
			}

			// Only instantiate each class of DAO a single time
			$daos[$name] =& instantiate($className, array('DAO', 'XMLDAO'));
			if ($dbconn != null) {
				$daos[$name]->setDataSource($dbconn);
			}
		}

		return $daos[$name];
	}
}

?>
