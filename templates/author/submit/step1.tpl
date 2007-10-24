{**
 * step1.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of author article submission.
 *
 * $Id$
 *}
{assign var="pageTitle" value="author.submit.step1"}
{include file="author/submit/submitHeader.tpl"}

{if $journalSettings.supportPhone}
	{assign var="howToKeyName" value="author.submit.howToSubmit"}
{else}
	{assign var="howToKeyName" value="author.submit.howToSubmitNoPhone"}
{/if}

<p>{translate key=$howToKeyName supportName=$journalSettings.supportName supportEmail=$journalSettings.supportEmail supportPhone=$journalSettings.supportPhone}</p>

<div class="separator"></div>

{if count($sectionOptions) <= 1}
<p>{translate key="author.submit.notAccepting"}</p>
{else}

<h3>{translate key="author.submit.journalSection"}</h3>

{url|assign:"url" page="about"}
<p>{translate key="author.submit.journalSectionDescription" aboutUrl=$url}</p>

<form name="submit" method="post" action="{url op="saveSubmit" path=$submitStep}" onsubmit="return checkSubmissionChecklist()">

{if $articleId}
<input type="hidden" name="articleId" value="{$articleId}" />
{/if}
<input type="hidden" name="submissionChecklist" value="1" />
{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">	
	<td width="20%" class="label">{fieldLabel name="sectionId" required="true" key="section.section"}</td>
	<td width="80%" class="value"><select name="sectionId" id="sectionId" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$sectionId}</select></td>
</tr>
	
</table>

<div class="separator"></div>

<script type="text/javascript">
{literal}
<!--
function checkSubmissionChecklist() {
	var elements = document.submit.elements;
	for (var i=0; i < elements.length; i++) {
		if (elements[i].type == 'checkbox' && !elements[i].checked) {
			if (elements[i].name.match('^checklist')) {
				alert({/literal}'{translate|escape:"jsparam" key="author.submit.verifyChecklist"}'{literal});
				return false;
			} else if (elements[i].name == 'copyrightNoticeAgree') {
				alert({/literal}'{translate|escape:"jsparam" key="author.submit.copyrightNoticeAgreeRequired"}'{literal});
				return false;
			}
		}
	}
	return true;
}
// -->
{/literal}
</script>

{if $currentJournal->getLocalizedSetting('submissionChecklist')}

{foreach name=checklist from=$currentJournal->getLocalizedSetting('submissionChecklist') key=checklistId item=checklistItem}
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

{if $currentJournal->getLocalizedSetting('copyrightNotice') != ''}
<h3>{translate key="about.copyrightNotice"}</h3>

<p>{$currentJournal->getLocalizedSetting('copyrightNotice')|nl2br}</p>

{if $journalSettings.copyrightNoticeAgree}
<table width="100%" class="data">
	<tr valign="top">
		<td width="5%"><input type="checkbox" name="copyrightNoticeAgree" id="copyrightNoticeAgree" value="1"{if $articleId || $copyrightNoticeAgree} checked="checked"{/if} /></td>
		<td width="95%"><label for="copyrightNoticeAgree">{translate key="author.submit.copyrightNoticeAgree"}</label></td>
	</tr>
</table>
{/if}

<div class="separator"></div>
{/if}

<h3>{translate key="author.submit.privacyStatement"}</h3>
<br />
{$currentJournal->getLocalizedSetting('privacyStatement')|nl2br}

<div class="separator"></div>

<h3>{translate key="author.submit.commentsForEditor"}</h3>
<table width="100%" class="data">

<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="commentsToEditor" key="author.submit.comments"}</td>
	<td width="80%" class="value"><textarea name="commentsToEditor" id="commentsToEditor" rows="3" cols="40" class="textArea">{$commentsToEditor|escape}</textarea></td>
</tr>

</table>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="{if $articleId}confirmAction('{url page="author"}', '{translate|escape:"jsparam" key="author.submit.cancelSubmission"}'){else}document.location.href='{url page="author" escape=false}'{/if}" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{/if}{* If not accepting submissions *}

{include file="common/footer.tpl"}
