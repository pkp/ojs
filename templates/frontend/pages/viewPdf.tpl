{**
 * templates/frontend/pages/viewPdf.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Lightweight page for viewing PDF files
 *
 * @uses $pdfTitle string Title of the pdf being viewed
 *       attached to
 * @uses $galley Galley The PDF galley to display
 * @uses $parent Issue|Article Object which this galley is attached to
 *}

{* Get the Id of the parent object *}
{if $parent instanceOf Issue}
	{assign var="parentId" value=$parent->getBestIssueId($currentJournal)}
	{url|assign:"parentUrl" op="view" path=$parentId}
{else}
	{assign var="parentId" value=$parent->getBestArticleId($currentJournal)}
	{url|assign:"parentUrl" page="article" op="view" path=$parentId}
{/if}

<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{translate key="article.pdf.pageTitle" pdfTitle=$pdfTitle}</title>
	<meta name="description" content="{$metaSearchDescription|escape}" />
	<meta name="keywords" content="{$metaSearchKeywords|escape}" />
	<meta name="generator" content="{$applicationName} {$currentVersionString|escape}" />
	{$metaCustomHeaders}
	{if $displayFavicon}<link rel="icon" href="{$faviconDir}/{$displayFavicon.uploadName|escape:"url"}" type="{$displayFavicon.mimeType|escape}" />{/if}

    {load_stylesheet context="frontend" stylesheets=$stylesheets}
</head>
<body class="pkp_page_{$requestedPage|escape} pkp_op_{$requestedOp|escape}">

	{* Header wrapper *}
	<header class="header_view_pdf">

		<a href="{$parentUrl}" class="return">
			<span class="pkp_screen_reader">
				{if $parent instanceOf Issue}
					{translate key="issue.return"}
				{else}
					{translate key="article.return"}
				{/if}
			</span>
		</a>

		<a href="{$parentUrl}" class="title">
			{$pdfTitle}
		</a>

		<a href="{url page="issue" op="download" path=$parentId|to_array:$galley->getBestGalleyId($currentJournal)}" class="download" download>
			<span class="label">
				{translate key="common.download"}
			</span>
			<span class="pkp_screen_reader">
				{translate key="common.downloadPdf"}
			</span>
		</a>

	</header>

	<iframe class="pdf" src="{url page="issue" op="download" path=$parentId|to_array:$galley->getBestGalleyId($currentJournal)}"></iframe>

	{call_hook name="Templates::Common::Footer::PageFooter"}

</body>
</html>
