{**
 * schedulingQueue.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Articles waiting to be scheduled for publishing.
 *
 * $Id$
 *}

{assign var="pageTitle" value="editor.schedulingQueue"}
{assign var="currentUrl" value="$pageUrl/editor/schedulingQueue"}
{assign var="pageId" value="editor.schedulingQueue"}
{include file="common/header.tpl"}

<div id="topSelectBar">
	<span>{translate key="journal.section"}:&nbsp;<select name="section" onchange="location.href='{$pageUrl}/editor/schedulingQueue?section='+this.options[this.selectedIndex].value" size="1" class="selectMenu">{html_options options=$sectionOptions selected=$section}</select></span>
</div>

<div id="content">

<div id="contentMain">

	<form method="post" action="{$pageUrl}/editor/updateSchedulingQueue" onsubmit="return confirm('{translate|escape:"javascript" key="editor.schedulingQueue.saveChanges"}')">

	<div id="contentHeader">
		<table>
			<tr>
				<td>&nbsp;</td>
			</tr>
		</table>
	</div>

	<div id="hitlistHeader">
		<table>
			<tr>
				<td width="12%" align="center"><a href="{$pageUrl}/editor/schedulingQueue?sort=submitted&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="editor.schedulingQueue.submitted"}</a></td>
				<td width="10%" align="center"><a href="{$pageUrl}/editor/schedulingQueue?sort=section&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="editor.schedulingQueue.section"}</a></td>
				<td width="20%">{translate key="editor.article.authors"}</td>
				<td width="28%"><a href="{$pageUrl}/editor/schedulingQueue?sort=title&amp;order={$order}{if $section}&amp;section={$section}{/if}" class="sortColumn">{translate key="common.title"}</a></td>
				<td width="20%" align="center">{translate key="editor.schedulingQueue.schedule"}</td>
				<td width="10%" align="center">{translate key="common.remove"}</td>
			</tr>
		</table>
	</div>

	<div id="hitlist">
		{foreach from=$schedulingQueueSubmissions item=article}
		<div id="record">
			<table>
				{assign var="articleId" value=$article->getArticleId()}
				{assign var="onclick" value="onclick=\"javascript:loadUrl('$requestPageUrl/submission/$articleId');\""}
				<tr class="{cycle name="cycle1" values="row,rowAlt"}">
					<td width="12%" align="center" {$onclick}>{$article->getDateSubmitted()|date_format:"$dateFormatShort"}</td>
					<td width="10%" align="center" {$onclick}>{$article->getSectionAbbrev()}</td>
					<td width="20%" {$onclick}>
						<div>
						{foreach from=$article->getAuthors() item=author name=authorList}
							{$author->getLastName()}{if !$smarty.foreach.authorList.last},{/if}
						{/foreach}
						</div>
					</td>
					<td width="28%" {$onclick}>{$article->getTitle()|truncate:25:"..."}</td>
					<td width="20%" align="center"><select name="schedule[{$article->getArticleID()}]" class="smartyHtmlOptions">{html_options options=$issueOptions}</select></td>
					<td width="10%" align="center"><input type="checkbox" name="remove[]" value="{$article->getArticleID()}" /></td>
				</tr>
			</table>
		</div>
		{foreachelse}
		<div id="record">
			<table>
				<tr class="row">
					<td align="center"><span class="boldText">{translate key="editor.schedulingQueue.noSubmissions"}</span></td>
				</tr>
			</table>
		</div>
		{/foreach}
	</div>

	<div id="hitlistFooter">
		<table>
			<tr>
				<td width="100%" align="right"><input type="submit" value="{translate key="common.saveChanges"}" /></td>
			</tr>
		</table>
	</div>

	</form>

</div>

</div>

{include file="common/footer.tpl"}
