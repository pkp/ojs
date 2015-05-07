{**
 * templates/workflow/galley.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Accordion with galley grid and related actions.
 *}
{assign var="representationId" value=$representation->getId()}

{url|assign:representationFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.galley.GalleyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() articleGalleyId=$representationId escape=false}
{assign var=representationContainerId value='representationFilesGrid-'|concat:$representationId|concat:'-'|uniqid}
{load_url_in_div id=$representationContainerId url=$representationFilesGridUrl}
