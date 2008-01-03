{**
 * thesisForm.tpl
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Thesis abstract form under plugin management.
 *
 * $Id$
 *}
{assign var="pageCrumbTitle" value="$thesisTitle"}
{if $thesisId}
	{assign var="pageTitle" value="plugins.generic.thesis.manager.edit"}
{else}
	{assign var="pageTitle" value="plugins.generic.thesis.manager.create"}
{/if}
{include file="common/header.tpl"}

<br/>

<form method="post" action="{plugin_url path="update"}">
{if $thesisId}
<input type="hidden" name="thesisId" value="{$thesisId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="status" required="true" key="plugins.generic.thesis.manager.form.status"}</td>
	<td width="80%" class="value"><select name="status" id="status" class="selectMenu" />{html_options options=$validStatus selected=$status}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="degree" required="true" key="plugins.generic.thesis.manager.form.degree"}</td>
	<td class="value"><select name="degree" id="degree" class="selectMenu" />{html_options options=$validDegrees selected=$degree}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="degreeName" key="plugins.generic.thesis.manager.form.degreeName"}</td>
	<td class="value"><input type="text" name="degreeName" value="{$degreeName|escape}" size="40" id="degreeName" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.degreeNameInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="department" required="true" key="plugins.generic.thesis.manager.form.department"}</td>
	<td class="value"><input type="text" name="department" value="{$department|escape}" size="40" id="department" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="university" required="true" key="plugins.generic.thesis.manager.form.university"}</td>
	<td class="value"><input type="text" name="university" value="{$university|escape}" size="40" id="university" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateApproved" required="true" key="plugins.generic.thesis.manager.form.dateApproved"}</td>
	<td class="value">{html_select_date prefix="dateApproved" all_extra="class=\"selectMenu\"" start_year="$yearOffsetPast" time="$dateApproved"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="title" required="true" key="plugins.generic.thesis.manager.form.title"}</td>
	<td class="value"><input type="text" name="title" value="{$title|escape}" size="40" id="title" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="url" key="plugins.generic.thesis.manager.form.url"}</td>
	<td class="value"><input type="text" name="url" value="{$url|escape}" size="40" id="url" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.urlInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="abstract" required="true" key="plugins.generic.thesis.manager.form.abstract"}</td>
	<td class="value"><textarea name="abstract" id="abstract" cols="40" rows="6" class="textArea" />{$abstract|escape}</textarea>
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.abstractInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="comment" key="plugins.generic.thesis.manager.form.comment"}</td>
	<td class="value"><textarea name="comment" id="comment" cols="40" rows="6" class="textArea" />{$comment|escape}</textarea></td>
</tr>
</table>

<div class="separator"></div>

<h3>{translate key="plugins.generic.thesis.manager.form.author"}</h3>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="studentFirstName" required="true" key="plugins.generic.thesis.manager.form.studentFirstName"}</td>
	<td width="80%" class="value"><input type="text" name="studentFirstName" value="{$studentFirstName|escape}" size="40" id="studentFirstName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentMiddleName" key="plugins.generic.thesis.manager.form.studentMiddleName"}</td>
	<td class="value"><input type="text" name="studentMiddleName" value="{$studentMiddleName|escape}" size="40" id="studentMiddleName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentLastName" required="true" key="plugins.generic.thesis.manager.form.studentLastName"}</td>
	<td class="value"><input type="text" name="studentLastName" value="{$studentLastName|escape}" size="40" id="studentLastName" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentEmail" required="true" key="plugins.generic.thesis.manager.form.studentEmail"}</td>
	<td class="value"><input type="text" name="studentEmail" value="{$studentEmail|escape}" size="40" id="studentEmail" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentEmailPublish" key="plugins.generic.thesis.manager.form.studentEmailPublish"}</td>
	<td class="value"><input type="checkbox" name="studentEmailPublish" id="studentEmailPublish" value="1"{if $studentEmailPublish} checked="checked"{/if} />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.studentEmailPublishInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentBio" key="plugins.generic.thesis.manager.form.studentBio"}</td>
	<td class="value"><textarea name="studentBio" id="studentBio" cols="40" rows="6" class="textArea" />{$studentBio|escape}</textarea>
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.studentBioInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorFirstName" required="true" key="plugins.generic.thesis.manager.form.supervisorFirstName"}</td>
	<td class="value"><input type="text" name="supervisorFirstName" value="{$supervisorFirstName|escape}" size="40" id="supervisorFirstName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorMiddleName" key="plugins.generic.thesis.manager.form.supervisorMiddleName"}</td>
	<td class="value"><input type="text" name="supervisorMiddleName" value="{$supervisorMiddleName|escape}" size="40" id="supervisorMiddleName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorLastName" required="true" key="plugins.generic.thesis.manager.form.supervisorLastName"}</td>
	<td class="value"><input type="text" name="supervisorLastName" value="{$supervisorLastName|escape}" size="40" id="supervisorLastName" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorEmail" required="true" key="plugins.generic.thesis.manager.form.supervisorEmail"}</td>
	<td class="value"><input type="text" name="supervisorEmail" value="{$supervisorEmail|escape}" size="40" id="supervisorEmail" maxlength="90" class="textField" /></td>
</tr>
</table>

<div class="separator"></div>

<h3>{translate key="plugins.generic.thesis.manager.form.indexing"}</h3>
<p>{translate key="plugins.generic.thesis.manager.form.indexingDescription"}</p>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="discipline" key="plugins.generic.thesis.manager.form.discipline"}</td>
	<td width="80%" class="value"><input type="text" name="discipline" value="{$discipline|escape}" size="40" id="discipline" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaDisciplineExamples') != ''}
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{$currentJournal->getLocalizedSetting('metaDisciplineExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="subjectClass" key="plugins.generic.thesis.manager.form.subjectClass"}</td>
	<td class="value"><input type="text" name="subjectClass" value="{$subjectClass|escape}" size="40" id="subjectClass" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaSubjectClassUrl') != '' and $currentJournal->getLocalizedSetting('metaSubjectClassTitle') != ''}
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct"><a href="{$currentJournal->getLocalizedSetting('metaSubjectClassUrl')|escape}" target="_blank">{$currentJournal->getLocalizedSetting('metaSubjectClassTitle')|escape}</a></span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="keyword" key="plugins.generic.thesis.manager.form.keyword"}</td>
	<td class="value"><input type="text" name="keyword" value="{$keyword|escape}" size="40" id="keyword" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaSubjectExamples') != ''}
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{$currentJournal->getLocalizedSetting('metaSubjectExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="coverageGeo" key="plugins.generic.thesis.manager.form.coverageGeo"}</td>
	<td class="value"><input type="text" name="coverageGeo" value="{$coverageGeo|escape}" size="40" id="coverageGeo" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaCoverageGeoExamples') != ''}
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{$currentJournal->getLocalizedSetting('metaCoverageGeoExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="coverageChron" key="plugins.generic.thesis.manager.form.coverageChron"}</td>
	<td class="value"><input type="text" name="coverageChron" value="{$coverageChron|escape}" size="40" id="coverageChron" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaCoverageChronExamples') != ''}
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{$currentJournal->getLocalizedSetting('metaCoverageChronExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="coverageSample" key="plugins.generic.thesis.manager.form.coverageSample"}</td>
	<td class="value"><input type="text" name="coverageSample" value="{$coverageSample|escape}" size="40" id="coverageSample" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaCoverageResearchSampleExamples') != ''}
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{$currentJournal->getLocalizedSetting('metaCoverageResearchSampleExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="method" key="plugins.generic.thesis.manager.form.method"}</td>
	<td class="value"><input type="text" name="method" value="{$method|escape}" size="40" id="method" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaTypeExamples')}
<tr valign="top">
	<td>&nbsp;</td>
	<td><span class="instruct">{$currentJournal->getLocalizedSetting('metaTypeExamples')|escape}</span></td>
</tr>
{/if}
<tr valign="top">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="language" key="plugins.generic.thesis.manager.form.language"}</td>
	<td class="value"><input type="text" name="language" value="{$language|escape|default:en}" size="5" id="language" maxlength="10" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.manager.form.languageInstructions"}</span>
	</td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $thesisId}<input type="submit" name="createAnother" value="{translate key="plugins.generic.thesis.manager.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="history.go(-1);" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
