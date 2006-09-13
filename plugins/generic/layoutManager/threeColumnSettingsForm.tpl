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

{translate key="plugins.generic.layoutmanager.form.twosidebars.description"}

<br />
<br />
<hr />
<table border="0">
	<tr>
		<td>
			<a href="{plugin_url path="changeLayout" escape=false}"><img src="{$pluginFileUrl}/images/twocolumnlayout.png" /></a>
		</td>
		<td>
			{translate key="plugins.generic.layoutmanager.form.onesidebar"}
		</td>
	</tr>
</table>
<hr />
<form method="post" action="{plugin_url path="settings"}">

{include file="common/formErrors.tpl"}
<table border="0" align="center">
	<tr align="center">
		<td rowspan="2">
			{translate key="plugins.generic.layoutmanager.form.leftsidebar"}
			{formtool_moveup button_text="&uarr;" save="blockSelectLeft_save" name="blockSelectLeft[]" class="button defaultButton" style="width: 130px;"}
			<select name="blockSelectLeft[]" multiple size="10" class="selectMenu" style="width: 130px; height:200px" >
				{html_options values=$leftBlockEnabled output=$leftBlockEnabled }
			</select><br/>
			<input type="hidden" name="blockSelectLeft_save" value="{$blockSelectLeft_save}" >
	   		{formtool_movedown button_text="&darr;" save="blockSelectLeft_save" name="blockSelectLeft[]" class="button defaultButton" style="width: 130px;"}
		</td>
		<td>
			{formtool_move button_text="&larr;" from="blockUnselected[]" to="blockSelectLeft[]" save_from="blockUnselected_save" save_to="blockSelectLeft_save" class="button defaultButton" style="width: 30px;"}
			{formtool_move button_text="&rarr;" from="blockSelectLeft[]" to="blockUnselected[]" save_from="blockSelectLeft_save" save_to="blockUnselected_save" class="button defaultButton" style="width: 30px;"}
		</td>
		<td valign="top">
			{translate key="plugins.generic.layoutmanager.form.unselected"}
			<select name="blockUnselected[]" multiple size="10" class="selectMenu" style="width: 120px; height:180px;" >
				{html_options values=$blockDisabled output=$blockDisabled}
			</select>
			<input type="hidden" name="blockUnselected_save">	
		</td> 
		<td>
			{formtool_move button_text="&larr;" from="blockSelectRight[]" to="blockUnselected[]" save_from="blockSelectRight_save" save_to="blockUnselected_save" class="button defaultButton" style="width: 30px;"}
			{formtool_move button_text="&rarr;" from="blockUnselected[]" to="blockSelectRight[]" save_from="blockUnselected_save" save_to="blockSelectRight_save" class="button defaultButton" style="width: 30px;"}
		</td>
	   	<td rowspan="2">
			{translate key="plugins.generic.layoutmanager.form.rightsidebar"}
			{formtool_moveup button_text="&uarr;" save="blockSelectRight_save" name="blockSelectRight[]" class="button defaultButton" style="width: 130px;"}
			<select name="blockSelectRight[]" multiple size="10" class="selectMenu" style="width: 130px; height:200px" >
				{html_options values=$rightBlockEnabled output=$rightBlockEnabled }
			</select><br/>
			<input type="hidden" name="blockSelectRight_save" value="{$blockSelectRight_save}" >
	   		{formtool_movedown button_text="&darr;" save="blockSelectRight_save" name="blockSelectRight[]" class="button defaultButton" style="width: 130px;"}
		</td>
	</tr>
	<tr align="center">
		<td colspan="3" valign="top" height="60px">
			{formtool_move button_text="&larr;" from="blockSelectRight[]" to="blockSelectLeft[]" save_from="blockSelectRight_save" save_to="blockSelectLeft_save" class="button defaultButton" style="width: 190px;"}
			{formtool_move button_text="&rarr;" from="blockSelectLeft[]" to="blockSelectRight[]" save_from="blockSelectLeft_save" save_to="blockSelectRight_save" class="button defaultButton" style="width: 190px;"}
		</td>
	</tr>
	<tr>
		<td colspan="5" align="center">
		 <br />
		 <input type="hidden" name="preview" value=0>
			<input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
			<input type="button" value="{translate key="plugins.generic.layoutmanager.form.preview"}" class="button defaultButton" onclick="this.form.preview.value=1;this.form.submit()" />
			<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="plugins" escape=false}'" />
		</td>
	</tr>
</table>



</form>

{include file="common/footer.tpl"}
