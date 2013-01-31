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
	<tr>
		<td class="label">{fieldLabel name="name" required="true" key="manager.setup.journalTitle"}</td>
		<td class="value"><input type="text" name="name[{$formLocale|escape}]" id="name" value="{$name[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="acronym" required="true" key="manager.setup.journalInitials"}</td>
		<td class="value"><input type="text" name="acronym[{$formLocale|escape}]" id="acronym" value="{$acronym[$formLocale]|escape}" size="8" maxlength="16" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="abbreviation" key="manager.setup.journalAbbreviation"}</td>
		<td class="value"><input type="text" name="abbreviation[{$formLocale|escape}]" id="abbreviation" value="{$abbreviation[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="printIssn" key="manager.setup.printIssn"}</td>
		<td class="value"><input type="text" name="printIssn" id="printIssn" value="{$printIssn|escape}" size="8" maxlength="16" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="onlineIssn" key="manager.setup.onlineIssn"}</td>
		<td class="value">
			<input type="text" name="onlineIssn" id="onlineIssn" value="{$onlineIssn|escape}" size="8" maxlength="16" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.issnDescription"}</span>
		</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="mailingAddress" key="common.mailingAddress"}</td>
		<td class="value">
			<textarea name="mailingAddress" id="mailingAddress" rows="3" cols="40" class="textArea richContent">{$mailingAddress|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.mailingAddressDescription"}</span>
		</td>
	</tr>
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

<div id="principalContact">
<h3>1.2 {translate key="manager.setup.principalContact"}</h3>

<p>{translate key="manager.setup.principalContactDescription"}</p>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="contactName" key="user.name" required="true"}</td>
		<td class="value"><input type="text" name="contactName" id="contactName" value="{$contactName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="contactTitle" key="user.title"}</td>
		<td class="value"><input type="text" name="contactTitle[{$formLocale|escape}]" id="contactTitle" value="{$contactTitle[$formLocale]|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="contactAffiliation" key="user.affiliation"}</td>
		<td class="value"><textarea name="contactAffiliation[{$formLocale|escape}]" id="contactAffiliation" rows="5" cols="40" class="textArea">{$contactAffiliation[$formLocale]|escape}</textarea></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="contactEmail" key="user.email" required="true"}</td>
		<td class="value"><input type="text" name="contactEmail" id="contactEmail" value="{$contactEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="contactPhone" key="user.phone"}</td>
		<td class="value"><input type="text" name="contactPhone" id="contactPhone" value="{$contactPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="contactFax" key="user.fax"}</td>
		<td class="value"><input type="text" name="contactFax" id="contactFax" value="{$contactFax|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="contactMailingAddress" key="common.mailingAddress"}</td>
		<td class="value"><textarea name="contactMailingAddress[{$formLocale|escape}]" id="contactMailingAddress" rows="3" cols="40" class="textArea richContent">{$contactMailingAddress[$formLocale]|escape}</textarea></td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="technicalSupportContact">
<h3>1.3 {translate key="manager.setup.technicalSupportContact"}</h3>

<p>{translate key="manager.setup.technicalSupportContactDescription"}</p>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="supportName" key="user.name" required="true"}</td>
		<td class="value"><input type="text" name="supportName" id="supportName" value="{$supportName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="supportEmail" key="user.email" required="true"}</td>
		<td class="value"><input type="text" name="supportEmail" id="supportEmail" value="{$supportEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="supportPhone" key="user.phone"}</td>
		<td class="value"><input type="text" name="supportPhone" id="supportPhone" value="{$supportPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="setupEmails">
<h3>1.4 {translate key="manager.setup.emails"}</h3>
<table class="data">
	<tr><td colspan="2">{translate key="manager.setup.emailHeaderDescription"}<br />&nbsp;</td></tr>
	<tr>
		<td class="label">{fieldLabel name="emailHeader" key="manager.setup.emailHeader"}</td>
		<td class="value">
			<textarea name="emailHeader" id="emailHeader" rows="3" cols="60" class="textArea">{$emailHeader|escape}</textarea>
		</td>
	</tr>
	<tr><td colspan="2">{translate key="manager.setup.emailSignatureDescription"}<br />&nbsp;</td></tr>
	<tr>
		<td class="label">{fieldLabel name="emailSignature" key="manager.setup.emailSignature"}</td>
		<td class="value">
			<textarea name="emailSignature" id="emailSignature" rows="3" cols="60" class="textArea">{$emailSignature|escape}</textarea>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr><td colspan="2">{translate key="manager.setup.emailBounceAddressDescription"}<br />&nbsp;</td></tr>
	<tr>
		<td class="label">{fieldLabel name="envelopeSender" key="manager.setup.emailBounceAddress"}</td>
		<td class="value">
			<input type="text" name="envelopeSender" id="envelopeSender" size="40" maxlength="255" class="textField" {if !$envelopeSenderEnabled}disabled="disabled" value=""{else}value="{$envelopeSender|escape}"{/if} />
			{if !$envelopeSenderEnabled}
			<br />
			<span class="instruct">{translate key="manager.setup.emailBounceAddressDisabled"}</span>
			{/if}
		</td>
	</tr>
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

<div id="searchEngineIndexing">
<h3>1.8 {translate key="manager.setup.searchEngineIndexing"}</h3>

<p>{translate key="manager.setup.searchEngineIndexingDescription"}</p>

<table class="data">
	<tr>
		<td class="label">{fieldLabel name="searchDescription" key="common.description"}</td>
		<td class="value"><input type="text" name="searchDescription[{$formLocale|escape}]" id="searchDescription" value="{$searchDescription[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="searchKeywords" key="common.keywords"}</td>
		<td class="value"><input type="text" name="searchKeywords[{$formLocale|escape}]" id="searchKeywords" value="{$searchKeywords[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="customHeaders" key="manager.setup.customTags"}</td>
		<td class="value">
			<textarea name="customHeaders[{$formLocale|escape}]" id="customHeaders" rows="3" cols="40" class="textArea">{$customHeaders[$formLocale]|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.customTagsDescription"}</span>
		</td>
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
