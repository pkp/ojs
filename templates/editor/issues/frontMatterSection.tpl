{**
 * frontMatterSection.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create or edit a front matter section
 *
 * $Id$
 *}

<ul id="tabnav" style="border: none;">
	<li><a href="{$requestPageUrl}/issueManagement/issueToc/{$issueId}">{translate key="editor.issues.toc"}</a></li>
	<li><a href="{$requestPageUrl}/issueManagement/issueData/{$issueId}">{translate key="editor.issues.issueData"}</a></li>
	<li><a href="{$requestPageUrl}/issueManagement/issueFrontMatter/{$issueId}" class="active">{translate key="editor.issues.frontMatter"}</a></li>
</ul>

<div id="content">
<ul class="subnav">
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/issueFrontMatter/{$issueId}">{translate key="editor.issues.frontMatter"}</a></li>
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/frontMatterSections/{$issueId}">{translate key="editor.issues.frontMatterSections"}</a></li>
	{if $frontSectionId}<li class="subnav"><a href="{$requestPageUrl}/issueManagement/frontMatterSection/{$issueId}">{translate key="editor.issues.addFrontMatterSection"}</a></li>{/if}
</ul>

	<div id="contentMain">

	<div id="contentHeader">
		<table>
			<tr>
				<td>{if $frontSectionId}{translate key="editor.issues.editFrontMatterSection"}{else}{translate key="editor.issues.addFrontMatterSection"}{/if}</td>
				<td align="right">{translate key="form.required"}</td>
			</tr>
		</table>
	</div>

	{if $isError}
	<div id="formError">
		{include file="common/formErrors.tpl"}
	</div>
	{/if}

	<form name="frontMatterSectionForm" method="post" action="{$requestPageUrl}/updateFrontMatterSection/{$frontSectionId}">
	<input type="hidden" name="issueId" value="{$issueId}" />
	<input type="hidden" name="frontSectionId" value="{$frontSectionId}" />

	<div id="form">
		<table>
		<tr>
			<td class="formFieldLabelRequired">{formLabel name="title" required="true"}{translate key="common.title"}{/formLabel}</td>
			<td class="formField"><input type="text" name="title" value="{$title|escape}" size="40" maxlength="120" class="textField" /></td>
		</tr>
		<tr>
			<td class="formFieldLabelRequired">{formLabel name="abbrev" required="true"}{translate key="editor.issues.abbrev"}{/formLabel}</td>
			<td class="formField"><input type="text" name="abbrev" value="{$abbrev|escape}" size="20" maxlength="5" class="textField" /></td>
		</tr>
		</table>
	</div>

	<div id="formFooter" align="right">
		{if $frontSectionId}<input type="button" value="{translate key="common.delete"}" onclick="confirmAction('{$pageUrl}/editor/removeFrontMatterSection/{$issueId}/{$frontSectionId}', '{translate|escape:"javascript" key="editor.issues.confirmFrontMatterSectionDelete"}')" />&nbsp;{/if}
		<input type="submit" value="{if $frontSectionId}{translate key="common.saveChanges"}{else}{translate key="editor.issues.createFrontMatterSection"}{/if}" />
	</div>
	</form>

	</div>

</div>


