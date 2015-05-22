{**
 * @file plugins/importexport/crossref/templates/settings.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * DataCite plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.settings"}
{include file="common/header.tpl"}
{/strip}
<div id="crossrefSettings">
	{include file="common/formErrors.tpl"}
	<p>{translate key="plugins.importexport.crossref.registrationIntro"}</p>
	<h3>{translate key="plugins.importexport.crossref.requirements"}</h3>
	<br />

	{capture assign="settingsUrl"}{plugin_url path="settings"}{/capture}
	{url|assign:"publisherUrl" page="manager" op="setup" path="1" anchor='setupPublisher'}
	{url|assign:"doiUrl" page="manager" op="plugin" path="pubIds"}

	{if !empty($configurationErrors) || !$currentJournal->getSetting('publisherInstitution')|escape}
	<ul>
		{foreach from=$configurationErrors item=configurationError}
			{if $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_DOIPREFIX}
				<li>{translate key="plugins.importexport.crossref.error.DOIsNotAvailable" doiUrl=$doiUrl}</li>
			{elseif $configurationError == $smarty.const.DOI_EXPORT_CONFIGERROR_SETTINGS}
				<li>{translate key="plugins.importexport.crossref.error.pluginNotConfigured" settingsUrl=$settingsUrl}</li>
			{/if}
		{/foreach}
		{if !$currentJournal->getSetting('publisherInstitution')|escape}
			<li>{translate key="plugins.importexport.crossref.error.publisherNotConfigured" publisherUrl=$publisherUrl}</li>
		{/if}

	</ul>
	{else}
		{translate key="plugins.importexport.crossref.requirements.satisfied"}
	{/if}

	<h3>{translate key="plugins.importexport.common.settings"}</h3>
	<br />
	<form method="post" action="{plugin_url path="settings"}">
		<table width="100%" class="data">
			<tr valign="top">
				<td colspan="2">
					<span class="instruct">{translate key="plugins.importexport.crossref.settings.depositorIntro"}</span>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="depositorName" key="plugins.importexport.crossref.settings.form.depositorName" required="true"}</td>
				<td width="80%" class="value">
					<input type="text" name="depositorName" value="{$depositorName|escape|default:$currentJournal->getSetting('supportName')|escape}" size="30" maxlength="60" id="depositorName" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="depositorEmail" key="plugins.importexport.crossref.settings.form.depositorEmail" required="true"}</td>
				<td width="80%" class="value">
					<input type="text" name="depositorEmail" value="{$depositorEmail|escape|default:$currentJournal->getSetting('supportEmail')|escape}" size="30" maxlength="90" id="depositorEmail" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td colspan="2">
					<span class="instruct">{translate key="plugins.importexport.crossref.registrationIntro"}</span>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="username" key="plugins.importexport.crossref.settings.form.username"}</td>
				<td width="80%" class="value">
					<input type="text" name="username" value="{$username|escape}" size="20" maxlength="50" id="username" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="password" key="plugins.importexport.common.settings.form.password"}</td>
				<td width="80%" class="value">
					<input type="password" name="password" value="{$password|escape}" size="20" maxlength="50" id="password" class="textField" />
					<br />{translate key="plugins.importexport.common.settings.form.password.description"}
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="automaticRegistration" key="plugins.importexport.crossref.settings.form.automaticRegistration"}</td>
				<td width="80%" class="value">
					<input type="checkbox" name="automaticRegistration" id="automaticRegistration" value="1" {if $automaticRegistration} checked="checked"{/if} />&nbsp;{translate key="plugins.importexport.crossref.settings.form.automaticRegistration.description"}
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
		</table>

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

		<p>
			<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
			&nbsp;
			<input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
		</p>
	</form>

</div>
{include file="common/footer.tpl"}
