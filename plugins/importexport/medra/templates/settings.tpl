{**
 * @file plugins/importexport/medra/templates/settings.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * mEDRA plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.settings"}
{include file="common/header.tpl"}
{/strip}
<div id="medraSettings">
	<br />
	<br />

	<div id="description">{translate key="plugins.importexport.medra.settings.form.description"}</div>

	<br />

	<form method="post" action="{plugin_url path="settings"}">
		{include file="common/formErrors.tpl"}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="exportIssuesAs" required="true" key="plugins.importexport.medra.settings.form.exportIssuesAs"}</td>
				<td width="80%" class="value">
					<select name="exportIssuesAs" id="exportIssuesAs" class="selectMenu">
						{html_options options=$exportIssueOptions selected=$exportIssuesAs}
					</select>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="publicationCountry" required="true" key="plugins.importexport.medra.settings.form.publicationCountry"}</td>
				<td width="80%" class="value">
					<select name="publicationCountry" id="publicationCountry" class="selectMenu">
						{html_options options=$countries selected=$publicationCountry}
					</select>
				</td>
			</tr>
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
