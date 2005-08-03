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

{assign var=pageTitle value="rt.captureCite"}

{include file="rt/header.tpl"}

<h3>"{$article->getArticleTitle()|escape}"</h3>

{if $bibFormat == 'MLA'}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()|escape}, {$firstName|escape}{if $i==$authorCount-2}, {translate key="rt.context.and"} {elseif $i<$authorCount-1}, {else}.{/if}
	{/foreach}

	"{$article->getArticleTitle()|escape}" <i>{$journal->getTitle()|escape}</i> [{translate key="rt.captureCite.online"}], {$issue->getVolume()|escape} {$article->getDatePublished()|date_format:'%e %b %Y'}

{elseif $bibFormat == 'Turabian'}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()|escape}, {$firstName|escape}{if $i==$authorCount-2}, {translate key="rt.context.and"} {elseif $i<$authorCount-1}, {else}.{/if}
	{/foreach}

	"{$article->getArticleTitle()|escape}" <i>{$journal->getTitle()|escape}</i> [{translate key="rt.captureCite.online"}], {translate key="issue.volume"} {$issue->getVolume()|escape} {translate key="issue.number"} {$issue->getNumber()|escape} ({$article->getDatePublished()|date_format:'%e %B %Y'|trim})

{elseif $bibFormat == 'CBE'}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()|escape}, {$firstName[0]|escape}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
	{/foreach}

	{$article->getDatePublished()|date_format:'%Y %b %e'}. {$article->getArticleTitle()|escape}. {$journal->getTitle()|escape}. [{translate key="rt.captureCite.online"}] {$issue->getVolume()|escape}:{$issue->getNumber()|escape}

{elseif $bibFormat == 'BibTeX'}

{literal}
<pre style="font-size: 1.5em;">@article{{{/literal}{$journal->getSetting('journalInitials')|escape}{literal}}{{/literal}{$articleId|escape}{literal}},
	author = {{/literal}{assign var=authors value=$article->getAuthors()}{foreach from=$authors item=author name=authors key=i}{$author->getLastName()|escape}, {assign var=firstName value=$author->getFirstName()}{assign var=authorCount value=$authors|@count}{$firstName[0]|escape}.{if $i<$authorCount-1}, {/if}{/foreach}{literal}},
	title = {{/literal}{$article->getArticleTitle()|escape}{literal}},
	journal = {{/literal}{$journal->getTitle()|escape}{literal}},
	volume = {{/literal}{$issue->getVolume()|escape}{literal}},
	number = {{/literal}{$issue->getNumber()|escape}{literal}},
	year = {{/literal}{$article->getDatePublished()|date_format:'%Y'}{literal}},
{/literal}{assign var=issn value=$journal->getSetting('issn')|escape}{if $issn}{literal}	issn = {{/literal}{$issn}{literal}},{/literal}{/if}{literal}
	url = {{/literal}{$pageUrl}/article/view/{$articleId|escape:"url"}/{$galleyId}{literal}}
}
</pre>
{/literal}

{elseif $bibFormat == 'ABNT'}

	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()|escape}, {$firstName[0]|escape}.{if $i<$authorCount-1}; {/if}{/foreach}.
	{$article->getArticleTitle()|escape}.
	<b>{$journal->getTitle()|escape}</b>, {translate key="rt.captureCite.acaoLocation"}, {$issue->getVolume()|escape}
	{$article->getDatePublished()|date_format:'%e %m %Y'}.

{else}
	{assign var=authors value=$article->getAuthors()}
	{assign var=authorCount value=$authors|@count}
	{foreach from=$authors item=author name=authors key=i}
		{assign var=firstName value=$author->getFirstName()}
		{$author->getLastName()|escape}, {$firstName[0]|escape}.{if $i==$authorCount-2}, &amp; {elseif $i<$authorCount-1}, {/if}
	{/foreach}

	{$article->getDatePublished()|date_format:'%Y %b %e'}.
	{$article->getArticleTitle()|escape}.
	<i>{$journal->getTitle()|escape}</i> [{translate key="rt.captureCite.online"}] {$issue->getVolume()|escape}:{$issue->getNumber()|escape}.
	{translate key="rt.captureCite.available"} <a target="_new" href="{$pageUrl}/article/view/{$articleId|escape:"url"}/{$galleyId}">{$pageUrl}/article/view/{$articleId|escape:"url"}/{$galleyId}</a>
{/if}

<br />
<br />

<div class="separator"></div>

<h3>{translate key="rt.captureCite.capture"}</h3>
<ul>
	{assign var=escapedArticleId value=$articleId|escape:"url"}
	<li>{translate key="rt.captureCite.capture.endNote" url="$requestPageUrl/captureCite/$escapedArticleId/$galleyId/endNote"}</li>
	<li>{translate key="rt.captureCite.capture.referenceManager" url="$requestPageUrl/captureCite/$escapedArticleId/$galleyId/referenceManager"}</li>
	<li>{translate key="rt.captureCite.capture.proCite" url="$requestPageUrl/captureCite/$escapedArticleId/$galleyId/proCite"}</li>
</ul>

{include file="rt/footer.tpl"}
