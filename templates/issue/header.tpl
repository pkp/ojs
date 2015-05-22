{**
 * templates/issue/header.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header for issue pages.
 *}
{strip}
{if $issue && !$issue->getPublished()}
	{translate|assign:"previewText" key="editor.issues.preview"}
	{assign var="pageTitleTranslated" value="$issueHeadingTitle $previewText"}
{else}
	{assign var="pageTitleTranslated" value=$issueHeadingTitle}
{/if}
{if $issue && $issue->getShowTitle() && $issue->getLocalizedTitle() && ($issueHeadingTitle != $issue->getLocalizedTitle())}
	{* If the title is specified and should be displayed then show it as a subheading *}
	{assign var="pageSubtitleTranslated" value=$issue->getLocalizedTitle()}
{/if}
{include file="common/header.tpl"}
{/strip}
