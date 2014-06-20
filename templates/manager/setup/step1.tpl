{**
 * templates/manager/setup/step1.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of journal setup.
 *
 *}
{assign var="pageTitle" value="manager.setup.gettingDownTheDetails"}
{include file="manager/setup/setupHeader.tpl"}

<form id="setupForm" method="post" action="{url op="saveSetup" path="1"}">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<div id="locales">
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="1" escape=false}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
</div>
{/if}
<div id="generalInformation">
<h3>1.1 {translate key="manager.setup.generalInformation"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="title" required="true" key="manager.setup.journalTitle"}</td>
		<td width="80%" class="value"><input type="text" name="title[{$formLocale|escape}]" id="title" value="{$title[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="initials" required="true" key="manager.setup.journalInitials"}</td>
		<td width="80%" class="value"><input type="text" name="initials[{$formLocale|escape}]" id="initials" value="{$initials[$formLocale]|escape}" size="8" maxlength="16" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="abbreviation" key="manager.setup.journalAbbreviation"}</td>
		<td width="80%" class="value"><input type="text" name="abbreviation[{$formLocale|escape}]" id="abbreviation" value="{$abbreviation[$formLocale]|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="printIssn" key="manager.setup.printIssn"}</td>
		<td width="80%" class="value"><input type="text" name="printIssn" id="printIssn" value="{$printIssn|escape}" size="8" maxlength="16" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="onlineIssn" key="manager.setup.onlineIssn"}</td>
		<td width="80%" class="value">
			<input type="text" name="onlineIssn" id="onlineIssn" value="{$onlineIssn|escape}" size="8" maxlength="16" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.issnDescription"}</span>
		</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="mailingAddress" key="common.mailingAddress"}</td>
		<td width="80%" class="value">
			<textarea name="mailingAddress" id="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.mailingAddressDescription"}</span>
		</td>
	</tr>
	{if $categoriesEnabled}
		<tr valign="top">
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

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactName" key="user.name" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="contactName" id="contactName" value="{$contactName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactTitle" key="user.title"}</td>
		<td width="80%" class="value"><input type="text" name="contactTitle[{$formLocale|escape}]" id="contactTitle" value="{$contactTitle[$formLocale]|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactAffiliation" key="user.affiliation"}</td>
		<td width="80%" class="value"><textarea name="contactAffiliation[{$formLocale|escape}]" id="contactAffiliation" rows="5" cols="40" class="textArea">{$contactAffiliation[$formLocale]|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactEmail" key="user.email" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="contactEmail" id="contactEmail" value="{$contactEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="contactPhone" id="contactPhone" value="{$contactPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactFax" key="user.fax"}</td>
		<td width="80%" class="value"><input type="text" name="contactFax" id="contactFax" value="{$contactFax|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactMailingAddress" key="common.mailingAddress"}</td>
		<td width="80%" class="value"><textarea name="contactMailingAddress[{$formLocale|escape}]" id="contactMailingAddress" rows="3" cols="40" class="textArea">{$contactMailingAddress[$formLocale]|escape}</textarea></td>
	</tr>
</table>
</div>

<div class="separator"></div>

<div id="technicalSupportContact">
<h3>1.3 {translate key="manager.setup.technicalSupportContact"}</h3>

<p>{translate key="manager.setup.technicalSupportContactDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportName" key="user.name" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="supportName" id="supportName" value="{$supportName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportEmail" key="user.email" required="true"}</td>
		<td width="80%" class="value"><input type="text" name="supportEmail" id="supportEmail" value="{$supportEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="supportPhone" id="supportPhone" value="{$supportPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="setupEmails">
<h3>1.4 {translate key="manager.setup.emails"}</h3>
<table width="100%" class="data">
	<tr valign="top"><td colspan="2">{translate key="manager.setup.emailSignatureDescription"}<br />&nbsp;</td></tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="emailSignature" key="manager.setup.emailSignature"}</td>
		<td class="value">
			<textarea name="emailSignature" id="emailSignature" rows="3" cols="60" class="textArea">{$emailSignature|escape}</textarea>
		</td>
	</tr>
	<tr valign="top"><td colspan="2">&nbsp;</td></tr>
	<tr valign="top"><td colspan="2">{translate key="manager.setup.emailBounceAddressDescription"}<br />&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="envelopeSender" key="manager.setup.emailBounceAddress"}</td>
		<td width="80%" class="value">
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

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisherNote" key="manager.setup.note"}</td>
		<td width="80%" class="value">
			<textarea name="publisherNote[{$formLocale|escape}]" id="publisherNote" rows="5" cols="40" class="textArea">{$publisherNote[$formLocale]|escape}</textarea>
			<br/>
			<span class="instruct">{translate key="manager.setup.publisherNoteDescription"}</span>
			</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisherInstitution" key="manager.setup.institution"}</td>
		<td width="80%" class="value"><input type="text" name="publisherInstitution" id="publisherInstitution" value="{$publisherInstitution|escape}" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisherUrl" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="publisherUrl" id="publisherUrl" value="{$publisherUrl|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
</table>
</div>
<div class="separator"></div>
<div id="sponsors">
<h3>1.6 {translate key="manager.setup.sponsors"}</h3>

<p>{translate key="manager.setup.sponsorsDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsorNote" key="manager.setup.note"}</td>
		<td width="80%" class="value">
			<textarea name="sponsorNote[{$formLocale|escape}]" id="sponsorNote" rows="5" cols="40" class="textArea">{$sponsorNote[$formLocale]|escape}</textarea>
			<br/>
			<span class="instruct">{translate key="manager.setup.sponsorNoteDescription"}</span>
		</td>
	</tr>
{foreach name=sponsors from=$sponsors key=sponsorId item=sponsor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-institution" key="manager.setup.institution"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[{$sponsorId|escape}][institution]" id="sponsors-{$sponsorId|escape}-institution" value="{$sponsor.institution|escape}" size="40" maxlength="90" class="textField" />{if $smarty.foreach.sponsors.total > 1} <input type="submit" name="delSponsor[{$sponsorId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-$sponsorId-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[{$sponsorId|escape}][url]" id="sponsors-{$sponsorId|escape}-url" value="{$sponsor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.sponsors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-institution" key="manager.setup.institution"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][institution]" id="sponsors-0-institution" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors-0-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][url]" id="sponsors-0-url" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addSponsor" value="{translate key="manager.setup.addSponsor"}" class="button" /></p>
</div>

<div class="separator"></div>

<div id="contributors">
<h3>1.7 {translate key="manager.setup.contributors"}</h3>

<p>{translate key="manager.setup.contributorsDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributorNote" key="manager.setup.note"}</td>
		<td width="80%" class="value">
			<textarea name="contributorNote[{$formLocale|escape}]" id="contributorNote" rows="5" cols="40" class="textArea">{$contributorNote[$formLocale]|escape}</textarea>
			<br/>
			<span class="instruct">{translate key="manager.setup.contributorNoteDescription"}</span>
		</td>
	</tr>
{foreach name=contributors from=$contributors key=contributorId item=contributor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-$contributorId-name" key="manager.setup.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId|escape}][name]" id="contributors-{$contributorId|escape}-name" value="{$contributor.name|escape}" size="40" maxlength="90" class="textField" />{if $smarty.foreach.contributors.total > 1} <input type="submit" name="delContributor[{$contributorId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-$contributorId-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId|escape}][url]" id="contributors-{$contributorId|escape}-url" value="{$contributor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.contributors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-0-name" key="manager.setup.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][name]" id="contributors-0-name" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors-0-url" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][url]" id="contributors-0-url" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addContributor" value="{translate key="manager.setup.addContributor"}" class="button" /></p>
</div>

<div class="separator"></div>

<div id="searchEngineIndexing">
<h3>1.8 {translate key="manager.setup.searchEngineIndexing"}</h3>

<p>{translate key="manager.setup.searchEngineIndexingDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchDescription" key="common.description"}</td>
		<td width="80%" class="value"><input type="text" name="searchDescription[{$formLocale|escape}]" id="searchDescription" value="{$searchDescription[$formLocale]|escape}" size="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchKeywords" key="common.keywords"}</td>
		<td width="80%" class="value"><input type="text" name="searchKeywords[{$formLocale|escape}]" id="searchKeywords" value="{$searchKeywords[$formLocale]|escape}" size="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customHeaders" key="manager.setup.customTags"}</td>
		<td width="80%" class="value">
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

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="history" key="manager.setup.history"}</td>
		<td width="80%" class="value">
			<textarea name="history[{$formLocale|escape}]" id="history" rows="5" cols="40" class="textArea">{$history[$formLocale]|escape}</textarea>
		</td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}

