<?php

/**
 * @file classes/NavigationMenusDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.navigationMenus
 * @class NavigationMenusDAO
 * Operations for retrieving and modifying NavigationMenus objects.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.navigationMenus.classes.NavigationMenu');

class NavigationMenusDAO extends DAO {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Get a navigationMenu by ID
	 * @param $navigationMenuId int navigationMenu ID
	 * @param $contextId int Optional context ID
	 */
	function getById($navigationMenuId, $contextId = null) {
		$params = array((int) $navigationMenuId);
		if ($contextId) $params[] = $contextId;

		$result = $this->retrieve(
			'SELECT * FROM navigation_menus WHERE navigation_menu_id = ?'
			. ($contextId?' AND context_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Get a set of navigationMenus by context ID
	 * @param $contextId int
	 * @param $rangeInfo Object optional
	 * @return DAOResultFactory
	 */
	function getByContextId($contextId, $rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM navigation_menus WHERE context_id = ?',
			(int) $contextId,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Get a navigationMenus by path.
	 * @param $contextId int Context ID
	 * @param $path string Path
	 * @return NavigationMenu
	 */
	function getByPath($contextId, $path) {
	    $result = $this->retrieve(
	        'SELECT * FROM navigation_menus WHERE context_id = ? AND path = ?',
	        array((int) $contextId, $path)
	    );

	    $returner = null;
	    if ($result->RecordCount() != 0) {
	        $returner = $this->_fromRow($result->GetRowAssoc(false));
	    }
	    $result->Close();
	    return $returner;
	}

	/**
	 * Insert a navigation Menu.
	 * @param $navigationMenu NavigationMenu
	 * @return int Inserted navigation Menu ID
	 */
	function insertObject($navigationMenu) {
		$this->update(
			'INSERT INTO navigation_menus (context_id, path) VALUES (?, ?)',
			array(
				(int) $navigationMenu->getContextId(),
				$navigationMenu->getPath()
			)
		);

		$navigationMenu->setId($this->getInsertId());
		$this->updateLocaleFields($navigationMenu);

		return $navigationMenu->getId();
	}

	/**
	 * Update the database with a navigationMenu object
	 * @param $navigationMenu NavigationMenu
	 */
	function updateObject($navigationMenu) {
		$this->update(
			'UPDATE	navigation_menus
			SET	context_id = ?,
				path = ?
			WHERE	navigation_menu_id = ?',
			array(
				(int) $navigationMenu->getContextId(),
				$navigationMenu->getPath(),
				(int) $navigationMenu->getId()
			)
		);
		$this->updateLocaleFields($navigationMenu);
	}

	/**
	 * Delete a navigationMenu by ID.
	 * @param $navigationMenuId int
	 */
	function deleteById($navigationMenuId) {
		$this->update(
			'DELETE FROM navigation_menus WHERE navigation_menu_id = ?',
			(int) $navigationMenuId
		);
		$this->update(
			'DELETE FROM navigation_menu_settings WHERE navigation_menu_id = ?',
			(int) $navigationMenuId
		);
	}

	/**
	 * Delete a navigationMenu object.
	 * @param $navigationMenu NavigationMenu
	 */
	function deleteObject($navigationMenu) {
		$this->deleteById($navigationMenu->getId());
	}

	/**
	 * Generate a new navigationMenu object.
	 * @return NavigationMenu
	 */
	function newDataObject() {
		return new NavigationMenu();
	}

	/**
	 * Return a new navigationMenu object from a given row.
	 * @return NavigationMenu
	 */
	function _fromRow($row) {
		$navigationMenu = $this->newDataObject();
		$navigationMenu->setId($row['navigation_menu_id']);
		$navigationMenu->setPath($row['path']);
		$navigationMenu->setContextId($row['context_id']);

		$this->getDataObjectSettings('navigation_menu_settings', 'navigation_menu_id', $row['navigation_menu_id'], $navigationMenu);
		return $navigationMenu;
	}

	/**
	 * Get the insert ID for the last inserted navigationMenu.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('navigation_menus', 'navigation_menu_id');
	}

	/**
	 * Get field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'content');
	}

	/**
	 * Update the localized data for this object
	 * @param $author object
	 */
	function updateLocaleFields(&$navigationMenu) {
		$this->updateDataObjectSettings('navigation_menu_settings', $navigationMenu, array(
			'navigation_menu_id' => $navigationMenu->getId()
		));
	}
}

?>
