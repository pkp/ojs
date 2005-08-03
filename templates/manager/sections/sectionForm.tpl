{**
 * sectionForm.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create/modify a journal section.
 *
 * $Id$
 *}

{assign var="pageTitle" value="section.section"}
{assign var="pageCrumbTitle" value="section.sections"}
{assign var="currentUrl" value="$pageUrl/manager/sections"}
{include file="common/header.tpl"}

<form name="section" method="post" action="{$pageUrl}/manager/updateSection" onsubmit="return saveSelectedEditors()">
{if $sectionId}
<input type="hidden" name="sectionId" value="{$sectionId}" />
{/if}
<input type="hidden" name="assignedEditors" value="" />
<input type="hidden" name="unassignedEditors" value="" />

{literal}
<script type="text/javascript">
	// Move the currently selected item between two select menus
	function moveSelectItem(currField, newField) {
		var selectedIndex = currField.selectedIndex;
		
		if (selectedIndex == -1) {
			return;
		}
		
		var selectedOption = currField.options[selectedIndex];
		
		// Add item to new menu
		newField.options.length += 1;
		newField.options[newField.options.length - 1] = new Option(selectedOption.text, selectedOption.value);

		// Delete item from old menu
		for (var i = selectedIndex + 1; i < currField.options.length; i++) {
			currField.options[i - 1].value = currField.options[i].value;
			currField.options[i - 1].text = currField.options[i].text;
		}
		currField.options.length -= 1;
		
		// Update selected item
		if (currField.options.length > 0) {
			currField.selectedIndex = selectedIndex < (currField.options.length - 1) ? selectedIndex : (currField.options.length - 1);
		}
	}
	
	// Save IDs of selected editors in hidden field
	function saveSelectedEditors() {
		var assigned = document.section.assigned;
		var assignedIds = '';
		for (var i = 0; i < assigned.options.length; i++) {
			if (assignedIds != '') {
				assignedIds += ':';
			}
			assignedIds += assigned.options[i].value;
		}
		document.section.assignedEditors.value = assignedIds;
		
		var unassigned = document.section.unassigned;
		var unassignedIds = '';
		for (var i = 0; i < unassigned.options.length; i++) {
			if (unassignedIds != '') {
				unassignedIds += ':';
			}
			unassignedIds += unassigned.options[i].value;
		}
		document.section.unassignedEditors.value = unassignedIds;
		
		return true;
	}
</script>
{/literal}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="title" required="true" key="section.title"}</td>
	<td width="80%" class="value"><input type="text" name="title" value="{$title|escape}" id="title" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="abbrev" required="true" key="section.abbreviation"}</td>
	<td class="value"><input type="text" name="abbrev" id="abbrev" value="{$abbrev|escape}" size="20" maxlength="20" class="textField" />&nbsp;&nbsp;{translate key="section.abbreviation.example"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="policy" key="manager.sections.policy"}</td>
	<td class="value"><textarea name="policy" rows="4" cols="40" id="policy" class="textArea">{$policy|escape}</textarea></td>
</tr>
<tr valign="top">
	<td rowspan="2" class="label">{fieldLabel key="submission.indexing"}</td>
	<td class="value">
		<input type="checkbox" name="metaIndexed" id="metaIndexed" value="1" {if $metaIndexed}checked="checked"{/if} />
		{fieldLabel name="metaIndexed" key="manager.sections.submissionIndexing"}
	</td>
</tr>
<tr valign="top">
	<td class="value">
		{fieldLabel name="identifyType" key="manager.sections.identifyType"} <input type="text" name="identifyType" id="identifyType" value="{$identifyType|escape}" size="20" maxlength="60" class="textField" />
		<br />
		<span class="instruct">{translate key="manager.sections.identifyTypeExamples"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel key="submission.restrictions"}</td>
	<td class="value">
		<input type="checkbox" name="editorRestriction" id="editorRestriction" value="1" {if $editorRestriction}checked="checked"{/if} />
		{fieldLabel name="editorRestriction" key="manager.sections.editorRestriction"}
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="hideTitle" key="issue.toc"}</td>
	<td class="value">
		<input type="checkbox" name="hideTitle" id="hideTitle" value="1" {if $hideTitle}checked="checked"{/if} />
		{fieldLabel name="hideTitle" key="manager.sections.hideTocTitle"}
	</td>
</tr>
</table>
<div class="separator"></div>

<h3>{translate key="user.role.sectionEditors"}</h3>
<p><span class="instruct">{translate key="manager.section.sectionEditorInstructions"}</span></p>
<table class="data" width="100%">
<tr valign="top">
	<td width="20%">&nbsp;</td>
	<td>{translate key="manager.sections.unassigned"}</td>
	<td>&nbsp;</td>
	<td>{translate key="manager.sections.assigned"}</td>
</tr>
<tr valign="top">
	<td width="20%">&nbsp;</td>
	<td><select name="unassigned" size="15" style="width: 150px" class="selectMenu">
		{foreach from=$unassignedEditors item=editor}
			<option value="{$editor->getUserId()}">{$editor->getFullName()|escape}</option>
		{/foreach}
	</select></td>
	<td><input type="button" value="{translate key="manager.sections.assignEditor"} &gt;&gt;" onclick="moveSelectItem(this.form.unassigned, this.form.assigned)" class="button" />
		<br /><br />
		<input type="button" value="&lt;&lt; {translate key="manager.sections.unassignEditor"}" onclick="moveSelectItem(this.form.assigned, this.form.unassigned)" class="button" /></td>
	<td><select name="assigned" size="15" style="width: 150px" class="selectMenu">
		{foreach from=$assignedEditors item=editor}
			<option value="{$editor->getUserId()}">{$editor->getFullName()|escape}</option>
		{/foreach}
	</select></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{$pageUrl}/manager/sections'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
{include file="common/footer.tpl"}
