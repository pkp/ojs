{**
 * templates/frontend/components/header.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Site-wide header; includes journal logo, user menu, and primary menu
 * @uses $languageToggleLocales array All supported locales (from the Immersion theme)
 *}

{strip}
	{* Determine whether a logo or title string is being displayed *}
	{assign var="showingLogo" value=true}
	{if !$displayPageHeaderLogo}
		{assign var="showingLogo" value=false}
	{/if}
	{assign var="localeShow" value=false}
	{if $languageToggleLocales && $languageToggleLocales|@count > 1}
		{assign var="localeShow" value=true}
	{/if}
{/strip}

<!DOCTYPE html>

<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
{if !$pageTitleTranslated}{capture assign="pageTitleTranslated"}{translate key=$pageTitle}{/capture}{/if}
{include file="frontend/components/headerHead.tpl"}
<body class="page_{$requestedPage|escape|default:"index"} op_{$requestedOp|escape|default:"index"}{if $showingLogo} has_site_logo{/if}{if $immersionIndexType} {$immersionIndexType|escape}{/if}"
      dir="{$currentLocaleLangDir|escape|default:"ltr"}">

<div class="cmp_skip_to_content">
	<a class="visually-hidden" href="#immersion_content_header">{translate key="navigation.skip.nav"}</a>
	<a class="visually-hidden" href="#immersion_content_main">{translate key="navigation.skip.main"}</a>
	<a class="visually-hidden" href="#immersion_content_footer">{translate key="navigation.skip.footer"}</a>
</div>

<header class="main-header"
        id="immersion_content_header"{if $immersionHomepageImage} style="background-image: url('{$publicFilesDir}/{$immersionHomepageImage.uploadName|escape:"url"}')"{/if}>
	<div class="container-fluid">
		<nav class="main-header__admin{if $localeShow} locale-enabled{else} locale-disabled{/if}">

			{* User navigation *}
			{capture assign="userMenu"}
				{load_menu name="user" id="navigationUser" ulClass="pkp_navigation_user"}
			{/capture}

			{* language toggle block *}
			{if $localeShow}
				{include file="frontend/components/languageSwitcher.tpl" id="languageNav"}
			{/if}

			{if !empty(trim($userMenu))}
				<h2 class="visually-hidden">{translate key="plugins.themes.immersion.adminMenu"}</h2>
				{$userMenu}
			{/if}

		</nav>

		{if $requestedOp == 'index'}
			<h1 class="main-header__title">
		{else}
			<div class="main-header__title">
		{/if}

		{capture assign="homeUrl"}
			{url page="index" router=$smarty.const.ROUTE_PAGE}
		{/capture}

		{if $displayPageHeaderLogo}
			<a href="{$homeUrl}" class="is_img">
				<img src="{$publicFilesDir}/{$displayPageHeaderLogo.uploadName|escape:"url"}" width="{$displayPageHeaderLogo.width|escape}" height="{$displayPageHeaderLogo.height|escape}" {if $displayPageHeaderLogo.altText != ''}alt="{$displayPageHeaderLogo.altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} />
			</a>
		{elseif $displayPageHeaderTitle}
			<a href="{$homeUrl}" class="is_text">
				<span>{$displayPageHeaderTitle|escape}</span>
			</a>
		{else}
			<a href="{$homeUrl}" class="is_img">
				<img src="{$baseUrl}/templates/images/structure/logo.png" alt="{$applicationName|escape}" title="{$applicationName|escape}" width="180" height="90" />
			</a>
		{/if}

		{if $requestedOp == 'index'}
			</h1>
		{else}
			</div>
		{/if}

			{* Primary navigation *}
			{capture assign="primaryMenu"}
				{load_menu name="primary" id="navigationPrimary" ulClass="pkp_navigation_primary"}
			{/capture}

			{if !empty(trim($primaryMenu)) || $currentContext}
			<nav class="navbar navbar-expand-sm main-header__nav">
				<button class="navbar-toggler mx-auto hamburger" data-bs-target="#main-menu" data-bs-toggle="collapse"
				        type="button"
				        aria-label="Menu" aria-controls="navigation">
					<span class="hamburger__wrapper">
		                <span class="hamburger__icon"></span>
		            </span>
				</button>
				<h2 class="visually-hidden">{translate key="plugins.themes.immersion.mainMenu"}</h2>
				<div class="collapse navbar-collapse" id="main-menu">
					{$primaryMenu}
				</div>
			</nav>
			{/if}
	</div> {* container closing tag *}
</header>
