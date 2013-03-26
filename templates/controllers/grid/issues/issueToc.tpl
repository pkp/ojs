{**
 * templates/controllers/grid/issues/issueToc.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the issue's table of contents
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#issueTocForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="issueTocForm" method="post" action="{url op="updateIssueToc" issueId=$issueId}"}')">

{assign var=numCols value=5}
{if $issueAccess == $smarty.const.ISSUE_ACCESS_SUBSCRIPTION && $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}{assign var=numCols value=$numCols+1}{/if}
{if $enablePublicArticleId}{assign var=numCols value=$numCols+1}{/if}
{if $enablePageNumber}{assign var=numCols value=$numCols+1}{/if}

{foreach from=$sections key=sectionKey item=section}
<h4>{$section[1]}&uarr; &darr;</h4>

<table class="listing" id="issueToc-{$sectionKey|escape}">
	<tr>
		<td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="5%">&nbsp;</td>
		<td>{translate key="article.authors"}</td>
		<td>{translate key="article.title"}</td>
		{if $issueAccess == $smarty.const.ISSUE_ACCESS_SUBSCRIPTION && $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}<td>{translate key="editor.issues.access"}</td>{/if}
		{if $enablePublicArticleId}<td width="7%">{translate key="editor.issues.publicId"}</td>{/if}
		{if $enablePageNumber}<td width="7%">{translate key="editor.issues.pages"}</td>{/if}
		<td width="5%">{translate key="common.remove"}</td>
		<td width="5%">{translate key="editor.issues.proofed"}</td>
	</tr>
	<tr>
		<td colspan="{$numCols|escape}" class="headseparator">&nbsp;</td>
	</tr>

	{assign var="articleSeq" value=0}
	{foreach from=$section[2] item=article name="currSection"}

	{assign var="articleSeq" value=$articleSeq+1}
	{assign var="articleId" value=$article->getId()}
	<tr id="article-{$article->getPublishedArticleId()|escape}" class="data">
		<td>&uarr; &darr;</td>
		<td>
			{foreach from=$article->getAuthors() item=author name=authorList}
				{$author->getLastName()|escape}{if !$smarty.foreach.authorList.last},{/if}
			{/foreach}
		</td>
		<td class="drag">{$article->getLocalizedTitle()|strip_tags|truncate:60:"..."}</td>
		{if $issueAccess == $smarty.const.ISSUE_ACCESS_SUBSCRIPTION && $currentJournal->getSetting('publishingMode') == $smarty.const.PUBLISHING_MODE_SUBSCRIPTION}
		<td><select name="accessStatus[{$article->getPublishedArticleId()}]" size="1" class="selectMenu">{html_options options=$accessOptions selected=$article->getAccessStatus()}</select></td>
		{/if}
		{if $enablePublicArticleId}
		<td><input type="text" name="publishedArticles[{$article->getId()}]" value="{$article->getPubId('publisher-id')|escape}" size="7" maxlength="255" class="textField" /></td>
		{/if}
		{if $enablePageNumber}<td><input type="text" name="pages[{$article->getId()}]" value="{$article->getPages()|escape}" size="7" maxlength="255" class="textField" /></td>{/if}
		<td><input type="checkbox" name="remove[{$article->getId()}]" value="{$article->getPublishedArticleId()}" /></td>
		<td>
			{if in_array($article->getId(), $proofedArticleIds)}
				{icon name="checked"}
			{else}
				{icon name="unchecked"}
			{/if}
		</td>
	</tr>
	{/foreach}
</table>
{foreachelse}
<p><em>{translate key="editor.issues.noArticles"}</em></p>

<div class="separator"></div>
{/foreach}

<input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
{if $unpublished && !$isLayoutEditor}
	{* Unpublished; give the option to publish it. *}
	<input type="button" value="{translate key="editor.issues.publishIssue"}" class="button" />
{elseif !$isLayoutEditor}
	{* Published; give the option to unpublish it. *}
	<input type="button" value="{translate key="editor.issues.unpublishIssue"}" class="button" />
{/if}

</form>
