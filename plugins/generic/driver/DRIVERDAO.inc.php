<?php

/**
 * @file plugins/generic/driver/DRIVERDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DRIVERDAO
 * @ingroup plugins_generic_driver
 *
 * @brief DAO operations for DRIVER.
 */

import('classes.oai.ojs.OAIDAO');


class DRIVERDAO extends OAIDAO {

	/**
	 * Set parent OAI object.
	 * @param JournalOAI
	 */
	function setOAI(&$oai) {
		$this->oai = $oai;
	}

	//
	// Records
	//

	/**
	 * Return set of OAI records matching specified parameters.
	 * @param $setIds array Objects ids that specify an OAI set, in this case only journal ID.
	 * @param $from int timestamp
	 * @param $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @param $funcName string
	 * @return array OAIRecord
	 */
	function &getDRIVERRecordsOrIdentifiers($setIds, $from, $until, $offset, $limit, &$total, $funcName) {
		$records = array();

		$result = $this->_getRecordsRecordSet($setIds, $from, $until, null);

		$total = $result->RecordCount();

		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row = $result->GetRowAssoc(false);
			$record = $this->_returnRecordFromRow($row);
			if(in_array('driver', $record->sets)){
				$records[] = $record;
			}
			$result->MoveNext();
		}

		$result->Close();
		return $records;
	}

}


