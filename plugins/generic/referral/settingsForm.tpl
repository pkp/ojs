{**
 * plugins/generic/referral/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Referral plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.referral.settings"}
{include file="common/header.tpl"}
{/strip}
<div id="referralSettings">
<div id="description">{translate key="plugins.generic.referral.settings.description"}</div>

<div class="separator"></div>

<br />

<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="exclusions" key="plugins.generic.referral.excusions"}</td>
		<td width="80%" class="value">
			<textarea id="exclusions" name="exclusions" cols="40" rows="5">{$exclusions|escape}</textarea>
			<br/>
			<span class="instruct">{translate key="plugins.generic.referral.exclusions.description"}</span>
		</td>
	</tr>
</table>

<br/>

<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
