{**
 * @file plugins/importexport/datacite/templates/index.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{strip}
{include file="common/header.tpl" pageTitle="plugins.importexport.datacite.displayName"}
{/strip}

{if !empty($configurationErrors)}
	{assign var="allowExport" value=false}
{else}
	{assign var="allowExport" value=true}
{/if}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
	{rdelim});
</script>
<div id="importExportTabs">
	<ul>
		<li><a href="#settings-tab">{translate key="plugins.importexport.common.settings"}</a></li>
		{if $allowExport}
			{if $exportArticles}
				<li><a href="#exportSubmissions-tab">{translate key="plugins.importexport.common.export.articles"}</a></li>
			{/if}
			{if $exportIssues}
				<li><a href="#exportIssues-tab">{translate key="plugins.importexport.common.export.issues"}</a></li>
			{/if}
			{if $exportRepresentations}
				<li><a href="#exportRepresentations-tab">{translate key="plugins.importexport.common.export.representations"}</a></li>
			{/if}
		{/if}
	</ul>
	<div id="settings-tab">
		{if !$allowExport}
			<div class="pkp_notification" id="dataciteConfigurationErrors">
				{foreach from=$configurationErrors item=configurationError}
					{if $configurationError == $smarty.const.DOI_EXPORT_CONFIG_ERROR_DOIPREFIX}
						{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=dataciteConfigurationErrors notificationStyleClass="notifyWarning" notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.DOIsNotAvailable"|translate}
					{elseif $configurationError == $smarty.const.EXPORT_CONFIG_ERROR_SETTINGS}
						{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=dataciteConfigurationErrors notificationStyleClass="notifyWarning" notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.pluginNotConfigured"|translate}
					{/if}
				{/foreach}
				{if !$exportArticles && !$exportIssues && !$exportRepresentations}
					{include file="controllers/notification/inPlaceNotificationContent.tpl" notificationId=dataciteConfigurationErrors notificationStyleClass="notifyWarning" notificationTitle="plugins.importexport.common.missingRequirements"|translate notificationContents="plugins.importexport.common.error.noDOIContentObjects"|translate}
				{/if}
			</div>
		{/if}

		{capture assign=dataciteSettingsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.plugins.settingsPluginGridHandler" op="manage" plugin="DataciteExportPlugin" category="importexport" verb="index" escape=false}{/capture}
		{load_url_in_div id="dataciteSettingsGridContainer" url=$dataciteSettingsGridUrl}
	</div>

	{if $allowExport}
		{if $exportArticles}
			<div id="exportSubmissions-tab">
				<script type="text/javascript">
					$(function() {ldelim}
						// Attach the form handler.
						$('#exportSubmissionXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
					{rdelim});
				</script>
				<form id="exportSubmissionXmlForm" class="pkp_form" action="{plugin_url path="exportSubmissions"}" method="post">
					<input type="hidden" name="tab" value="exportSubmissions-tab" />
					{fbvFormArea id="submissionsXmlForm"}
						{capture assign=submissionsListGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.pubIds.PubIdExportSubmissionsListGridHandler" op="fetchGrid" plugin="datacite" category="importexport" escape=false}{/capture}
						{load_url_in_div id="submissionsListGridContainer" url=$submissionsListGridUrl}
						{fbvFormSection list="true"}
							{fbvElement type="checkbox" id="validation" label="plugins.importexport.common.validation" checked=$validation|default:true}
						{/fbvFormSection}
						{if !empty($actionNames)}
							{fbvFormSection}
							<ul class="export_actions">
								{foreach from=$actionNames key=action item=actionName}
									<li class="export_action">
										{fbvElement type="submit" label="$actionName" id="$action" name="$action" value="1" class="$action" translate=false inline=true}
									</li>
								{/foreach}
							</ul>
							{/fbvFormSection}
						{/if}
					{/fbvFormArea}
				</form>
			</div>
		{/if}
		{if $exportIssues}
			<div id="exportIssues-tab">
				<script type="text/javascript">
					$(function() {ldelim}
						// Attach the form handler.
						$('#exportIssueXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
					{rdelim});
				</script>
				<form id="exportIssueXmlForm" class="pkp_form" action="{plugin_url path="exportIssues"}" method="post">
					<input type="hidden" name="tab" value="exportIssues-tab" />
					{fbvFormArea id="issuesXmlForm"}
						{capture assign=issuesListGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.pubIds.PubIdExportIssuesListGridHandler" op="fetchGrid" plugin="datacite" category="importexport" escape=false}{/capture}
						{load_url_in_div id="issuesListGridContainer" url=$issuesListGridUrl}
						{fbvFormSection list="true"}
							{fbvElement type="checkbox" id="validation" label="plugins.importexport.common.validation" checked=$validation|default:true}
						{/fbvFormSection}
						{if !empty($actionNames)}
							{fbvFormSection}
							<ul class="export_actions">
								{foreach from=$actionNames key=action item=actionName}
									<li class="export_action">
										{fbvElement type="submit" label="$actionName" id="$action" name="$action" value="1" class="$action" translate=false inline=true}
									</li>
								{/foreach}
							</ul>
							{/fbvFormSection}
						{/if}
					{/fbvFormArea}
				</form>
			</div>
		{/if}
		{if $exportRepresentations}
			<div id="exportRepresentations-tab">
				<script type="text/javascript">
					$(function() {ldelim}
						// Attach the form handler.
						$('#exportRepresentationXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
					{rdelim});
				</script>
				<form id="exportRepresentationXmlForm" class="pkp_form" action="{plugin_url path="exportRepresentations"}" method="post">
					<input type="hidden" name="tab" value="exportRepresentations-tab" />
					{fbvFormArea id="representationsXmlForm"}
						{capture assign=representationsListGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.pubIds.PubIdExportRepresentationsListGridHandler" op="fetchGrid" plugin="datacite" category="importexport" escape=false}{/capture}
						{load_url_in_div id="representationsListGridContainer" url=$representationsListGridUrl}
						{fbvFormSection list="true"}
							{fbvElement type="checkbox" id="validation" label="plugins.importexport.common.validation" checked=$validation|default:true}
						{/fbvFormSection}
						{if !empty($actionNames)}
							{fbvFormSection}
							<ul class="export_actions">
								{foreach from=$actionNames key=action item=actionName}
									<li class="export_action">
										{fbvElement type="submit" label="$actionName" id="$action" name="$action" value="1" class="$action" translate=false inline=true}
									</li>
								{/foreach}
							</ul>
							{/fbvFormSection}
						{/if}
					{/fbvFormArea}
				</form>
			</div>
		{/if}
	{/if}
</div>

{include file="common/footer.tpl"}
