{**
 * frontMatter.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to create or edit a front matter
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
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/frontMatterSections/{$issueId}">{translate key="editor.issues.frontMatterSections"}</a></li>
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/issueFrontMatter/{$issueId}">{translate key="editor.issues.frontMatter"}</a></li>
	{if $frontId}<li class="subnav"><a href="{$requestPageUrl}/issueManagement/frontMatter/{$issueId}">{translate key="editor.issues.addFrontMatter"}</a></li>{/if}
</ul>

	<div id="contentMain">

	<div id="contentHeader">
		<table>
			<tr>
				<td>{if $frontId}{translate key="editor.issues.editFrontMatter"}{else}{translate key="editor.issues.addFrontMatter"}{/if}</td>
				<td align="right">{translate key="form.required"}</td>
			</tr>
		</table>
	</div>

	{if $isError}
	<div id="formError">
		{include file="common/formErrors.tpl"}
	</div>
	{/if}

	<form name="frontMatterForm" method="post" action="{$requestPageUrl}/updateFrontMatter/{$issueId}/{$frontId}" enctype="multipart/form-data">
	<input type="hidden" name="issueId" value="{$issueId}" />
	<input type="hidden" name="frontId" value="{$frontId}" />
	
	<div id="form">
		<table>
		<tr>
			<td class="formFieldLabelRequired">{formLabel name="frontSectionId" required="true"}{translate key="editor.issues.frontMatterSections"}{/formLabel}</td>
			<td class="formField"><select name="frontSectionId" size="1" class="selectMenu">{html_options options=$frontSectionIdOptions selected=$frontSectionId}</select></td>
		</tr>
		<tr>
			<td class="formFieldLabelRequired">{formLabel name="upload" required="true"}{translate key="editor.issues.fileName"}{/formLabel}</td>
			<td class="formField"><input type="file" name="upload" class="textField" />{if $frontId}&nbsp;{translate key="editor.issues.uploaded"}:&nbsp;<a href="{$pageUrl}/editor/download/{$issueId}/{$fileName}" class="file">{$originalFileName}</a>{/if}</td>
		</tr>
		<tr>
			<td class="formFieldLabelRequired">{formLabel name="title" required="true"}{translate key="common.title"}{/formLabel}</td>
			<td class="formField"><input type="text" name="title" value="{$title|escape}" size="40" maxlength="255" class="textField" /></td>
		</tr>
		<tr>
			<td class="formFieldLabel">{translate key="editor.issues.coverPageOption"}</td>
			<td class="formField"><input type="checkbox" name="cover" value="1"{if $cover} checked="checked"{/if} />&nbsp;{translate key="editor.issues.coverPageOptionMsg"}</td>
		</tr>
		</table>	
	</div>

	<div id="formFooter" align="right">
		{if $frontId}<input type="button" value="{translate key="common.delete"}" onclick="confirmAction('{$pageUrl}/editor/removeFrontMatter/{$issueId}/{$frontId}', '{translate|escape:"javascript" key="editor.issues.confirmFrontMatterDelete"}')" />&nbsp;{/if}
		<input type="submit" value="{if $frontId}{translate key="common.saveChanges"}{else}{translate key="editor.issues.createFrontMatter"}{/if}" />
	</div>

	</form>

	</div>

</div>
