{**
 * @file plugins/generic/objectsForReview/templates/editor/objectsForReviewAssignmentsAll.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display page for objects for review assignments for editor management.
 *
 *}
{assign var="pageTitle" value="$pageTitle"}
{include file="common/header.tpl"}

<div id="objectsForReview">
<ul class="menu">
	<li class="current"><a href="{url op="objectsForReview" path="all"}">{translate key="plugins.generic.objectsForReview.editor.assignments"}</a></li>
	<li><a href="{url op="objectsForReview"}">{translate key="plugins.generic.objectsForReview.editor.objectsForReview"}</a></li>
	<li><a href="{url op="objectsForReviewSettings"}">{translate key="plugins.generic.objectsForReview.settings"}</a></li>
</ul>
<br />
{if $mode == $smarty.const.OFR_MODE_FULL}
<ul class="menu">
	<li{if $returnPage == 'all'} class="current"{/if}><a href="{url op="objectsForReview" path="all"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.all"}</a></li>
	<li{if $returnPage == 'requested'} class="current"{/if}><a href="{url op="objectsForReview" path="requested"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.requested"} ({$counts[$smarty.const.OFR_STATUS_REQUESTED]|escape})</a></li>
	<li{if $returnPage == 'assigned'} class="current"{/if}><a href="{url op="objectsForReview" path="assigned"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.assigned"} ({$counts[$smarty.const.OFR_STATUS_ASSIGNED]|escape})</a></li>
	<li{if $returnPage == 'mailed'} class="current"{/if}><a href="{url op="objectsForReview" path="mailed"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.mailed"} ({$counts[$smarty.const.OFR_STATUS_MAILED]|escape})</a></li>
	<li{if $returnPage == 'submitted'} class="current"{/if}><a href="{url op="objectsForReview" path="submitted"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.submitted"} ({$counts[$smarty.const.OFR_STATUS_SUBMITTED]|escape})</a></li>
</ul>
{/if}

{include file="../plugins/generic/objectsForReview/templates/editor/objectsForReviewAssignmentsList.tpl"}

</div>

{include file="common/footer.tpl"}
