{**
 * templates/workflow/reviewHistory.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review history for a particular review assignment.
 *}

{if $reviewAssignment}
	<div class="pkp_review_history">
		{if $reviewAssignment->getDateAssigned() != ''}
			<div>
				<strong>{$reviewAssignment->getDateAssigned()|date_format:$datetimeFormatShort}</strong>
				{translate key="common.assigned"}
			</div>
		{/if}
		{if $reviewAssignment->getDateNotified() != ''}
			<div>
				<strong>{$reviewAssignment->getDateNotified()|date_format:$datetimeFormatShort}</strong>
				{translate key="common.notified"}
			</div>
		{/if}
		{if $reviewAssignment->getDateReminded() != ''}
			<div>
				<strong>{$reviewAssignment->getDateReminded()|date_format:$datetimeFormatShort}</strong>
				{translate key="common.reminder"}
			</div>
		{/if}
		{if $reviewAssignment->getDateConfirmed() != ''}
			<div>
				<strong>{$reviewAssignment->getDateConfirmed()|date_format:$datetimeFormatShort}</strong>
				{translate key="common.confirm"}
			</div>
		{/if}
		{if $reviewAssignment->getDateCompleted() != ''}
			<div>
				<strong>{$reviewAssignment->getDateCompleted()|date_format:$datetimeFormatShort}</strong>
				{translate key="common.completed"}
			</div>
		{/if}
		{if $reviewAssignment->getDateAcknowledged() != ''}
			<div>
				<strong>{$reviewAssignment->getDateAcknowledged()|date_format:$datetimeFormatShort}</strong>
				{translate key="common.acknowledged"}
			</div>
		{/if}
	</div>
{/if}
