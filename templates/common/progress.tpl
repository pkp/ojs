{**
 * templates/common/progress.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Generic progress bar page.
 * Displays a simple message and progress bar.
 *
 *}
{strip}
{include file="common/header.tpl"}
{/strip}

<p>{translate key=$message}</p>

<div class="progressbar">
  {call_progress_function}
</div>

{if $backLink}
	<ul>
		<li><a href="{$backLink}">{translate key="$backLinkLabel"}</a></li>
	</ul>
{/if}

{include file="common/footer.tpl"}
