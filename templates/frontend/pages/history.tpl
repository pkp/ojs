{**
 * templates/frontend/pages/history.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * About the Journal => History
 *
 *}
{strip}
{assign var="pageTitle" value="about.history"}
{include file="frontend/components/header.tpl"}
{/strip}

{$history}

{include file="common/frontend/footer.tpl"}
