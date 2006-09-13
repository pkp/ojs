{**
 * studentThesisForm.tpl
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for student thesis abstract submission.
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.generic.layoutmanager.displayName"}
{include file="common/header.tpl"}
{formtool_init src="$pluginFileUrl/javascript/formtool.js"}

{translate key="plugins.generic.layoutmanager.form.onesidebar.description"}

<br />
<br />
<hr />
<table border="0">
	<tr>
		<td>
			<a href="{plugin_url path="changeLayout" escape=false}"><img src="{$pluginFileUrl}/images/threecolumnlayout.png" /></a>
		</td>
		<td>
			{translate key="plugins.generic.layoutmanager.form.twosidebars"}
		</td>
	</tr>
</table>

<hr />
<form method="post" action="{plugin_url path="settings"}">
{include file="common/formErrors.tpl"}

<table border="0" align="center">
	<tr>
		<td align="center">{translate key="plugins.generic.layoutmanager.form.unselected"}</td>
		<td/>
		<td align="center">
			{formtool_moveup button_text="&uarr;" save="blockSelectRight_save" name="blockSelectRight[]" class="button defaultButton" style="width: 150px;"}
		</td>
	</tr>
	<tr align="center">
		<td valign="top">
			<select name="blockUnselected[]" multiple size="10" class="selectMenu" style="width: 150px; height:200px; " >
			{html_options values=$blockDisabled output=$blockDisabled}
			</select>
			<input type="hidden" name="blockUnselected_save">
		</td>
		<td align="left">
			{formtool_move button_text="&larr;" from="blockSelectRight[]" to="blockUnselected[]" save_from="blockSelectRight_save" save_to="blockUnselected_save" class="button defaultButton" style="width: 40px;"}
			<br/>
			{formtool_move button_text="&rarr;" from="blockUnselected[]" to="blockSelectRight[]" save_from="blockUnselected_save" save_to="blockSelectRight_save" class="button defaultButton" style="width: 40px;"}
		</td>
		<td valign="top">
			<select name="blockSelectRight[]" multiple size="10" class="selectMenu" style="width: 150px; height:200px" >
			{html_options values=$rightBlockEnabled output=$rightBlockEnabled }
			</select><br />
			<input type="hidden" name="blockSelectRight_save" value="{$blockSelectRight_save}" >
		</td>
	</tr>
	<tr>
		<td colspan="2" />
		<td align="center">
			{formtool_movedown button_text="&darr;" save="blockSelectRight_save" name="blockSelectRight[]" class="button defaultButton" style="width: 150px;"}
		</td>
	</tr>
	<tr align="center">
		<td colspan="3">
			<br/>
			<input type="hidden" name="preview" value="0">
			<input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
			<input type="button" value="{translate key="plugins.generic.layoutmanager.form.preview"}" class="button defaultButton" onclick="this.form.preview.value=1;this.form.submit()" />
			<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="plugins" escape=false}'" />
		</td>
	</tr>
</table>

</form>

{include file="common/footer.tpl"}
