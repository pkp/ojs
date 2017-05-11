{**
 * references-list.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Create a references list.
 *}
{if $ordering == $smarty.const.REFERENCES_LIST_ORDERING_NUMERICAL}
	<ol>
		{foreach from=$citationsOutput key=seq item=citationOutput}
			<li>{$citationOutput}</li>
		{/foreach}
	</ol>
{else}
	{foreach from=$citationsOutput key=seq item=citationOutput}
		{$citationOutput}
	{/foreach}
{/if}
