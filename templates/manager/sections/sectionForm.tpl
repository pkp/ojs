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

{assign var="pageTitle" value="section.sections"}
{assign var="currentUrl" value="$pageUrl/manager/sections"}
{assign var="pageId" value="manager.sections.sectionForm"}
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

<div class="form">
	{include file="common/formErrors.tpl"}

<span class="formRequired">{translate key="form.required"}</span>
<br /><br />

<table class="form">
<tr>
	<td class="formLabel">{formLabel name="title" required="true"}{translate key="section.title"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="abbrev" required="true"}{translate key="section.abbreviation"}:{/formLabel}</td>
	<td class="formField"><input type="text" name="abbrev" value="{$abbrev|escape}" size="20" maxlength="20" class="textField" /></td>
</tr>
<tr>
	<td class="formLabel">{formLabel name="policy"}{translate key="manager.sections.policy"}:{/formLabel}</td>
	<td class="formField"><textarea name="policy" rows="4" cols="40" class="textArea">{$policy|escape}</textarea></td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<td class="formLabel"></td>
	<td class="formLabel" style="text-align: left;">{translate key="manager.sections.sectionSubmissionOptions"}</td>
</tr>
<tr>
	<td class="formLabel"></td>
	<td class="formField"><input type="checkbox" name="peerReviewed" value="1" {if $peerReviewed}checked="checked"{/if} />{formLabel name="peerReviewed"}{translate key="manager.sections.peerReviewedDescription"}{/formLabel}</td>
</tr>
<tr>
	<td class="formLabel"></td>
	<td class="formField"><input type="checkbox" name="metaIndexed" value="1" {if $metaIndexed}checked="checked"{/if} />{formLabel name="metaIndexed"}{translate key="manager.sections.openSubmissionsDescription"}{/formLabel}</td>
</tr>
<tr>
	<td class="formLabel"></td>
	<td class="formField"><input type="checkbox" name="authorIndexed" value="1" {if $authorIndexed}checked="checked"{/if} />{formLabel name="authorIndexed"}{translate key="manager.sections.IndexedDescription"}{/formLabel}</td>
</tr>
<tr>
	<td class="formLabel"></td>
	<td class="formField"><input type="checkbox" name="rst" value="1" {if $rst}checked="checked"{/if} />{formLabel name="rst"}{translate key="manager.sections.researchSupportToolDescription"}{/formLabel}</td>
</tr>
<tr>
	<td colspan="2">&nbsp;</td>
</tr>
<tr>
	<td class="formLabel">{translate key="manager.sections.editors"}:</td>
	<td>
		<table class="plain">
		<tr>
			<td>{translate key="manager.sections.unassigned"}</td>
			<td></td>
			<td>{translate key="manager.sections.assigned"}</td>
		</tr>
		<tr>
			<td><select name="unassigned" size="15" style="width: 150px" class="selectMenu">
			{foreach from=$unassignedEditors item=editor}
				<option value="{$editor->getUserId()}">{$editor->getFullName()}</option>
			{/foreach}
			</select></td>
			<td><input type="button" value="{translate key="manager.sections.assignEditor"} &gt;&gt;" onclick="moveSelectItem(this.form.unassigned, this.form.assigned)" class="formButtonPlain">
			<br /><br />
			<input type="button" value="&lt;&lt; {translate key="manager.sections.unassignEditor"}" onclick="moveSelectItem(this.form.assigned, this.form.unassigned)" class="formButtonPlain"></td>
			<td><select name="assigned" size="15" style="width: 150px" class="selectMenu">
			{foreach from=$assignedEditors item=editor}
				<option value="{$editor->getUserId()}">{$editor->getFullName()}</option>
			{/foreach}
			</select></td>
		</tr>
		</table>
	</td>
</tr>
<tr>
	<td></td>
	<td class="formInstructions">{translate key="manager.sections.assignEditorInstructions"}</td>
</tr>
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/sections'" /></td>
</tr>
</table>

</div>
</form>

{include file="common/footer.tpl"}
