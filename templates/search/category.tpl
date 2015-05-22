{**
 * templates/index/category.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of journals in a category.
 *
 *}
{strip}
{assign var="pageTitleTranslated" value=$category->getLocalizedName()}
{include file="common/header.tpl"}
{/strip}

<br />

<a name="journals"></a>

{foreach from=$journals item=journal}
	{assign var="displayHomePageImage" value=$journal->getLocalizedSetting('homepageImage')}
	{assign var="displayHomePageLogo" value=$journal->getLocalizedPageHeaderLogo(true)}
	{assign var="displayPageHeaderLogo" value=$journal->getLocalizedPageHeaderLogo()}

	<div style="clear:left;">
	{if $displayHomePageImage && is_array($displayHomePageImage)}
		{assign var="altText" value=$journal->getLocalizedSetting('homepageImageAltText')}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayHomePageImage.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{elseif $displayHomePageLogo && is_array($displayHomePageLogo)}
		{assign var="altText" value=$journal->getLocalizedSetting('homeHeaderLogoImageAltText')}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayHomePageLogo.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{elseif $displayPageHeaderLogo && is_array($displayPageHeaderLogo)}
		{assign var="altText" value=$journal->getLocalizedSetting('pageHeaderLogoImageAltText')}
		<div class="homepageImage"><a href="{url journal=$journal->getPath()}" class="action"><img src="{$journalFilesPath}{$journal->getId()}/{$displayPageHeaderLogo.uploadName|escape:"url"}" {if $altText != ''}alt="{$altText|escape}"{else}alt="{translate key="common.pageHeaderLogo.altText"}"{/if} /></a></div>
	{/if}
	</div>

	<h3>{$journal->getLocalizedTitle()|escape}</h3>
	{if $journal->getLocalizedDescription()}
		<p>{$journal->getLocalizedDescription()|nl2br}</p>
	{/if}

	<p><a href="{url journal=$journal->getPath()}" class="action">{translate key="site.journalView"}</a> | <a href="{url journal=$journal->getPath() page="issue" op="current"}" class="action">{translate key="site.journalCurrent"}</a> | <a href="{url journal=$journal->getPath() page="user" op="register"}" class="action">{translate key="site.journalRegister"}</a></p>
{/foreach}

{include file="common/footer.tpl"}

