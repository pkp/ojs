{**
 * issueFrontMatter.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the issue's front matter
 *
 * $Id$
 *}

<ul id="tabnav" style="border-bottom: none;">
	<li><a href="{$requestPageUrl}/issueManagement/issueToc/{$issueId}">{translate key="editor.issues.toc"}</a></li>
	<li><a href="{$requestPageUrl}/issueManagement/issueData/{$issueId}">{translate key="editor.issues.issueData"}</a></li>
	<li><a href="{$requestPageUrl}/issueManagement/issueFrontMatter/{$issueId}" class="active">{translate key="editor.issues.frontMatter"}</a></li>
</ul>

<div id="content">
<ul class="subnav">
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/frontMatterSections/{$issueId}">{translate key="editor.issues.frontMatterSections"}</a></li>
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/frontMatter/{$issueId}">{translate key="editor.issues.addFrontMatter"}</a></li>
</ul>

<div id="contentMain">

	<form id="issueFrontMatter" method="post" onsubmit="return confirm('{translate|escape:"javascript" key="editor.issues.applySelectionChanges"}')">

	<div id="contentHeader">
		<table>
			<tr>
				<td>{translate key="editor.issues.frontMatter"}</td>
			</tr>
		</table>
	</div>

	<div id="hitlistHeader">
		<table>
			<tr>
				<td width="13%" align="center">{translate key="editor.issues.modified"}</td>
				<td width="10%" align="center">{translate key="editor.issues.section"}</td>
				<td width="47%">{translate key="common.title"}</td>
				<td width="20%">{translate key="editor.issues.fileName"}</td>
				<td width="10%" align="center">{translate key="common.select"}</td>
			</tr>
		</table>
	</div>

	<div id="hitlist">
		{foreach from=$frontMatters item=frontMatter}
		<div id="record">
			<table>
				{assign var="frontId" value=$frontMatter->getFrontId()}
				{assign var="frontSectionId" value=$frontMatter->getFrontSectionId()}
				{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/issueManagement/frontMatter/$issueId/$frontId');\""}
				<tr class="{cycle name="cycle1" values="row,rowAlt"}">
					<td width="13%" align="center" {$onclick}>{$frontMatter->getDateModified()|date_format:"$dateFormatShort"}</td>
					<td width="10%" align="center" {$onclick}>{$frontMatterSections[$frontSectionId]}</td>
					<td width="47%" {$onclick}>{if $frontMatter->getCover()}<span style="color: #FF6633;">{translate key="editor.issues.coverPage"}</span>&nbsp;{$frontMatter->getTitle()|truncate:35:"..."}{else}{$frontMatter->getTitle()|truncate:45:"..."}{/if}</td>
					<td width="20%" {$onclick}><a href="{$pageUrl}/editor/download/{$issueId}/{$frontMatter->getFileName()}" class="file">{$frontMatter->getOriginalFileName()}</a></td>
					<td width="10%" align="center"><input name="select[]" type="checkbox" value="{$frontMatter->getFrontId()}" class="optionCheckBox" onclick="javascript:markRow(this,'selectedRow','{cycle name="cycle2" values="row,rowAlt"}');" /></td>
				</tr>
			</table>
		</div>
		{foreachelse}
		<div id="record">
			<table>
				<tr class="row">
					<td align="center"><span class="boldText">{translate key="editor.issues.noFrontMatter"}</span></td>
				</tr>
			</table>
		</div>
		{/foreach}
	</div>

	<div id="hitlistFooter">
		<table>
			<tr>
				<td width="100%" align="right"><a href="javascript:checkAll('issueFrontMatter', 'optionCheckBox', true, 'selectedRow', 'selectedRow');">{translate key="common.selectAll"}</a>&nbsp;|&nbsp;<a href="javascript:checkAll('issueFrontMatter', 'optionCheckBox', false, 'row', 'rowAlt');">{translate key="common.selectNone"}</a>&nbsp;|&nbsp;<select name="selectOptions" onchange="javascript:changeActionAndSubmit(this.form, '{$requestPageUrl}/updateIssueFrontMatter/{$issueId}/' + this.options[this.selectedIndex].value, this.options[this.selectedIndex].value);" size="1">{html_options options=$selectOptions selected=0}</select></td>
			</tr>
		</table>
	</div>

	</form>

</div>

</div>
