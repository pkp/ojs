{**
 * plugins/pubIds/urn/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * URN plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.pubIds.urn.manager.settings.urnSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="urnSettings">
<div id="description">{translate key="plugins.pubIds.urn.manager.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="journalContent" required="true" key="plugins.pubIds.urn.manager.settings.journalContent"}</td>
		<td width="80%" class="value">
			{translate key="plugins.pubIds.urn.manager.settings.URNsForJournalContent"}<br />
			<input type="checkbox" name="enableIssueURN" id="enableIssueURN" value="1"{if $enableIssueURN} checked="checked"{/if} />
			{fieldLabel name="enableIssueURN" key="plugins.pubIds.urn.manager.settings.enableIssueURN"}<br />
			<input type="checkbox" name="enableArticleURN" id="enableArticleURN" value="1"{if $enableArticleURN} checked="checked"{/if} />
			{fieldLabel name="enableArticleURN" key="plugins.pubIds.urn.manager.settings.enableArticleURN"}<br />
			<input type="checkbox" name="enableGalleyURN" id="enableGalleyURN" value="1"{if $enableGalleyURN} checked="checked"{/if} />
			{fieldLabel name="enableGalleyURN" key="plugins.pubIds.urn.manager.settings.enableGalleyURN"}<br />
			<input type="checkbox" name="enableSuppFileURN" id="enableSuppFileURN" value="1"{if $enableSuppFileURN} checked="checked"{/if} />
			{fieldLabel name="enableSuppFileURN" key="plugins.pubIds.urn.manager.settings.enableSuppFileURN"}<br />
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="urnPrefix" required="true" key="plugins.pubIds.urn.manager.settings.urnPrefix"}</td>
		<td width="80%" class="value"><input type="text" name="urnPrefix" value="{$urnPrefix|escape}" size="20" maxlength="20" id="urnPrefix" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnPrefix.description"}</span>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="urnSuffix" key="plugins.pubIds.urn.manager.settings.urnSuffix"}</td>
		<td width="80%" class="value">
			<table width="100%" class="data">
				<tr>
					<td width="5%" class="label" align="right" valign="top">
						<input type="radio" name="urnSuffix" id="urnSuffixPattern" value="pattern" {if $urnSuffix eq "pattern"}checked{/if} />
					</td>
					<td width="95%" class="value">
						{fieldLabel name="urnSuffixPattern" key="plugins.pubIds.urn.manager.settings.urnSuffix.pattern"}
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="text" name="urnIssueSuffixPattern" value="{$urnIssueSuffixPattern|escape}" size="15" maxlength="50" id="urnIssueSuffixPattern" class="textField" />
						<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.issues"}</span>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="text" name="urnArticleSuffixPattern" value="{$urnArticleSuffixPattern|escape}" size="15" maxlength="50" id="urnArticleSuffixPattern" class="textField" />
						<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.articles"}</span>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="text" name="urnGalleySuffixPattern" value="{$urnGalleySuffixPattern|escape}" size="15" maxlength="50" id="urnGalleySuffixPattern" class="textField" />
						<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.galleys"}</span>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="text" name="urnSuppFileSuffixPattern" value="{$urnSuppFileSuffixPattern|escape}" size="15" maxlength="50" id="urnSuppFileSuffixPattern" class="textField" />
						<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.pattern.suppFiles"}</span>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.patternExample"}</span>
					</td>
				</tr>
				<tr>
					<td width="5%" class="label" align="right" valign="top">
						<input type="radio" name="urnSuffix" id="urnSuffixDefault" value="default" {if ($urnSuffix neq "pattern" && $urnSuffix neq "customIdentifier")}checked{/if} />
					</td>
					<td width="95%" class="value">
						{fieldLabel name="urnSuffixDefault" key="plugins.pubIds.urn.manager.settings.urnSuffix.default"}
						<br />
						<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.default.description"}</span>
					</td>
				</tr>
				<tr>
					<td width="5%" class="label" align="right" valign="top">
						<input type="radio" name="urnSuffix" id="urnSuffixPublisherId" value="publisherId" {if $urnSuffix eq "publisherId"}checked{/if} />
					</td>
					<td width="95%" class="value">
						{fieldLabel name="urnSuffixpublisherId" key="plugins.pubIds.urn.manager.settings.urnSuffix.publisherId"}
					</td>
				</tr>
				<tr>
					<td width="5%" class="label" align="right" valign="top">
						<input type="radio" name="urnSuffix" id="urnSuffixCustomIdentifier" value="customIdentifier" {if $urnSuffix eq "customIdentifier"}checked{/if} />
					</td>
					<td width="95%" class="value">
						{fieldLabel name="urnSuffixCustomIdentifier" key="plugins.pubIds.urn.manager.settings.urnSuffix.customIdentifier"}
					</td>
				</tr>
			</table>
			<br />
			<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnSuffix.description"}</span>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="checkNo" key="plugins.pubIds.urn.manager.settings.checkNo"}</td>
		<td class="value">
			<input type="checkbox" name="checkNo" id="checkNo" value="1"{if $checkNo} checked="checked"{/if} />
			<label for="checkNo">{translate key="plugins.pubIds.urn.manager.settings.checkNo.label"}</label><br />
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="namespace" required="true" key="plugins.pubIds.urn.manager.settings.namespace"}</td>
		<td class="value">
			<select name="namespace" id="namespace" class="selectMenu">
				<option value="">{translate key="plugins.pubIds.urn.manager.settings.namespace.choose"}</option>
				{html_options options=$namespaces selected=$namespace}
			</select>
			<br />
			<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.namespace.description"}</span>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="urnResolver" required="true" key="plugins.pubIds.urn.manager.settings.urnResolver"}</td>
		<td width="80%" class="value"><input type="text" name="urnResolver" value="{$urnResolver|escape}" size="40" maxlength="255" id="urnResolver" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.urnResolver.description"}</span>
		</td>
	</tr>
	<tr><td colspan="2">&nbsp;</td></tr>
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="plugins.pubIds.urn.manager.settings.clearURNs.description"}</span>
			<br />
			<input type="submit" name="clearPubIds" value="{translate key="plugins.pubIds.urn.manager.settings.clearURNs"}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.pubIds.urn.manager.settings.clearURNs.confirm"}')" class="action"/>
		</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
