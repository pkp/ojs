{**
 * issueToc.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the issue's table of contents
 *
 * $Id$
 *}

<ul id="tabnav" style="border-bottom: none;">
	<li><a href="{$requestPageUrl}/issueManagement/issueToc/{$issueId}" class="active">{translate key="editor.issues.toc"}</a></li>
	<li><a href="{$requestPageUrl}/issueManagement/issueData/{$issueId}">{translate key="editor.issues.issueData"}</a></li>
	<li><a href="{$requestPageUrl}/issueManagement/issueFrontMatter/{$issueId}">{translate key="editor.issues.frontMatter"}</a></li>
</ul>

<div id="content">

<div id="contentMain">

	<div id="contentHeader">
		<table>
			<tr>
				<td>{translate key="editor.issues.summary"}</td>
			</tr>
		</table>
	</div>

	<div id="tocSummary">
	<table>
			<tr>
				<td><i>{translate key="editor.issues.toc"}</i>:&nbsp;{translate key="editor.issues.vol"}&nbsp;{$issue->getVolume()},&nbsp;{translate key="editor.issues.no"}&nbsp;{$issue->getNumber()}</td>
				<td align="right">{if ($issueAccess == 1)}{translate key="editor.issues.openAccess"}{else}{translate key="editor.issues.subscription"}&nbsp;{translate key="editor.issues.accessDate"}&nbsp;{$issue->getOpenAccessDate()|date_format:"$dateFormatShort"}{/if}</td>
			</tr>
			{if $issue->getTitle()}
			<tr>
				<td colspan="2"><i>{translate key="common.title"}</i>:&nbsp;{$issue->getTitle()}</td>
			</tr>
			{/if}
			{if $issue->getDescription()}
			<tr>
				<td colspan="2"><i>{translate key="common.description"}</i>:&nbsp;{$issue->getDescription()|nl2br}</td>
			</tr>
			{/if}
	</table>
	</div>

	<div id="hitlistHeader">
		<table>
			<tr>
				<td width="8%" align="center">{translate key="editor.issues.order"}</td>
				<td width="20%">{translate key="editor.issues.authors"}</td>
				{if ($issueAccess == 1)}
					{assign var="titleWidth" value="51%"}
					{assign var="truncateSize" value="50"}
					{if !$enablePublicArticleId}
						{assign var="titleWidth" value="64%"}
						{assign var="truncateSize" value="70"}					
					{/if}				
				{elseif (($issueAccess == 2) && $enableSubscriptions)}
					{assign var="titleWidth" value="33%"}
					{assign var="truncateSize" value="33"}
					{if !$enablePublicArticleId}
						{assign var="titleWidth" value="46%"}
						{assign var="truncateSize" value="50"}					
					{/if}				
				{/if}
				<td width="{$titleWidth}">{translate key="common.title"}</td>
				{if (($issueAccess == 2) && $enableSubscriptions)}
				<td width="18%" align="center">{translate key="editor.issues.access"}</td>
				{/if}
				{if $enablePublicArticleId}
				<td width="12%" align="center">{translate key="editor.issues.publicId"}</td>
				{/if}
				<td width="9%" align="center">{translate key="common.remove"}</td>
			</tr>
		</table>
	</div>

	<form id="issueToc" method="post" action="{$pageUrl}/editor/updateIssueToc/{$issueId}" onsubmit="return confirm('{translate|escape:"javascript" key="editor.issues.saveChanges"}')">

	{foreach from=$sections item=section}

	<div id="tocSection">
		<table>
			<tr>
				<td>{$section[1]}&nbsp;<a href="{$pageUrl}/editor/moveSectionToc/{$issueId}?d=u&amp;sectionId={$section[0]}">&uarr;</a>&nbsp;<a href="{$pageUrl}/editor/moveSectionToc/{$issueId}?d=d&amp;sectionId={$section[0]}">&darr;</a></td>
			</tr>
		</table>
	</div>

	<div id="tocHitlist">
		{foreach from=$section[2] item=article}
		<div id="record">
			<table>
				{assign var="articleId" value=$article->getArticleID()}
				{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/submission/$articleId');\""}
				<tr class="{cycle name="cycle1" values="row,rowAlt"}">
					<td width="8%" align="center" valign="top" {$onclick}>{$article->getSeq()}&nbsp;<a href="{$pageUrl}/editor/moveArticleToc/{$issueId}?d=u&amp;sectionId={$section[0]}&amp;pubId={$article->getPubId()}">&uarr;</a>&nbsp;<a href="{$pageUrl}/editor/moveArticleToc/{$issueId}?d=d&amp;sectionId={$section[0]}&amp;pubId={$article->getPubId()}">&darr;</a></td>
					<td width="20%" {$onclick}>
						<div>
						{foreach from=$article->getAuthors() item=author name=authorList}
							{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
						{/foreach}
						</div>				
					</td>
					<td width="{$titleWidth}" {$onclick}>{$article->getArticleTitle()|truncate:$truncateSize:"..."}</td>
					{if (($issueAccess == 2) && $enableSubscriptions)}
					<td width="18%" align="center"><select name="accessStatus[{$article->getPubId()}]" size="1" class="selectMenu">{html_options options=$accessOptions selected=$article->getAccessStatus()}</select></td>
					{/if}
					{if $enablePublicArticleId}
					<td width="12%" class="formField" align="center"><input type="text" name="publishedArticles[{$article->getArticleId()}]" value="{$article->getPublicArticleId()|escape}" size="10" maxlength="10" class="textField" /></td>
					{/if}
					<td width="9%" align="center" valign="top"><input type="checkbox" class="optionCheckBox" name="remove[{$article->getArticleId()}]" value="{$article->getPubId()}" class="optionCheckBox" onclick="javascript:markRow(this,'selectedRow','{cycle name="cycle2" values="row,rowAlt"}');" /></td>
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

	{/foreach}

	<div id="hitlistFooter">
		<table>
			<tr>
				<td width="50%" align="left"><input type="submit" value="{translate key="common.saveChanges"}" />&nbsp;{if $unpublished}<input type="button" value="{translate key="editor.issues.publishIssue"}" onclick="confirmAction('{$requestPageUrl}/publishIssue/{$issueId}', '{translate|escape:"javascript" key="editor.issues.confirmPublish"}')" />{/if}</td>
				<td width="50%" align="right"><a href="javascript:checkAll('issueToc', 'optionCheckBox', true, 'selectedRow', 'selectedRow');">{translate key="common.selectAll"}</a>&nbsp;|&nbsp;<a href="javascript:checkAll('issueToc', 'optionCheckBox', false, 'row', 'rowAlt');">{translate key="common.selectNone"}</a></td>
			</tr>
		</table>
	</div>

	</form>



</div>

</div>
