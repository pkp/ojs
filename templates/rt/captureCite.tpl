{**
 * captureCite.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article reading tools -- Capture Citation
 *
 * $Id$
 *}

{assign var=pageTitle value="rst.captureCite"}

{include file="rt/header.tpl"}

{assign var=authors value=$article->getAuthors()}
{assign var=authorCount value=$authors|@count}
{foreach from=$authors item=author name=authors key=i}
	{assign var=firstName value=$author->getFirstName()}
	{$author->getLastName()}, {$firstName[0]}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
{/foreach}

{$publishedArticle->getDatePublished()|date_format:$dateFormatApa}.
{$article->getArticleTitle()}.
<i>{$journal->getTitle()}</i> {translate key="rst.captureCite.online"} {$issue->getVolume()}:{$issue->getNumber()}.
{translate key="rst.captureCite.available"} <a target="_new" href="{$pageUrl}/article/view/{$articleId}/{$galleyId}">{$pageUrl}/article/view/{$articleId}/{$galleyId}</a>

<div class="separator"></div>

<h3>{translate key="rst.captureCite.capture"}</h3>
<ul>
	<li>{translate key="rst.captureCite.capture.endNote" url="$requestPageUrl/captureCite/$articleId/$galleyId/endNote"}</li>
	<li>{translate key="rst.captureCite.capture.referenceManager" url="$requestPageUrl/captureCite/$articleId/$galleyId/referenceManager"}</li>
	<li>{translate key="rst.captureCite.capture.proCite" url="$requestPageUrl/captureCite/$articleId/$galleyId/proCite"}</li>
</ul>

<div class="separator"></div>
<a href="javascript:window.close()">{translate key="common.close"}</a>

{include file="rt/footer.tpl"}
