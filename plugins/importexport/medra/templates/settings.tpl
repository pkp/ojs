{**
 * @file plugins/importexport/medra/templates/settings.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * mEDRA plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.common.settings"}
{include file="common/header.tpl"}
{/strip}
<div id="medraSettings">
	{include file="common/formErrors.tpl"}
	<br />
	<br />

	<div id="description"><b>{translate key="plugins.importexport.medra.settings.form.description"}</b></div>

	<br />

	<form method="post" action="{plugin_url path="settings"}">
		<table width="100%" class="data">
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="registrantName" required="true" key="plugins.importexport.medra.settings.form.registrantName"}</td>
				<td width="80%" class="value">
					<input type="text" name="registrantName" value="{$registrantName|escape}" size="20" maxlength="50" id="registrantName" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" colspan="2" class="label">{fieldLabel name="fromFields" key="plugins.importexport.medra.settings.form.fromFields"}</td>
			</tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="fromCompany" required="true" key="plugins.importexport.medra.settings.form.fromCompany"}</td>
				<td width="80%" class="value">
					<input type="text" name="fromCompany" value="{$fromCompany|escape}" size="20" maxlength="30" id="fromCompany" class="textField" />
				</td>
			</tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="fromName" required="true" key="plugins.importexport.medra.settings.form.fromName"}</td>
				<td width="80%" class="value">
					<input type="text" name="fromName" value="{$fromName|escape}" size="20" maxlength="50" id="fromName" class="textField" />
				</td>
			</tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="fromEmail" required="true" key="plugins.importexport.medra.settings.form.fromEmail"}</td>
				<td width="80%" class="value">
					<input type="text" name="fromEmail" value="{$fromEmail|escape}" size="20" maxlength="50" id="fromEmail" class="textField" />
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
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">
					{* We cannot use the form vocab here because it will escape the label
					   and we need a link in there. *}
					<label for="exportIssuesAs">{translate key="plugins.importexport.medra.settings.form.exportIssuesAs"} *</label>
				</td>
				<td width="80%" class="value">
					<select name="exportIssuesAs" id="exportIssuesAs" class="selectMenu">
						{html_options options=$exportIssueOptions selected=$exportIssuesAs}
					</select>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td colspan="2">
					<span class="instruct">{translate key="plugins.importexport.medra.intro"}</span>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="username" key="plugins.importexport.medra.settings.form.username"}</td>
				<td width="80%" class="value">
					<input type="text" name="username" value="{$username|escape}" size="20" maxlength="50" id="username" class="textField" />
				</td>
			</tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="password" key="plugins.importexport.common.settings.form.password"}</td>
				<td width="80%" class="value">
					<input type="password" name="password" value="{$password|escape}" size="20" maxlength="50" id="password" class="textField" />
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
