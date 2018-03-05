{**
 * plugins/generic/lucene/templates/settingsForm.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Lucene plugin settings
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.lucene.settings.luceneSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="luceneSettings">

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#luceneSettingsForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="luceneSettingsForm" method="post" action="{plugin_url path="settings"}">
		{csrf}
		{include file="common/formErrors.tpl"}

		<h3>{translate key="plugins.generic.lucene.settings.solrServerSettings"}</h3>

		<div id="description"><p>{translate key="plugins.generic.lucene.settings.description"}</p></div>
		<div class="separator"></div>
		<br />

		<table class="data">
			<tr>
				<td class="label">{fieldLabel name="searchEndpoint" required="true" key="plugins.generic.lucene.settings.searchEndpoint"}</td>
				<td class="value"><input type="text" name="searchEndpoint" id="searchEndpoint" value="{$searchEndpoint|escape}" size="45" maxlength="255" class="textField" />
					<br />
					<span class="instruct">{translate key="plugins.generic.lucene.settings.searchEndpointInstructions"}</span>
				</td>
			</tr>
			<tr>
				<td class="label">{fieldLabel name="username" required="true" key="plugins.generic.lucene.settings.username"}</td>
				<td class="value"><input type="text" name="username" id="username" value="{$username|escape}" size="15" maxlength="25" class="textField" />
					<br />
					<span class="instruct">{translate key="plugins.generic.lucene.settings.usernameInstructions"}</span>
				</td>
			</tr>
			<tr>
				<td class="label">{fieldLabel name="password" required="true" key="plugins.generic.lucene.settings.password"}</td>
				<td class="value"><input type="password" name="password" id="password" value="{$password|escape}" size="15" maxlength="25" class="textField" />
					<br />
					<span class="instruct">{translate key="plugins.generic.lucene.settings.passwordInstructions"}</span>
				</td>
			</tr>
			<tr>
				<td class="label">{fieldLabel name="instId" required="true" key="plugins.generic.lucene.settings.instId"}</td>
				<td class="value"><input type="text" name="instId" id="instId" value="{$instId|escape}" size="15" maxlength="25" class="textField" />
					<br />
					<span class="instruct">{translate key="plugins.generic.lucene.settings.instIdInstructions"}</span>
				</td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel name="useProxySettings" key="plugins.generic.lucene.settings.useProxySettings"}</td>
				<td class="value"><input type="checkbox" name="useProxySettings" id="useProxySettings" {if $useProxySettings}checked="checked" {/if}/>
					<label for="useProxySettings">{translate key="plugins.generic.lucene.settings.useProxySettingsInstructions"}</label>
				</td>
			</tr>
		</table>

		<br />

		<h3>{translate key="plugins.generic.lucene.settings.searchFeatures"}</h3>

		<div id="featureDescription"><p>{translate key="plugins.generic.lucene.settings.featureDescription"}</p></div>
		<div class="separator"></div>
		<br />

		<table class="data">
			<tr>
				<td width="5%" class="label" align="right"><input type="checkbox" name="autosuggest" id="autosuggest" {if $autosuggest}checked="checked" {/if}/></td>
				<td class="value">
					<label for="autosuggest">{translate key="plugins.generic.lucene.settings.autosuggest"}</label><br/>
					<br/>
					<select name="autosuggestType" id="autosuggestType" class="selectMenu">
						{html_options options=$autosuggestTypes selected=$autosuggestType}
					</select>
					<p class="instruct">{translate key="plugins.generic.lucene.settings.autosuggestTypeExplanation"}</p>
				</td>
			</tr>
			<tr>
				<td class="label" align="right"><input type="checkbox" name="highlighting" id="highlighting" {if $highlighting}checked="checked" {/if}/></td>
				<td class="value">
					<label for="highlighting">{translate key="plugins.generic.lucene.settings.highlighting"}</label>
				</td>
			</tr>
			<tr>
				<script>{literal}
					$(function() {
						var $facetingCheckbox = $('#faceting');
						var facetCategoryClass = '.plugins_generic_lucene_facetCategory';

						/**
						 * Toggling the faceting checkbox will (de-)select
						 * all facet categories.
						 */
						function toggleFaceting() {
							$(facetCategoryClass).each(function(index) {
								$(this).attr('checked', $facetingCheckbox.attr('checked'));
							});
						}
						$facetingCheckbox.click(toggleFaceting);

						/**
						 * Toggling a facet category checkbox will update
						 * the state fo the faceting checkbox: One or more
						 * selected facet categories will enable faceting.
						 * Faceting will be disabled when no category is
						 * being selected.
						 */
						function checkFacetingState() {
							var facetingEnabled = false;
							$(facetCategoryClass).each(function(index) {
								if (this.checked) facetingEnabled = true;
							});
							var facetingChecked = (facetingEnabled ? 'checked' : '');
							$facetingCheckbox.attr('checked', facetingChecked);
						 }
						 $(facetCategoryClass).click(checkFacetingState);
						 checkFacetingState();
					});
				{/literal}</script>
				<td class="label" align="right"><input type="checkbox" name="faceting" id="faceting" /></td>
				<td class="value">
					<label for="faceting">{translate key="plugins.generic.lucene.settings.faceting"}</label><br/>
					<p>
						{translate key="plugins.generic.lucene.settings.facetingSelectCategory"}:<br/>
						<input type="checkbox" class="plugins_generic_lucene_facetCategory" name="facetCategoryDiscipline" id="facetCategoryDiscipline" {if $facetCategoryDiscipline}checked="checked" {/if}/>&nbsp;{translate key="plugins.generic.lucene.faceting.discipline}<br/>
						<input type="checkbox" class="plugins_generic_lucene_facetCategory" name="facetCategorySubject" id="facetCategorySubject" {if $facetCategorySubject}checked="checked" {/if}/>&nbsp;{translate key="plugins.generic.lucene.faceting.subject}<br/>
						<input type="checkbox" class="plugins_generic_lucene_facetCategory" name="facetCategoryType" id="facetCategoryType" {if $facetCategoryType}checked="checked" {/if}/>&nbsp;{translate key="plugins.generic.lucene.faceting.type}<br/>
						<input type="checkbox" class="plugins_generic_lucene_facetCategory" name="facetCategoryCoverage" id="facetCategoryCoverage" {if $facetCategoryCoverage}checked="checked" {/if}/>&nbsp;{translate key="plugins.generic.lucene.faceting.coverage}<br/>
						<input type="checkbox" class="plugins_generic_lucene_facetCategory" name="facetCategoryJournalTitle" id="facetCategoryJournalTitle" {if $facetCategoryJournalTitle}checked="checked" {/if}/>&nbsp;{translate key="plugins.generic.lucene.faceting.journalTitle}<br/>
						<input type="checkbox" class="plugins_generic_lucene_facetCategory" name="facetCategoryAuthors" id="facetCategoryAuthors" {if $facetCategoryAuthors}checked="checked" {/if}/>&nbsp;{translate key="plugins.generic.lucene.faceting.authors}<br/>
						<input type="checkbox" class="plugins_generic_lucene_facetCategory" name="facetCategoryPublicationDate" id="facetCategoryPublicationDate" {if $facetCategoryPublicationDate}checked="checked" {/if}/>&nbsp;{translate key="plugins.generic.lucene.faceting.publicationDate}
					</p>
				</td>
			</tr>
			<tr>
				<td class="label" align="right"><input type="checkbox" name="spellcheck" id="spellcheck" {if $spellcheck}checked="checked" {/if}/></td>
				<td class="value">
					<label for="spellcheck">{translate key="plugins.generic.lucene.settings.spellcheck"}</label>
				</td>
			</tr>
			<tr>
				<td class="label" align="right"><input type="checkbox" name="simdocs" id="simdocs" {if $simdocs}checked="checked" {/if}/></td>
				<td class="value">
					<label for="simdocs">{translate key="plugins.generic.lucene.settings.simdocs"}</label>
				</td>
			</tr>
			<tr>
				<td class="label" align="right"><input type="checkbox" name="customRanking" id="customRanking" {if $customRanking}checked="checked" {/if}/></td>
				<td class="value">
					<label for="customRanking">{translate key="plugins.generic.lucene.settings.customRanking"}</label>
				</td>
			</tr>
			<tr>
				<td class="label" align="right"><input {if $noMainMetric}disabled="disabled" {/if}type="checkbox" name="rankingByMetric" id="rankingByMetric" {if $rankingByMetric}checked="checked" {/if}/></td>
				<td class="value">
					<label for="rankingByMetric">
						{if $noMainMetric}
							{translate key="plugins.generic.lucene.settings.rankingByMetricDisabled"}
						{else}
							{translate key="plugins.generic.lucene.settings.rankingByMetricEnabled" metricName=$metricName}
						{/if}
					</label>
				</td>
			</tr>
			<tr>
				<td class="label" align="right"><input {if $noMainMetric}disabled="disabled" {/if}type="checkbox" name="sortingByMetric" id="sortingByMetric" {if $sortingByMetric}checked="checked" {/if}/></td>
				<td class="value">
					<label for="sortingByMetric">
						{if $noMainMetric}
							{translate key="plugins.generic.lucene.settings.sortingByMetricDisabled"}
						{else}
							{translate key="plugins.generic.lucene.settings.sortingByMetricEnabled" metricName=$metricName}
						{/if}
					</label>
				</td>
			</tr>
			<tr valign="top">
				<td class="label" align="right"><input type="checkbox" name="instantSearch" id="instantSearch" {if $instantSearch}checked="checked" {/if}/></td>
				<td class="value">
					<label for="instantSearch">{translate key="plugins.generic.lucene.settings.instantSearch"}</label>
				</td>
			</tr>
			<tr>
				<td class="label" align="right"><input type="checkbox" name="pullIndexing" id="pullIndexing" {if $pullIndexing}checked="checked" {/if}/></td>
				<td class="value">
					<label for="pullIndexing">{translate key="plugins.generic.lucene.settings.pullIndexing"}</label>
				</td>
			</tr>
		</table>

		<br/>

		<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/><input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>

		<br/>
		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		<br/>

		<a id="indexAdmin"> </a>
		<h3>{translate key="plugins.generic.lucene.settings.indexAdministration"}</h3>
		<script>
			function jumpToIndexAdminAnchor() {ldelim}
				$form = $('#luceneSettings form');
				// Return directly to the rebuild index section.
				$form.attr('action', $form.attr('action') + '#indexAdmin');
				return true;
			{rdelim}
		</script>

		<div class="separator"></div>
		<br />

		<table class="data">
			<tr>
				<td class="label">{fieldLabel name="rebuildIndex" key="plugins.generic.lucene.settings.indexRebuild"}</td>
				<td class="value">
					<select name="journalToReindex" id="journalToReindex" class="selectMenu">
						{html_options options=$journalsToReindex selected=$journalToReindex}
					</select>
					<script>
						function rebuildIndexClick() {ldelim}
							var confirmation = confirm({translate|json_encode key="plugins.generic.lucene.settings.indexRebuild.confirm"});
							if (confirmation === true) jumpToIndexAdminAnchor();
							return confirmation;
						{rdelim}
					</script>
					<input type="submit" name="rebuildIndex" value="{translate key="plugins.generic.lucene.settings.indexRebuild"}" onclick="rebuildIndexClick()" class="action" /><br/>
					<input type="submit" name="rebuildDictionaries" value="{translate key="plugins.generic.lucene.settings.dictionaryRebuild"}" onclick="rebuildIndexClick()" class="action" /><br/>
					<br/>
					{if $rebuildIndexMessages}
						<div id="rebuildIndexMessage">
							<strong>{translate key="plugins.generic.lucene.settings.indexRebuildMessages"}</strong><br/>
							{$rebuildIndexMessages|escape|replace:$smarty.const.PHP_EOL:"<br/>"|replace:" ":"&nbsp;"}
						</div>
					{else}
						<span class="instruct">{translate key="plugins.generic.lucene.settings.indexRebuildDescription"}</span><br/>
					{/if}
					<br/>
				</td>
			</tr>
			{if $rankingByMetric || $sortingByMetric}
				<tr>
					<td width="20%" class="label">{fieldLabel name="updateBoostFile" key="plugins.generic.lucene.settings.usageStatistics"}</td>
					<td class="value">
						{if $pullIndexing || !$canWriteBoostFile}
							<span class="instruct">{translate key="plugins.generic.lucene.settings.updateBoostFileDisabled"}</span>
						{else}
							<input type="submit" name="updateBoostFile" value="{translate key="plugins.generic.lucene.settings.updateBoostFile"}" onclick="jumpToIndexAdminAnchor()" class="action" /><br/>
							<br/>
							<span class="instruct">{translate key="plugins.generic.lucene.settings.updateBoostFileDescription"}</span><br/>
						{/if}<br/>
					</td>
				</tr>
			{/if}
			<tr>
				<td class="label">{fieldLabel name="startStopServer" key="plugins.generic.lucene.settings.startStopServer"}</td>
				<td class="value">
					{if $serverIsAvailable}
						{if $serverIsRunning}
							<input type="submit" name="stopServer" value="{translate key="plugins.generic.lucene.settings.stopServer"}" onclick="jumpToIndexAdminAnchor()" class="action" /><br/>
						{else}
							<input type="submit" name="startServer" value="{translate key="plugins.generic.lucene.settings.startServer"}" onclick="jumpToIndexAdminAnchor()" class="action" /><br/>
						{/if}
					{else}
						<div id="serverNotAvailable">
							{translate key="plugins.generic.lucene.settings.serverNotAvailable"}
						</div>
					{/if}
				</td>
			</tr>
		</table>
	</form>

</div>
{include file="common/footer.tpl"}
