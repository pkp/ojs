{**
 * templates/workflow/galley.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Accordion with galley grid and related actions.
 *}
{assign var="galleyId" value=$galley->getId()}

{url|assign:galleyFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.galley.GalleyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() articleGalleyId=$galleyId escape=false}
{assign var=galleyContainerId value='galleyFilesGrid-'|concat:$galleyId|concat:'-'|uniqid}
{load_url_in_div id=$galleyContainerId url=$galleyFilesGridUrl}
