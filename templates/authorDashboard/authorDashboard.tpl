{**
 * templates/authorDashboard/authorDashboard.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Display the author dashboard.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<div class="pkpWorkflow">
		<pkp-header class="pkpWorkflow__header">
			<h1 class="pkpWorkflow__identification">
				<span class="pkpWorkflow__identificationId">{{ submission.id }}</span>
				<span class="pkpWorkflow__identificationDivider">/</span>
				<span class="pkpWorkflow__identificationAuthor">
					{{ currentPublication.authorsStringShort }}
				</span>
				<span class="pkpWorkflow__identificationDivider">/</span>
				<span class="pkpWorkflow__identificationTitle">
					{{ localizeSubmission(currentPublication.title, currentPublication.locale) }}
				</span>
			</h1>
			<template slot="actions">
				<pkp-button
					v-if="uploadFileUrl"
					ref="uploadFileButton"
					@click="openFileUpload"
				>
					{translate key="common.upload.addFile"}
				</pkp-button>
				<pkp-button
					@click="openLibrary"
				>
					{translate key="editor.submissionLibrary"}
				</pkp-button>
			</template>
		</pkp-header>
		<tabs :track-history="true">
			<tab id="workflow" label="{translate key="manager.workflow"}">

				{include file="controllers/notification/inPlaceNotification.tpl" notificationId="authorDashboardNotification" requestOptions=$authorDashboardNotificationRequestOptions}

				{assign var=selectedTabIndex value=0}
				{foreach from=$workflowStages item=stage}
					{if $stage.id < $submission->getStageId()}
						{assign var=selectedTabIndex value=$selectedTabIndex+1}
					{/if}
				{/foreach}

				<script type="text/javascript">
					// Attach the JS file tab handler.
					$(function() {ldelim}
						$('#stageTabs').pkpHandler(
							'$.pkp.controllers.tab.workflow.WorkflowTabHandler',
							{ldelim}
								selected: {$selectedTabIndex},
								emptyLastTab: true
							{rdelim}
						);
					{rdelim});
				</script>
				<div id="stageTabs" class="pkp_controllers_tab">
					<ul>
						{foreach from=$workflowStages item=stage}
							<li class="pkp_workflow_{$stage.path} stageId{$stage.id}{if $stage.statusKey} initiated{/if}">
								<a name="stage-{$stage.path}" class="{$stage.path} stageId{$stage.id}" href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.authorDashboard.AuthorDashboardTabHandler" op="fetchTab" submissionId=$submission->getId() stageId=$stage.id escape=false}">
									{translate key=$stage.translationKey}
									{if $stage.statusKey}
										<span class="pkp_screen_reader">
											{translate key=$stage.statusKey}
										</span>
									{/if}
								</a>
							</li>
						{/foreach}
					</ul>
				</div>
			</tab>
			<tab id="publication" label="{translate key="submission.publication"}">
				<div class="pkpPublication" ref="publication" aria-live="polite">
					<pkp-header class="pkpPublication__header" :is-one-line="false">
						<span class="pkpPublication__status">
							<strong>{{ statusLabel }}</strong>
							<span v-if="workingPublication.status === getConstant('STATUS_QUEUED') && workingPublication.id === currentPublication.id" class="pkpPublication__statusUnpublished">{translate key="publication.status.unscheduled"}</span>
							<span v-else-if="workingPublication.status === getConstant('STATUS_SCHEDULED')">{translate key="publication.status.scheduled"}</span>
							<span v-else-if="workingPublication.status === getConstant('STATUS_PUBLISHED')" class="pkpPublication__statusPublished">{translate key="publication.status.published"}</span>
							<span v-else class="pkpPublication__statusUnpublished">{translate key="publication.status.unpublished"}</span>
						</span>
							<span v-if="publicationList.length > 1" class="pkpPublication__version">
								<strong tabindex="0">{{ versionLabel }}</strong> {{ workingPublication.id }}
								<dropdown
									class="pkpPublication__versions"
									label="{translate key="publication.version.all"}"
									:is-link="true"
									submenu-label="{translate key="common.submenu"}"
								>
									<ul>
										<li v-for="publication in publicationList" :key="publication.id">
											<button
												class="pkpDropdown__action"
												:disabled="publication.id === workingPublication.id"
												@click="setWorkingPublicationById(publication.id)"
											>
												{{ publication.id }} /
												<template v-if="publication.status === getConstant('STATUS_QUEUED') && publication.id === currentPublication.id">{translate key="publication.status.unscheduled"}</template>
												<template v-else-if="publication.status === getConstant('STATUS_SCHEDULED')">{translate key="publication.status.scheduled"}</template>
												<template v-else-if="publication.status === getConstant('STATUS_PUBLISHED')">{translate key="publication.status.published"}</template>
												<template v-else>{translate key="publication.status.unpublished"}</template>
											</button>
										</li>
									</ul>
								</dropdown>
							</span>
					</pkp-header>
					<div
						v-if="workingPublication.status === getConstant('STATUS_PUBLISHED')"
						class="pkpPublication__versionPublished"
					>
						{translate key="publication.editDisabled"}
					</div>
					<tabs :is-side-tabs="true" :track-history="true" class="pkpPublication__tabs" :label="currentPublicationTabsLabel">
						<tab id="titleAbstract" label="{translate key="publication.titleAbstract"}">
							<pkp-form v-bind="components.{$smarty.const.FORM_TITLE_ABSTRACT}" @set="set" />
						</tab>
						<tab id="contributors" label="{translate key="publication.contributors"}">
							<div id="contributors-grid" ref="contributors">
								<spinner></spinner>
							</div>
						</tab>
						{if $metadataEnabled}
							<tab id="metadata" label="{translate key="submission.informationCenter.metadata"}">
								<pkp-form v-bind="components.{$smarty.const.FORM_METADATA}" @set="set" />
							</tab>
						{/if}
						<tab v-if="supportsReferences" id="citations" label="{translate key="submission.citations"}">
							<pkp-form v-bind="components.{$smarty.const.FORM_CITATIONS}" @set="set" />
						</tab>
						{if $canAccessProductionStage}
							<tab id="galleys" label="{translate key="submission.layout.galleys"}">
								<div id="representations-grid" ref="representations">
									<spinner></spinner>
								</div>
							</tab>
						{/if}
						{call_hook name="Template::Workflow::Publication"}
					</tabs>
					<span class="pkpPublication__mask" :class="publicationMaskClasses">
						<spinner></spinner>
					</span>
				</div>
			</tab>
			{call_hook name="Template::Workflow"}
		</tabs>
	</div>
{/block}
