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

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
