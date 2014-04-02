{**
 * templates/install/upgradeComplete.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display confirmation of successful upgrade.
 * If necessary, will also display new config file contents if config file could not be written.
 *
 *}
{strip}
{assign var="pageTitle" value="installer.ojsUpgrade"}
{include file="core:install/upgradeComplete.tpl"}
{/strip}
