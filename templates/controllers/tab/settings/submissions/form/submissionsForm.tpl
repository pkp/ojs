{**
 * templates/controllers/tab/settings/submissions/form/submissionsForm.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of journal setup.
 *
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#submissionSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="submissionSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="submissions"}">

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="submissionsFormNotification"}
{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}


<div class="separator"></div>

<div id="competingInterests">
<h3>3.3 {translate key="manager.setup.competingInterests"}</h3>

<p>{translate key="manager.setup.competingInterests.description"}</p>

<table class="data">
	<tr>
		<td class="label" width="5%">
			<input type="checkbox" name="requireAuthorCompetingInterests" id="requireAuthorCompetingInterests" value="1"{if $requireAuthorCompetingInterests} checked="checked"{/if} />
		</td>
		<td class="value" width="95%">
			<label for="requireAuthorCompetingInterests">{translate key="manager.setup.competingInterests.requireAuthors"}</label>
		</td>
	</tr>
	<tr>
		<td class="label">
			<input type="checkbox" name="requireReviewerCompetingInterests" id="requireReviewerCompetingInterests" value="1"{if $requireReviewerCompetingInterests} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="requireReviewerCompetingInterests">{translate key="manager.setup.competingInterests.requireReviewers"}</label>
		</td>
	</tr>
</table>

<div class="separator"></div>

<div id="publicationScheduling">
<h3>4.2 {translate key="manager.setup.publicationScheduling"}</h3>
<div id="publicationSchedule">
<h4>{translate key="manager.setup.publicationSchedule"}</h4>

<p>{translate key="manager.setup.publicationScheduleDescription"}</p>

<p><textarea name="pubFreqPolicy[{$formLocale|escape}]" id="pubFreqPolicy" rows="12" cols="60" class="textArea richContent">{$pubFreqPolicy[$formLocale]|escape}</textarea></p>
</div>

<div class="separator"></div>

<div id="publicIdentifier">
<h3>4.3 {translate key="manager.setup.publicIdentifier"}</h3>
<div id="uniqueIdentifier">
<h4>{translate key="manager.setup.uniqueIdentifier"}</h4>

<p>{translate key="manager.setup.uniqueIdentifierDescription"}</p>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="enablePublicIssueId" id="enablePublicIssueId" value="1"{if $enablePublicIssueId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicIssueId">{translate key="manager.setup.enablePublicIssueId"}</label></td>
	</tr>
	<tr>
		<td class="label"><input type="checkbox" name="enablePublicArticleId" id="enablePublicArticleId" value="1"{if $enablePublicArticleId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicArticleId">{translate key="manager.setup.enablePublicArticleId"}</label></td>
	</tr>
	<tr>
		<td class="label"><input type="checkbox" name="enablePublicGalleyId" id="enablePublicGalleyId" value="1"{if $enablePublicGalleyId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicGalleyId">{translate key="manager.setup.enablePublicGalleyId"}</label></td>
	</tr>
	<tr>
		<td class="label"><input type="checkbox" name="enablePublicSuppFileId" id="enablePublicSuppFileId" value="1"{if $enablePublicSuppFileId} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePublicSuppFileId">{translate key="manager.setup.enablePublicSuppFileId"}</label></td>
	</tr>
</table>
</div><!-- uniqueIdentifier -->
<br />
<div id="pageNumberIdentifier">
<h4>{translate key="manager.setup.pageNumberIdentifier"}</h4>

<table class="data">
	<tr>
		<td width="5%" class="label"><input type="checkbox" name="enablePageNumber" id="enablePageNumber" value="1"{if $enablePageNumber} checked="checked"{/if} /></td>
		<td class="value"><label for="enablePageNumber">{translate key="manager.setup.enablePageNumber"}</label></td>
	</tr>
</table>
</div><!-- pageNumberIdentifier -->
</div><!-- publicIdentifier -->

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
