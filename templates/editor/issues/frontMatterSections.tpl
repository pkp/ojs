{**
 * frontMatterSections.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the issue's front matter sections
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
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/issueFrontMatter/{$issueId}">{translate key="editor.issues.frontMatter"}</a></li>
	<li class="subnav"><a href="{$requestPageUrl}/issueManagement/frontMatterSection/{$issueId}">{translate key="editor.issues.addFrontMatterSection"}</a></li>
</ul>

<div id="contentMain">

	<form id="frontMatterSections" method="post" onsubmit="return confirm('{translate|escape:"javascript" key="editor.issues.applySelectionChanges"}')">

	<div id="contentHeader">
		<table>
			<tr>
				<td>{translate key="editor.issues.frontMatterSections"}</td>
			</tr>
		</table>
	</div>
	
	<div id="hitlistHeader">
		<table>
			<tr>
				<td width="80%">{translate key="common.title"}</td>
				<td width="10%" align="center">{translate key="editor.issues.abbrev"}</td>
				<td width="10%" align="center">{translate key="common.select"}</td>
			</tr>
		</table>
	</div>

	<div id="hitlist">
		{foreach from=$frontMatterSections item=frontMatterSection}
		<div id="record">
			<table>
				{assign var="frontSectionId" value=$frontMatterSection->getFrontSectionId()}
				{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/issueManagement/frontMatterSection/$issueId/$frontSectionId');\""}
				<tr class="{cycle name="cycle1" values="row,rowAlt"}">
					<td width="80%" {$onclick}>{$frontMatterSection->getTitle()|truncate:85:"..."}</td>
					<td width="10%" align="center" {$onclick}>{$frontMatterSection->getAbbrev()}</td>
					<td width="10%" align="center"><input name="select[]" type="checkbox" value="{$frontMatterSection->getFrontSectionId()}" class="optionCheckBox" onclick="javascript:markRow(this,'selectedRow','{cycle name="cycle2" values="row,rowAlt"}');" /></td>
				</tr>
			</table>
		</div>

		{foreachelse}
		<div id="record">
			<table>
				<tr class="row">
					<td align="center"><span class="boldText">{translate key="editor.issues.noFrontMatterSections"}</span></td>
				</tr>
			</table>
		</div>
		{/foreach}
	</div>

	<div id="hitlistFooter">
		<table>
			<tr>
				<td width="100%" align="right"><a href="javascript:checkAll('frontMatterSections', 'optionCheckBox', true, 'selectedRow', 'selectedRow');">{translate key="common.selectAll"}</a>&nbsp;|&nbsp;<a href="javascript:checkAll('frontMatterSections', 'optionCheckBox', false, 'row', 'rowAlt');">{translate key="common.selectNone"}</a>&nbsp;|&nbsp;<select name="selectOptions" onchange="javascript:changeActionAndSubmit(this.form, '{$requestPageUrl}/updateFrontMatterSections/{$issueId}/' + this.options[this.selectedIndex].value, this.options[this.selectedIndex].value);" size="1">{html_options options=$selectOptions selected=0}</select></td>
			</tr>
		</table>
	</div>

	</form>

</form>
</div>
</div>
