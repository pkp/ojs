{extends file="layouts/backend.tpl"}
{block name="page"}
	<!-- Add page content here -->
	<h1 class="app__pageHeading">
        {translate key="plugins.pubIds.doi.manager.displayName"}
	</h1>

	<tabs :track-history="true">
        {if $displayArticlesTab}
			<!-- TODO: Localize label -->
			<tab id="article-doi-management" label="Articles">
				<h1>Articles</h1>
				<doi-list-panel
						v-bind="components.submissionDoiListPanel"
						@set="set"
				/>
			</tab>
        {/if}
        {if $displayIssuesTab}
			<!-- TODO: Localize label -->
			<tab id="issue-doi-management" label="Issues">
				<h1>Issues</h1>
				<doi-list-panel
						v-bind="components.issueDoiListPanel"
						@set="set"
				/>
			</tab>
        {/if}

		<tab id="doi-settings" label={translate key="navigation.settings"}>
			<tabs :is-side-tabs="true" :track-history="true">
				<tab id="doisSetup" label="{translate key="manager.setup.dois.setup"}">
					<pkp-form
							v-bind="components.{PKP\components\forms\context\PKPDoiSetupSettingsForm::FORM_DOI_SETUP_SETTINGS}"
							@set="set"
					/>
				</tab>
				<tab id="doisRegistration" label="{translate key="manager.setup.dois.registration"}">
					<pkp-form
							v-bind="components.{PKP\components\forms\context\PKPDoiRegistrationSettingsForm::FORM_DOI_REGISTRATION_SETTINGS}"
							@set="set"
					/>
				</tab>
			</tabs>
		</tab>

        {call_hook name="Template::doiManagement"}
	</tabs>
{/block}
