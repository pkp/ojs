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

{assign var="pageId" value="author.submit.step1"}
{include file="author/submit/submitHeader.tpl"}

<div class="subTitle">{translate key="navigation.stepNumber" step=1}: {translate key="author.submit.start"}</div>

<br />

{translate key="author.submit.howToSubmit" supportName=$journalSettings.supportName supportEmail=$journalSettings.supportEmail supportPhone=$journalSettings.supportPhone}

<br /><br />

<script type="text/javascript">
{literal}
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
{/literal}
</script>

<form name="submit" method="post" action="{$pageUrl}/author/saveSubmit/{$submitStep}" onsubmit="return checkSubmissionChecklist()">
{if $articleId}
<input type="hidden" name="articleId" value="{$articleId}" />
{/if}
<input type="hidden" name="submissionChecklist" value="1" />
{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<div class="formSectionTitle">1.1 {translate key="author.submit.journalSection"}</div>
<div class="formSection">

<div class="formSectionDesc">{translate key="author.submit.journalSectionDescription"}</div>


<table class="form">
<tr>	
	<td class="formLabel">{formLabel name="sectionId" required="true"}{translate key="section.section"}:{/formLabel}</td>
	<td class="formField"><select name="sectionId" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$sectionId}</select></td>
</tr>
	
</table>
</div>

<br />

<div class="formSectionTitle">1.2 {translate key="author.submit.submissionChecklist"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionChecklistDescription"}</div>
<table class="form">
{foreach name=checklist from=$journalSettings.submissionChecklist key=checklistId item=checklistItem}
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="checklist[]" value="{$checklistId}"{if $articleId || $submissionChecklist} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{$checklistItem.content}</td>
</tr>
{/foreach}
</table>

</div>

<br />

<div class="formSectionTitle">1.3 {translate key="author.submit.commentsForEditor"}</div>
<div class="formSection">
<table class="form">

<tr>
	<td class="formLabel">{formLabel name="commentsToEditor"}{translate key="author.submit.comments"}:{/formLabel}</td>
	<td class="formField"><textarea name="commentsToEditor" rows="3" cols="60" class="textArea">{$commentsToEditor|escape}</textarea></td>
</tr>

</table>
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.continue"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="{if $articleId}confirmAction('{$pageUrl}/author', '{translate|escape:"javascript" key="author.submit.cancelSubmission"}'){else}document.location.href='{$pageUrl}/author'{/if}" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}
