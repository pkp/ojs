{**
 * plugins/importexport/native/importSuccess.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a list of the successfully-imported entities.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.importexport.native.import.success"}
{include file="common/header.tpl"}
{/strip}
<div id="importSuccess">
<p>{translate key="plugins.importexport.native.import.success.description"}</p>

{if $issues}
<h3>{translate key="issue.issues"}</h3>
<ul>
	{foreach from=$issues item=issue}
		<li>{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</li>
	{/foreach}
	</ul>
{/if}

{if $articles}
<h3>{translate key="article.articles"}</h3>
<ul>
	{foreach from=$articles item=article}
		<li>{$article->getLocalizedTitle()|strip_unsafe_html}</li>
	{/foreach}
	</ul>
{/if}
</div>
{include file="common/footer.tpl"}
