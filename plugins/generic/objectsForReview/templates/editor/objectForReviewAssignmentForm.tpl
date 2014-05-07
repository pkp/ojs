{**
 * @file plugins/generic/objectsForReview/templates/objectForReviewAssignmentForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Object for review assignemnt form.
 *
 *}

{include file="common/header.tpl"}

<br/>

<form id="objectForReviewAssignmentForm" method="post" action="{url op="updateObjectForReviewAssignment"}" enctype="multipart/form-data">
<input type="hidden" name="assignmentId" value="{$objectForReviewAssignment->getId()|escape}" />
<input type="hidden" name="objectId" value="{$objectForReview->getId()|escape}" />
{if $returnPage}
<input type="hidden" name="returnPage" value="{$returnPage|escape}" />
{/if}
{include file="common/formErrors.tpl"}

<div id="objectForReviewAssignmentFormDetails">
<h3><a href="{url op="editObjectForReview" path=$objectForReview->getId() reviewObjectTypeId=$objectForReview->getReviewObjectTypeId()}" class="action">{$objectForReview->getTitle()|escape}</a></h3>
</div>

{if $mode == $smarty.const.OFR_MODE_FULL && $reviewer != null}
<div class="separator"></div>
<div id="objectForReviewReviewer">
<h3>{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.objectReviewer"}</h3>
{assign var=status value=$objectForReviewAssignment->getStatus()}
{assign var=statusString value=$objectForReviewAssignment->getStatusString()}
<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.status"}</td>
		<td width="80%" class="value">{translate key=$statusString}</td>
	</tr>
	{if $status == $smarty.const.OFR_STATUS_REQUESTED || $status == $smarty.const.OFR_STATUS_ASSIGNED || $status == $smarty.const.OFR_STATUS_MAILED || $status == $smarty.const.OFR_STATUS_SUBMITTED}
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.requested"}</td>
		<td width="80%" class="value">{$objectForReviewAssignment->getDateRequested()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $status == $smarty.const.OFR_STATUS_ASSIGNED || $status == $smarty.const.OFR_STATUS_MAILED || $status == $smarty.const.OFR_STATUS_SUBMITTED}
	<tr valign="top">
		<td width="20%" class="label">{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.assigned"}</td>
		<td width="80%" class="value">{$objectForReviewAssignment->getDateAssigned()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $status == $smarty.const.OFR_STATUS_MAILED || $status == $smarty.const.OFR_STATUS_SUBMITTED}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="dateMailed" key="plugins.generic.objectsForReview.objectForReviewAssignments.mailed"}</td>
		<td width="80%" class="value">{$objectForReviewAssignment->getDateMailed()|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	{/if}
	{if $status == $smarty.const.OFR_STATUS_ASSIGNED || $status == $smarty.const.OFR_STATUS_MAILED}
	<tr valign="top">
		<td class="label">{fieldLabel name="dateDue" key="plugins.generic.objectsForReview.objectForReviewAssignments.dueDate"}</td>
		<td class="value" id="dateDue">{html_select_date prefix="dateDue" all_extra="class=\"selectMenu\"" end_year="+5" time=$objectForReviewAssignment->getDateDue()}</td>
	</tr>
	{/if}
	{if $status == $smarty.const.OFR_STATUS_SUBMITTED}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="dateSubmitted" key="plugins.generic.objectsForReview.objectForReviewAssignments.submitted"}</td>
		<td width="80%" class="value">{$dateSubmitted|date_format:$dateFormatShort|default:"&mdash;"}</td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="userId" key="plugins.generic.objectsForReview.objectsForReview.objectReviewer"}</td>
		<td width="80%" class="value">
		{assign var=userId value=$reviewer->getId()}
		{if $userId}
			{assign var=userMailingAddress value=$reviewer->getMailingAddress()}
			{assign var=userCountryCode value=$reviewer->getCountry()}
			{assign var=userCountry value=$countries.$userCountryCode}
			{assign var=emailString value=$reviewer->getFullName()|concat:" <":$reviewer->getEmail():">"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl}
			{$reviewer->getFullName()|escape}&nbsp;{icon name="mail" url=$url}
		{/if}
		{if $status == $smarty.const.OFR_STATUS_REQUESTED}
			<br />
			<a href="{url op="acceptObjectForReviewAuthor" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.acceptObjectReviewer"}</a>&nbsp;|&nbsp;<a href="{url op="denyObjectForReviewAuthor" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.denyObjectReviewer"}</a>
		{elseif $status == $smarty.const.OFR_STATUS_ASSIGNED}
			<br />
			{if $objectForReview->getCopy()}
			<a href="{url op="notifyObjectForReviewMailed" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.notifyObjectMailed"}</a>&nbsp;|
			{/if}
			<a href="{url op="removeObjectForReviewAssignment" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.confirmRemoveObjectReviewer"}')">{translate key="plugins.generic.objectsForReview.editor.removeObjectReviewer"}</a>
		{elseif $status == $smarty.const.OFR_STATUS_MAILED}
			<br />
			<a href="{url op="removeObjectForReviewAssignment" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.confirmRemoveObjectReviewer"}')">{translate key="plugins.generic.objectsForReview.editor.removeObjectReviewer"}</a>
		{elseif $userId && $status == $smarty.const.OFR_STATUS_SUBMITTED}
			<br />
			<a href="{url op="removeObjectForReviewAssignment" path=$objectForReviewAssignment->getId() returnPage=$returnPage}" class="action" onclick="return confirm('{translate|escape:"jsparam" key="plugins.generic.objectsForReview.editor.confirmRemoveObjectReviewer"}')">{translate key="plugins.generic.objectsForReview.editor.removeObjectReviewer"}</a>
		{else}
			&nbsp;
		{/if}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.mailingAddress"}</td>
		<td class="value">{$userMailingAddress|nl2br|strip_unsafe_html|default:"&mdash;"}<br />{$userCountry|escape}</td>
	</tr>
</table>
</div>
{/if}

<div class="separator"></div>
<div id="objectForReviewSubmission">
<h3>{translate key="plugins.generic.objectsForReview.objectForReviewAssignments.submission"}</h3>
<table class="data" width="100%">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="articleId" key="plugins.generic.objectsForReview.objectForReviewAssignments.submission"}</td>
		<td width="80%" class="value">
		{assign var=submissionId value=$objectForReviewAssignment->getSubmissionId()}
		{if $submissionId}
			{translate key="common.id"}: {$submissionId|escape}
			<br />
			<a href="{url page="editor" op="submission" path=$submissionId}" class="action">{translate key="plugins.generic.objectsForReview.editor.edit"}</a>&nbsp;|&nbsp;
		{/if}
			<a href="{url op="selectObjectForReviewSubmission" path=$objectForReviewAssignment->getId() objectId=$objectForReviewAssignment->getObjectId() returnPage=$returnPage}" class="action">{translate key="plugins.generic.objectsForReview.editor.select"}</a>
			<input type="hidden" name="submissionId" id="submissionId" value="{$submissionId}"/>
		</td>
	</tr>
</table>
</div>


<div class="separator"></div>
<div id="objectForReviewNotes">
<h3>{translate key="plugins.generic.objectsForReview.objectForReviewAssignment.additionalNotes"}</h3>
<p>{translate key="plugins.generic.objectsForReview.objectForReviewAssignment.notesInstructions"}</p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="notes" key="plugins.generic.objectsForReview.objectForReviewAssignment.notes"}</td>
	<td width="80%" class="value"><textarea name="notes" id="notes" cols="60" rows="6" class="textArea">{$objectForReviewAssignment->getNotes()|escape}</textarea></td>
</tr>
</table>
</div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
