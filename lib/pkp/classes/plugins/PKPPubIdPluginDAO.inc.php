<?php

/**
 * @file classes/plugins/PKPPubIdPluginDAO.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPubIdPluginDAO
 * @ingroup plugins
 *
 * @brief Interface that DAOs would need to implement in order for pub ID support to be added.
 */

interface PKPPubIdPluginDAO {

	/**
	 * Checks if public identifier exists (other than for the specified
	 * submission ID, which is treated as an exception).
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $submissionId int An ID to be excluded from the search.
	 * @param $contextId int
	 * @return boolean
	 */
	function pubIdExists($pubIdType, $pubId, $submissionId, $contextId);

	/**
	 * Change the public ID of a submission.
	 * @param $submissionId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function changePubId($submissionId, $pubIdType, $pubId);

	/**
	 * Delete the public ID of a submission.
	 * @param $submissionId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deletePubId($submissionId, $pubIdType);

	/**
	 * Delete the public IDs of all submissions in this context.
	 * @param $contextId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deleteAllPubIds($contextId, $pubIdType);


}

?>
