{**
 * @file plugins/generic/objectsForReview/templates/author/objectsForReviewAssignments.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display page for all author's objects for review.
 *
 *}
{assign var="pageTitle" value="$pageTitle"}
{include file="common/header.tpl"}

<div id="authorObjectsForReview">

<ul class="menu">
	<li{if $returnPage == 'all'} class="current"{/if}><a href="{url op="objectsForReview" path="all"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.all"}</a></li>
	{if $mode == $smarty.const.OFR_MODE_FULL}
		<li{if $returnPage == 'requested'} class="current"{/if}><a href="{url op="objectsForReview" path="requested"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.requested"} ({$counts[$smarty.const.OFR_STATUS_REQUESTED]|escape})</a></li>
		<li{if $returnPage == 'assigned'} class="current"{/if}><a href="{url op="objectsForReview" path="assigned"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.assigned"} ({$counts[$smarty.const.OFR_STATUS_ASSIGNED]|escape})</a></li>
		<li{if $returnPage == 'mailed'} class="current"{/if}><a href="{url op="objectsForReview" path="mailed"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.mailed"} ({$counts[$smarty.const.OFR_STATUS_MAILED]|escape})</a></li>
	{/if}
	<li{if $returnPage == 'submitted'} class="current"{/if}><a href="{url op="objectsForReview" path="submitted"}">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.submitted"} ({$counts[$smarty.const.OFR_STATUS_SUBMITTED]|escape})</a></li>
</ul>

{include file="../plugins/generic/objectsForReview/templates/author/objectsForReviewAssignmentsList.tpl"}

</div>

{include file="common/footer.tpl"}
