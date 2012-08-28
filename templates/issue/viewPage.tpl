{**
 * templates/issue/viewPage.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View issue: This adds the header and footer code to view.tpl.
 *}
{include file="issue/header.tpl"}

{foreach from=$pubIdPlugins item=pubIdPlugin}
	{if $issue->getPublished()}
		{assign var=pubId value=$pubIdPlugin->getPubId($issue)}
	{else}
		{assign var=pubId value=$pubIdPlugin->getPubId($issue, true)}{* Preview rather than assign a pubId *}
	{/if}
	{if $pubId}
		{$pubIdPlugin->getPubIdDisplayType()|escape}: {if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">{$pubId|escape}</a>{else}{$pubId|escape}{/if}
		<br />
		<br />
	{/if}
{/foreach}

{include file="issue/view.tpl"}

{include file="common/footer.tpl"}

