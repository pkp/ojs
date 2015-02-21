{**
 * plugins/generic/dataverse/templates/settingsForm.tpl
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dataverse plugin settings
 *
 *}
{strip}
	{assign var="pageTitle" value="plugins.generic.dataverse.displayName"}
	{include file="common/header.tpl"}
{/strip}
<ul class="menu">
	<li><a href="{plugin_url path="connect"}">{translate key="plugins.generic.dataverse.settings.connect"}</a></li>
	<li><a href="{plugin_url path="select"}">{translate key="plugins.generic.dataverse.settings.selectDataverse"}</a></li>	
	<li class="current"><a href="{plugin_url path="settings"}">{translate key="plugins.generic.dataverse.settings"}</a></li>
</ul>

<div id="dataverseSettings">

	<form method="post" action="{plugin_url path="settings"}" enctype="multipart/form-data">
		{include file="common/formErrors.tpl"}

		{** Configure data policies *}
		<h3>{translate key="plugins.generic.dataverse.settings.dataPolicies"}</h3>
		<p>{translate key="plugins.generic.dataverse.settings.dataPoliciesDescription"}</p>

		<h4>{translate key="plugins.generic.dataverse.settings.dataAvailabilityPolicy"}</h4>
		<p>{translate key="plugins.generic.dataverse.settings.dataAvailabilityPolicyDescription"}</p>
		<textarea name="dataAvailability" id="dataAvailability" rows="12" cols="80" class="textArea richContent">{$dataAvailability|escape}</textarea>
		
		<h4>{translate key="about.sectionPolicies"}</h4>
		<p>{translate key="plugins.generic.dataverse.settings.sectionPoliciesDescription"}</p>
		<ul>
			{foreach from=$sections item=section}
				<li><a href="{url op='editSection' path=$section->getId()}" target="_blank">{$section->getLocalizedTitle()}</a></li>
			{/foreach}
		</ul>
		
		<h4>{translate key="about.authorGuidelines"}</h4>
		{url|assign:"authorGuidelinesUrl" page="manager" op="setup" path="3" anchor='authorGuidelinesInfo'}
		<p>{translate key="plugins.generic.dataverse.settings.authorGuidelinesDescription" authorGuidelinesUrl="$authorGuidelinesUrl"}</p>
		<div id="authorGuidelinesWrapper">
			{include file="controllers/extrasOnDemand.tpl"
				id="authorGuidelinesExtras"
				widgetWrapper="#authorGuidelinesWrapper"
				moreDetailsText="plugins.generic.dataverse.settings.default.authorGuidelines.extras"
				moreDetailsLabel="plugins.generic.dataverse.settings.default.authorGuidelines.extras.label"
				extraContent=$authorGuidelinesContent}			
		</div>
		
		<h4>{translate key="about.submissionPreparationChecklist"}</h4>
		{url|assign:"checklistUrl" page="manager" op='setup' path='3' anchor='submissionPreparationChecklist'}
		<p>{translate key="plugins.generic.dataverse.settings.checklistDescription" checklistUrl="$checklistUrl"}</p>
		<div id="checklistWrapper">
			{include file="controllers/extrasOnDemand.tpl"
				id="checklistExtras"
				widgetWrapper="#checklistWrapper"
				moreDetailsText="plugins.generic.dataverse.settings.default.checklist.extras"
				moreDetailsLabel="plugins.generic.dataverse.settings.default.checklist.extras.label"
				extraContent=$checklistContent}
		</div>
		
		<h4>{translate key="manager.setup.reviewPolicy"}</h4>
		{url|assign:"reviewPolicyUrl" page="manager" op='setup' path='2' anchor='peerReviewDescription'}
		<p>{translate key="plugins.generic.dataverse.settings.reviewPolicyDescription" reviewPolicyUrl="$reviewPolicyUrl"}</p>
		<div id="reviewPolicyWrapper">
			{include file="controllers/extrasOnDemand.tpl" 
							 id="reviewPolicyExtras" 
							 widgetWrapper="#reviewPolicyWrapper" 
							 moreDetailsText="plugins.generic.dataverse.settings.default.reviewPolicy.extras" 
							 moreDetailsLabel="plugins.generic.dataverse.settings.default.reviewPolicy.extras.label" 
							 extraContent=$reviewPolicyContent}
		</div>
		
		<h4>{translate key="manager.setup.reviewGuidelines"}</h4>
		{url|assign:"reviewGuidelinesUrl" page="manager" op='setup' path='2' anchor='reviewGuidelinesInfo'} 
		<p>{translate key="plugins.generic.dataverse.settings.reviewGuidelinesUrl" reviewGuidelinesUrl="$reviewGuidelinesUrl"}</p>
		<div id="reviewGuidelinesWrapper">
			{include file="controllers/extrasOnDemand.tpl" 
							 id="reviewGuidelinesExras" 
							 widgetWrapper="#reviewGuidelinesWrapper" 
							 moreDetailsText="plugins.generic.dataverse.settings.default.reviewGuidelines.extras" 
							 moreDetailsLabel="plugins.generic.dataverse.settings.default.reviewGuidelines.extras.label" 
							 extraContent=$reviewGuidelinesContent}
		</div>
		
		<h4>{translate key="manager.setup.copyeditInstructions"}</h4>
		{url|assign:"copyeditInstructionsUrl" page="manager" op='setup' path='4' anchor='copyeditInstructionsSection'}
		<p>{translate key="plugins.generic.dataverse.settings.copyeditInstructionsUrl" copyeditInstructionsUrl="$copyeditInstructionsUrl"}</p>
		<div id="copyeditWrapper">
			{include file="controllers/extrasOnDemand.tpl" 
							 id="copyeditInstructionsExtras" 
							 widgetWrapper="#copyeditInstructionsWrapper" 
							 moreDetailsText="plugins.generic.dataverse.settings.default.copyeditInstructions.extras" 
							 moreDetailsLabel="plugins.generic.dataverse.settings.default.copyeditInstructions.extras.label" 
							 extraContent=$copyeditInstructionsContent}
		</div>
		
		<div class="separator"></div>
		
		{** Configure terms of use *}		 

		<h3>{translate key="plugins.generic.dataverse.settings.termsOfUse"}</h3>
		<div>
			<p>{translate key="plugins.generic.dataverse.settings.termsOfUseDescription"}</p>
			<input type="radio" name="fetchTermsOfUse" id="fetchTermsOfUse-true"	value="1" {if $fetchTermsOfUse}checked="checked" {/if} /> {translate key="plugins.generic.dataverse.settings.fetchTermsOfUse"}<br/>
			<input type="radio" name="fetchTermsOfUse" id="fetchTermsOfUse-false" value="0" {if not $fetchTermsOfUse}checked="checked" {/if}/> {translate key="plugins.generic.dataverse.settings.defineTermsOfUse"}
		</div>
		<div style="margin: 1em 0">
			<textarea name="termsOfUse" id="termsOfUse" rows="5" cols="40" class="textArea richContent">{$termsOfUse|escape}</textarea>					 
		</div>
		<div class="separator"></div>		 
		
		{** Metadata settings *}
		<h3>{translate key="plugins.generic.dataverse.settings.metadata"}</h3>
		<table width="100%" class="data">
			<tr valign="top">
				<td class="label" width="20%">{fieldLabel name="citationFormat" required="true" key="plugins.generic.dataverse.settings.citationFormat"}</td>
				<td class="value">
					{html_options name="citationFormat" id="citationFormat" options=$citationFormats selected=$citationFormat}
			 </td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>{translate key="plugins.generic.dataverse.settings.citationFormatDescription"}</td>
			</tr>
			<tr valign="top">
				<td class="label">{fieldLabel name="pubIdPlugin" key="plugins.generic.dataverse.settings.pubIdType"}</td>
				<td class="value">
					{html_options name="pubIdPlugin" id="pubIdPlugin" options=$pubIdTypes selected=$pubIdPlugin}
			 </td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				{url|assign:"pubIdsUrl" page="manager" op="plugins" path="pubIds"} 
				<td>{translate key="plugins.generic.dataverse.settings.pubIdTypeDescription" pubIdsUrl="$pubIdsUrl"}</td>
			</tr>
		</table>
			
		<div class="separator"></div>					 
		{** Workflow settings *}
		<h3>{translate key="plugins.generic.dataverse.settings.workflow"}</h3>
		<table width="100%" class="data">
			<tr valign="top">
				<td class="label" width="20%">{translate key="plugins.generic.dataverse.settings.requireData"}</td>
				<td class="value">
					<input type="checkbox" name="requireData" id="requireData" value="1" {if $requireData} checked="checked"{/if}/>&nbsp;
					<label for="requireData">{translate key="plugins.generic.dataverse.settings.requireDataDescription"}</label>					
				</td>
			</tr>
			<tr valign="top">
				<td class="label"><label for="studyRelease">{translate key="plugins.generic.dataverse.settings.studyRelease"}</label></td>
				<td class="value">
					{html_options name="studyRelease" id="studyRelease" options=$studyReleaseOptions selected=$studyRelease}
				</td>
			</tr>
			
		</table>
			
		<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
		<input type="button" class="button" value="{translate key="common.cancel"}" onclick="document.location='{plugin_url path=""}';"/>
		

	</form>

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
