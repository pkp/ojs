{**
 * templates/issue/viewPage.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View issue: This adds the header and footer code to view.tpl.
 *}
{include file="issue/header.tpl"}

{* FIXME: This will be moved to DOI PID-plug-in in the next release. *}
{if $issue->getPublished()}
	{assign var=doi value=$issue->getPubId('doi')}
{else}
	{assign var=doi value=$issue->getPubId('doi', true)}{* Don't affix DOI *}
{/if}
{if $doi}
	doi:<a id="pub-id::doi" href="http://dx.doi.org/{$doi|escape|escape:url}">{$doi|escape}</a>
	<br />
	<br />
{/if}

{include file="issue/view.tpl"}

{include file="common/footer.tpl"}

