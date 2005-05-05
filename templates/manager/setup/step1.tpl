{**
 * step1.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of journal setup.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.setup.gettingDownTheDetails}
{include file="manager/setup/setupHeader.tpl"}

<form method="post" action="{$pageUrl}/manager/saveSetup/1">
{include file="common/formErrors.tpl"}

<h3>1.1 {translate key="manager.setup.generalInformation"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="journalTitle" required="true" key="manager.setup.journalTitle"}</td>
		<td width="80%" class="value"><input type="text" name="journalTitle" id="journalTitle" value="{$journalTitle|escape}" size="40" maxlength="120" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="journalInitials" required="true" key="manager.setup.journalInitials"}</td>
		<td width="80%" class="value"><input type="text" name="journalInitials" id="journalInitials" value="{$journalInitials|escape}" size="8" maxlength="16" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="issn" key="manager.setup.issn"}</td>
		<td width="80%" class="value">
			<input type="text" name="issn" id="issn" value="{$issn|escape}" size="8" maxlength="16" class="textField" />
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
</table>


<div class="separator"></div>


<h3>1.2 {translate key="manager.setup.principalContact"}</h3>

<p>{translate key="manager.setup.principalContactDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactName" key="user.name"}</td>
		<td width="80%" class="value"><input type="text" name="contactName" id="contactName" value="{$contactName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactTitle" key="user.title"}</td>
		<td width="80%" class="value"><input type="text" name="contactTitle" id="contactTitle" value="{$contactTitle|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>	
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactAffiliation" key="user.affiliation"}</td>
		<td width="80%" class="value"><input type="text" name="contactAffiliation" id="contactAffiliation" value="{$contactAffiliation|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contactEmail" key="user.email"}</td>
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
		<td width="80%" class="value"><textarea name="contactMailingAddress" id="contactMailingAddress" rows="3" cols="40" class="textArea">{$contactMailingAddress|escape}</textarea></td>
	</tr>
</table>


<div class="separator"></div>


<h3>1.3 {translate key="manager.setup.technicalSupportContact"}</h3>

<p>{translate key="manager.setup.technicalSupportContactDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportName" key="user.name"}</td>
		<td width="80%" class="value"><input type="text" name="supportName" id="supportName" value="{$supportName|escape}" size="30" maxlength="60" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportEmail" key="user.email"}</td>
		<td width="80%" class="value"><input type="text" name="supportEmail" id="supportEmail" value="{$supportEmail|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="supportPhone" key="user.phone"}</td>
		<td width="80%" class="value"><input type="text" name="supportPhone" id="supportPhone" value="{$supportPhone|escape}" size="15" maxlength="24" class="textField" /></td>
	</tr>
</table>


<div class="separator"></div>

<h3>1.4 {translate key="manager.setup.publisher"}</h3>

<p>{translate key="manager.setup.publisherDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisher[note]" key="manager.setup.note"}</td>
		<td width="80%" class="value"><textarea name="publisher[note]" id="publisher[note]" rows="5" cols="40" class="textArea">{$publisher.note|escape}</textarea></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisher[institution]" key="manager.setup.institution"}</td>
		<td width="80%" class="value"><input type="text" name="publisher[institution]" id="publisher[institution]" value="{$publisher.institution|escape}" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="publisher[url]" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="publisher[url]" id="publisher[url]" value="{$publisher.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
</table>

<div class="separator"></div>

<h3>1.5 {translate key="manager.setup.sponsors"}</h3>

<p>{translate key="manager.setup.sponsorsDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsorNote" key="manager.setup.note"}</td>
		<td width="80%" class="value"><textarea name="sponsorNote" id="sponsorNote" rows="5" cols="40" class="textArea">{$sponsorNote|escape}</textarea></td>
	</tr>
{foreach name=sponsors from=$sponsors key=sponsorId item=sponsor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors[$sponsorId][institution]" key="manager.setup.institution"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[{$sponsorId}][institution]" id="sponsors[{$sponsorId}][institution]" value="{$sponsor.institution|escape}" size="40" maxlength="90" class="textField" />{if $smarty.foreach.sponsors.total > 1} <input type="submit" name="delSponsor[{$sponsorId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors[$sponsorId][url]" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[{$sponsorId}][url]" id="sponsors[{$sponsorId}][url]" value="{$sponsor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.sponsors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors[0][institution]" key="manager.setup.institution"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][institution]" id="sponsors[0][institution]" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="sponsors[0][url]" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="sponsors[0][url]" id="sponsors[0][url]" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addSponsor" value="{translate key="manager.setup.addSponsor"}" class="button" /></p>


<div class="separator"></div>


<h3>1.6 {translate key="manager.setup.contributors"}</h3>

<p>{translate key="manager.setup.contributorsDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributorNote" key="manager.setup.note"}</td>
		<td width="80%" class="value"><textarea name="contributorNote" id="contributorNote" rows="5" cols="40" class="textArea">{$contributorNote|escape}</textarea></td>
	</tr>
{foreach name=contributors from=$contributors key=contributorId item=contributor}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors[$contributorId][name]" key="manager.setup.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId}][name]" id="contributors[{$contributorId}][name]" value="{$contributor.name|escape}" size="40" maxlength="90" class="textField" />{if $smarty.foreach.contributors.total > 1} <input type="submit" name="delContributor[{$contributorId}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors[$contributorId][url]" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[{$contributorId}][url]" id="contributors[{$contributorId}][url]" value="{$contributor.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.contributors.last}
	<tr valign="top">
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors[0][name]" key="manager.setup.contributor"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][name]" id="contributors[0][name]" size="40" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="contributors[0][url]" key="common.url"}</td>
		<td width="80%" class="value"><input type="text" name="contributors[0][url]" id="contributors[0][url]" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addContributor" value="{translate key="manager.setup.addContributor"}" class="button" /></p>


<div class="separator"></div>


<h3>1.7 {translate key="manager.setup.searchEngineIndexing"}</h3>

<p>{translate key="manager.setup.searchEngineIndexingDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchDescription" key="common.description"}</td>
		<td width="80%" class="value"><input type="text" name="searchDescription" id="searchDescription" value="{$searchDescription|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="searchKeywords" key="common.keywords"}</td>
		<td width="80%" class="value"><input type="text" name="searchKeywords" id="searchKeywords" value="{$searchKeywords|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="customHeaders" key="manager.setup.customTags"}</td>
		<td width="80%" class="value">
			<textarea name="customHeaders" id="customHeaders" rows="3" cols="40" class="textArea">{$customHeaders|escape}</textarea>
			<br />
			<span class="instruct">{translate key="manager.setup.customTagsDescription"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>


<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager/setup'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
