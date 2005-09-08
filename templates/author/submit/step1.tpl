{**
 * step1.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of author article submission.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit.step1"}
{include file="author/submit/submitHeader.tpl"}

<p>{translate key="author.submit.howToSubmit" supportName=$journalSettings.supportName supportEmail=$journalSettings.supportEmail supportPhone=$journalSettings.supportPhone}</p>

<div class="separator"></div>

{if count($sectionOptions) <= 1}
<p>{translate key="author.submit.notAccepting"}</p>
{else}

<script type="text/javascript">
{literal}
<!--
function checkSubmissionChecklist() {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].type == 'checkbox' && elements[i].name.match('^checklist') && !elements[i].checked) {
			alert({/literal}'{translate|escape:"javascript" key="author.submit.verifyChecklist"}'{literal});
			return false;
		}
	}
	return true;
}
// -->
{/literal}
</script>

<form name="submit" method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}" onsubmit="return checkSubmissionChecklist()">

{if $articleId}
<input type="hidden" name="articleId" value="{$articleId}" />
{/if}
<input type="hidden" name="submissionChecklist" value="1" />
{include file="common/formErrors.tpl"}

{if $journalSettings.submissionChecklist}

{foreach name=checklist from=$journalSettings.submissionChecklist key=checklistId item=checklistItem}
	{if $checklistItem.content}
		{if !$notFirstChecklistItem}
			{assign var=notFirstChecklistItem value=1}
			<h3>{translate key="author.submit.submissionChecklist"}</h3>
			<p>{translate key="author.submit.submissionChecklistDescription"}</p>
			<table width="100%" class="data">
		{/if}
		<tr valign="top">
			<td width="5%"><input type="checkbox" id="checklist-{$smarty.foreach.checklist.iteration}" name="checklist[]" value="{$checklistId}"{if $articleId || $submissionChecklist} checked="checked"{/if} /></td>
			<td width="95%"><label for="checklist-{$smarty.foreach.checklist.iteration}">{$checklistItem.content|nl2br}</label></td>
		</tr>
	{/if}
{/foreach}

{if $notFirstChecklistItem}
	</table>
	<div class="separator"></div>
{/if}

{/if}

<h3>{translate key="author.submit.journalSection"}</h3>

<p>{translate key="author.submit.journalSectionDescription" aboutUrl=`$pageUrl`/about}</p>


<table class="data" width="100%">
<tr valign="top">	
	<td width="20%" class="label">{fieldLabel name="sectionId" required="true" key="section.section"}</td>
	<td width="80%" class="value"><select name="sectionId" id="sectionId" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$sectionId}</select></td>
</tr>
	
</table>

<div class="separator"></div>

<h3>{translate key="author.submit.commentsForEditor"}</h3>
<table width="100%" class="data">

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="commentsToEditor" key="author.submit.comments"}</td>
	<td width="80%" class="value"><textarea name="commentsToEditor" id="commentsToEditor" rows="3" cols="40" class="textArea">{$commentsToEditor|escape}</textarea></td>
</tr>

</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $articleId}confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}'){else}document.location.href='{$pageUrl}/author'{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{/if}

{include file="common/footer.tpl"}
