{**
 * systemConfigUpdated.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
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

<br/>

{if $writeConfigFailed}
<p>{translate key="admin.overwriteConfigFileInstructions"}</p>
{elseif $displayConfigContents}
<p>{translate key="admin.displayConfigFileInstructions"}</p>
{else}
<p>{translate key="admin.configFileUpdatedInstructions"}</p>
{/if}

{if $writeConfigFailed || $displayConfigContents}
<form>
<h4>{translate key="admin.contentsOfConfigFile"}</h4>
<textarea name="config" cols="80" rows="20" class="textArea">{$configFileContents|escape}</textarea>
</form>
<br />
{/if}

<a class="action" href="{$pageUrl}/admin/systemInfo">{translate key="admin.systemInformation"}</a>

{include file="common/footer.tpl"}
