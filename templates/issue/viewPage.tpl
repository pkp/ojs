{**
 * templates/issue/viewPage.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View issue: This adds the header and footer code to view.tpl.
 *}
{include file="issue/header.tpl"}

{if $issue}
	{foreach from=$pubIdPlugins item=pubIdPlugin}
		{if $issue->getPublished()}
			{assign var=pubId value=$pubIdPlugin->getPubId($issue)}
		{else}
			{assign var=pubId value=$pubIdPlugin->getPubId($issue, true)}{* Preview rather than assign a pubId *}
		{/if}
		{if $pubId}
			{$pubIdPlugin->getPubIdDisplayType()|escape}: {if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}</a>{else}{$pubId|escape}{/if}
			<br />
			<br />
		{/if}
	{/foreach}
{/if}

{include file="issue/view.tpl"}

{include file="common/footer.tpl"}

