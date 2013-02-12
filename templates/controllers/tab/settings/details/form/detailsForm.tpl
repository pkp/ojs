{**
 * templates/controllers/tab/settings/details/form/detailsForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of journal setup.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#detailSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="detailSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="details"}">

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="detailsFormNotification"}
{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

<div id="generalInformation">
<h3>1.1 {translate key="manager.setup.generalInformation"}</h3>

<table class="data">
	{if $categoriesEnabled}
		<tr>
			<td class="label">{fieldLabel name=categories key="manager.setup.categories"}</td>
			<td class="value">
				<select id="categories" name="categories[]" class="selectMenu" multiple="multiple">
					{html_options options=$allCategories selected=$categories}
				</select>
				<br/>
				{translate key="manager.setup.categories.description"}
			</td>
		</tr>
	{/if}{* $categoriesEnabled *}
</table>
</div>

<div class="separator"></div>
<div id="setupPublisher">
<h3>1.5 {translate key="manager.setup.publisher"}</h3>

<p>{translate key="manager.setup.publisherDescription"}</p>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="publisherNote" key="manager.setup.note"}</td>
		<td class="value">
			<textarea name="publisherNote[{$formLocale|escape}]" id="publisherNote" rows="5" cols="40" class="textArea richContent">{$publisherNote[$formLocale]|escape}</textarea>
			<br/>
			<span class="instruct">{translate key="manager.setup.publisherNoteDescription"}</span>
			</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="publisherInstitution" key="manager.setup.institution"}</td>
		<td class="value"><input type="text" name="publisherInstitution" id="publisherInstitution" value="{$publisherInstitution|escape}" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="publisherUrl" key="common.url"}</td>
		<td class="value"><input type="text" name="publisherUrl" id="publisherUrl" value="{$publisherUrl|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
</table>
</div>
<div class="separator"></div>

<h3>1.9 {translate key="manager.setup.history"}</h3>

<p>{translate key="manager.setup.historyDescription"}</p>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="history" key="manager.setup.history"}</td>
		<td class="value">
			<textarea name="history[{$formLocale|escape}]" id="history" rows="5" cols="40" class="textArea richContent">{$history[$formLocale]|escape}</textarea>
		</td>
	</tr>
</table>

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
