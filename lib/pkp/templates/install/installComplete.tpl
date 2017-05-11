{**
 * installComplete.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display confirmation of successful installation.
 * If necessary, will also display new config file contents if config file could not be written.
 *}
{include file="common/header.tpl" pageTitle="installer.installApplication"}

<div class="pkp_page_content pkp_page_install_complete">
	{url|assign:"loginUrl" page="login"}
	{translate key="installer.installationComplete" loginUrl=$loginUrl}

	{if $writeConfigFailed}
		{translate key="installer.overwriteConfigFileInstructions"}

		<form class="pkp_form" action="#">
			<p>
			{translate key="installer.contentsOfConfigFile"}:<br />
			<textarea name="config" cols="80" rows="20" class="textArea" style="font-family: Courier,'Courier New',fixed-width">{$configFileContents|escape}</textarea>
			</p>
		</form>
	{/if}
</div><!-- .pkp_page_install_complete -->

{include file="common/footer.tpl"}
