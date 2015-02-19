{**
 * templates/admin/languageDownloadErrors.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display error messages associated with a failed language download.
 *
 *}
{strip}
{assign var="pageTitle" value="common.languages"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="admin.languages.downloadLocales"}</h3>

<p>{translate key="admin.languages.downloadFailed"}</p>
<ul>
	{foreach from=$errors item=error}<li>{$error}</li>{/foreach}
</ul>

<a href="{url op="languages"}" class="action">{translate key="common.languages"}</a>

{include file="common/footer.tpl"}

