{**
 * thesisForm.tpl
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Thesis abstract form under plugin management.
 *
 * $Id$
 *}

{assign var="pageCrumbTitle" value="$thesisTitle"}
{if $thesisId}
	{assign var="pageTitle" value="plugins.generic.thesis.manager.edit"}
{else}
	{assign var="pageTitle" value="plugins.generic.thesis.manager.create"}
{/if}
{include file="common/header.tpl"}

<br/>

<form method="post" action="{plugin_url path="update"}">
{if $thesisId}
<input type="hidden" name="thesisId" value="{$thesisId}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="status" required="true" key="plugins.generic.thesis.manager.form.status"}</td>
	<td width="80%" class="value"><select name="status" id="status" class="selectMenu" />{html_options options=$validStatus selected=$status}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="degree" required="true" key="plugins.generic.thesis.manager.form.degree"}</td>
	<td class="value"><select name="degree" id="degree" class="selectMenu" />{html_options options=$validDegrees selected=$degree}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="department" required="true" key="plugins.generic.thesis.manager.form.department"}</td>
	<td class="value"><input type="text" name="department" value="{$department|escape}" size="40" id="department" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="university" required="true" key="plugins.generic.thesis.manager.form.university"}</td>
	<td class="value"><input type="text" name="university" value="{$university|escape}" size="40" id="university" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateApproved" required="true" key="plugins.generic.thesis.manager.form.dateApproved"}</td>
	<td class="value">{html_select_date prefix="dateApproved" all_extra="class=\"selectMenu\"" start_year="$yearOffsetPast" time="$dateApproved"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="title" required="true" key="plugins.generic.thesis.manager.form.title"}</td>
	<td class="value"><input type="text" name="title" value="{$title|escape}" size="40" id="title" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="url" key="plugins.generic.thesis.manager.form.url"}</td>
	<td class="value"><input type="text" name="url" value="{$url|escape}" size="40" id="url" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.urlInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentFirstName" required="true" key="plugins.generic.thesis.manager.form.studentFirstName"}</td>
	<td class="value"><input type="text" name="studentFirstName" value="{$studentFirstName|escape}" size="40" id="studentFirstName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentMiddleName" key="plugins.generic.thesis.manager.form.studentMiddleName"}</td>
	<td class="value"><input type="text" name="studentMiddleName" value="{$studentMiddleName|escape}" size="40" id="studentMiddleName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentLastName" required="true" key="plugins.generic.thesis.manager.form.studentLastName"}</td>
	<td class="value"><input type="text" name="studentLastName" value="{$studentLastName|escape}" size="40" id="studentLastName" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentEmail" required="true" key="plugins.generic.thesis.manager.form.studentEmail"}</td>
	<td class="value"><input type="text" name="studentEmail" value="{$studentEmail|escape}" size="40" id="studentEmail" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorFirstName" required="true" key="plugins.generic.thesis.manager.form.supervisorFirstName"}</td>
	<td class="value"><input type="text" name="supervisorFirstName" value="{$supervisorFirstName|escape}" size="40" id="supervisorFirstName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorMiddleName" key="plugins.generic.thesis.manager.form.supervisorMiddleName"}</td>
	<td class="value"><input type="text" name="supervisorMiddleName" value="{$supervisorMiddleName|escape}" size="40" id="supervisorMiddleName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorLastName" required="true" key="plugins.generic.thesis.manager.form.supervisorLastName"}</td>
	<td class="value"><input type="text" name="supervisorLastName" value="{$supervisorLastName|escape}" size="40" id="supervisorLastName" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorEmail" required="true" key="plugins.generic.thesis.manager.form.supervisorEmail"}</td>
	<td class="value"><input type="text" name="supervisorEmail" value="{$supervisorEmail|escape}" size="40" id="supervisorEmail" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="abstract" required="true" key="plugins.generic.thesis.manager.form.abstract"}</td>
	<td class="value"><textarea name="abstract" id="abstract" cols="40" rows="6" class="textArea" />{$abstract|escape}</textarea>
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.abstractInstructions"}</span>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $thesisId}<input type="submit" name="createAnother" value="{translate key="plugins.generic.thesis.manager.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{plugin_url escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
