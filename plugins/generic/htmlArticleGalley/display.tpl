{**
 * plugins/generic/htmlArticleGalley/display.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a HTML galley.
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset|escape}" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{translate key="article.pageTitle" title=$title}</title>

	{load_header context="frontend" headers=$headers}
	{load_stylesheet context="frontend" stylesheets=$stylesheets}
	{load_script context="frontend" scripts=$scripts}
</head>
<body class="pkp_page_{$requestedPage|escape} pkp_op_{$requestedOp|escape}">

	{* Header wrapper *}
	<header class="header_view">

		<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)}" class="return">
			<span class="pkp_screen_reader">
				{translate key="article.return"}
			</span>
		</a>

		<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal)}" class="title">
			{$article->getLocalizedTitle()|escape}
		</a>
	</header>

	<div id="htmlContainer">
		<iframe name="htmlFrame" src="{url page="article" op="download" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal) inline=true}" allowfullscreen webkitallowfullscreen></iframe>
	</div>
	{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>
