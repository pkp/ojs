{**
 * templates/install/installComplete.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display confirmation of successful installation.
 * If necessary, will also display new config file contents if config file could not be written.
 *}
{strip}
{assign var="pageTitle" value="installer.ojsInstallation"}
{include file="core:install/installComplete.tpl"}
{/strip}
