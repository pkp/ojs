{**
 * systemConfigUpdated.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display confirmation of successful configuration update.
 * If necessary, will display new config file contents if config file could not be written.
 *
 * $Id$
 *}

{assign var="pageTitle" value="admin.systemConfiguration"}
{assign var="currentUrl" value="$pageUrl/admin/editSystemConfig"}
{include file="common/header.tpl"}

{if $writeConfigFailed}
{translate key="admin.overwriteConfigFileInstructions"}
{elseif $displayConfigContents}
{translate key="admin.displayConfigFileInstructions"}
{else}
{translate key="admin.configFileUpdatedInstructions"}
<br /><br />
{/if}

{if $writeConfigFailed || $displayConfigContents}
<br /><br />
<form>
<div class="form">
{translate key="admin.contentsOfConfigFile"}:<br />
<textarea name="config" cols="80" rows="20" class="textAreaFixed">{$configFileContents|escape}</textarea>
</div>
</form>
<br />
{/if}

&#187; <a href="{$pageUrl}/admin/systemInfo">{translate key="admin.systemInformation"}</a>

{include file="common/footer.tpl"}
