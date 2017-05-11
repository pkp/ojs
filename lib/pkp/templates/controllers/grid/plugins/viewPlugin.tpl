{**
 * controllers/grid/plugins/viewPlugin.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief View a plugin gallery plugin's details.
 *
 * @uses $plugin Plugin The plugin being displayed
 * @uses $statusKey string The locale string describing the plugin's install
 *  status
 * @uses $statusClass string A class corresponding to the status
 *}
<div class="pkp_plugin_details">

	<div class="status {$statusClass|escape}">
		<div class="pkp_screen_reader">
			{translate key="manager.plugins.pluginGallery.latestCompatible"}
		</div>

		{if $statusClass == 'older' || $statusClass == 'notinstalled'}
			<div class="action_button">
				{include file="linkAction/linkAction.tpl" action=$installAction contextId="pluginGallery"}
			</div>
		{else}
			<div class="status_notice">
				{translate key=$statusKey}
			</div>
		{/if}

		{if $statusClass != 'incompatible'}

			<ul class="certifications">
				{foreach from=$plugin->getReleaseCertifications() item=certification}
					<li class="certification_{$certification|escape}">
						<span class="label">
							{translate key="manager.plugins.pluginGallery.certifications.$certification"}
						</span>
						<span class="description">
							{translate key="manager.plugins.pluginGallery.certifications.$certification.description"}
						</span>
					</li>
				{/foreach}
			</ul>

			<div class="release">
				{translate key="manager.plugins.pluginGallery.version" version=$plugin->getVersion()|escape date=$plugin->getDate()|date_format:$dateFormatShort}
			</div>
			<div class="release_description">
				{$plugin->getLocalizedReleaseDescription()|strip_unsafe_html}
			</div>
		{/if}
	</div>

	<h4 class="pkp_screen_reader">
		{translate key="manager.plugins.pluginGallery.summary"}
	</h4>

	<div class="maintainer">
		<div class="author">
			{if $plugin->getContactEmail()}
				<a href="mailto:{$plugin->getContactEmail()|escape}">
					{$plugin->getContactName()|escape}
				</a>
			{else}
				{$plugin->getContactName()|escape}
			{/if}
		</div>
		<div class="institution">
			{$plugin->getContactInstitutionName()|escape}
		</div>
	</div>

	<div class="url">
		<a href="{$plugin->getHomepage()|escape}" target="_blank">{$plugin->getHomepage()|escape}</a>
	</div>

	<div class="description">
		{include file="controllers/revealMore.tpl" content=$plugin->getLocalizedDescription()|strip_unsafe_html}
	</div>

	{if $plugin->getLocalizedInstallationInstructions()}
		<div class="installation">
			{include file="controllers/revealMore.tpl" content=$plugin->getLocalizedInstallationInstructions()|strip_unsafe_html}
		</div>
	{/if}
</div>
