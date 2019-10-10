<?php

/**
 * @file classes/publication/PublicationDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationDAO
 * @ingroup core
 * @see DAO
 *
 * @brief Add OJS-specific functions for PKPPublicationDAO
 */
import('lib.pkp.classes.publication.PKPPublicationDAO');

class PublicationDAO extends PKPPublicationDAO {

	/**
	 * @copydoc SchemaDAO::_fromRow()
	 */
	public function _fromRow($primaryRow) {
		$publication = parent::_fromRow($primaryRow);
		$publication->setData('galleys', iterator_to_array(
			Services::get('galley')->getMany(['publicationIds' => $publication->getId()])
		));
		return $publication;
	}
}
