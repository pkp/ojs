{**
 * installComplete.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display confirmation of successful installation.
 * If necessary, will also display new config file contents if config file could not be written.
 *
 * $Id$
 *}

{assign var="pageTitle" value="installer.ojsInstallation"}
{include file="common/header.tpl"}

{translate key="installer.installationComplete" indexUrl=$indexUrl}

{if $writeConfigFailed}
<br /><br />
{translate key="installer.overwriteConfigFileInstructions"}
<br /><br />

<form>
<div class="form">
{translate key="installer.contentsOfConfigFile"}:
<textarea name="config" cols="75" rows="20">{$configFileContents|escape}</textarea>
</div>
</form>
{/if}

{include file="common/footer.tpl"}
