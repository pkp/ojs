{**
 * plugins/generic/thesis/studentThesisForm.tpl
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for student thesis abstract submission.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.thesis.submit"}
{include file="common/header.tpl"}
{/strip}

<div id="description">{translate key="plugins.generic.thesis.form.introduction"}</div>

<br/>
<br/>

<form method="post" action="{url op="save"}">

{include file="common/formErrors.tpl"}
<div id="general">
<table class="data" width="100%">
{if $captchaEnabled}
<tr valign="top">
	<td class="label" valign="top">{fieldLabel name="captcha" required="true" key="common.captchaField"}</td>
	<td class="value">
		<img src="{url op="viewCaptcha" path=$captchaId}" alt="" /><br />
		<span class="instruct">{translate key="common.captchaField.description"}</span><br />
		<input name="captcha" id="captcha" value="" size="20" maxlength="32" class="textField" />
		<input type="hidden" name="captchaId" value="{$captchaId|escape:"quoted"}" />
	</td>
</tr>
{/if}
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="degree" required="true" key="plugins.generic.thesis.form.degree"}</td>
	<td width="80%" class="value"><select name="degree" id="degree" class="selectMenu" />{html_options options=$validDegrees selected=$degree}</select></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="degreeName" required="true" key="plugins.generic.thesis.form.degreeName"}</td>
	<td class="value"><input type="text" name="degreeName" value="{$degreeName|escape}" size="40" id="degreeName" maxlength="255" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.degreeNameInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="department" required="true" key="plugins.generic.thesis.form.department"}</td>
	<td class="value"><input type="text" name="department" value="{$department|escape}" size="40" id="department" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="university" required="true" key="plugins.generic.thesis.form.university"}</td>
	<td class="value"><input type="text" name="university" value="{$university|escape}" size="40" id="university" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="dateApproved" required="true" key="plugins.generic.thesis.form.dateApproved"}</td>
	<td class="value">{html_select_date prefix="dateApproved" all_extra="class=\"selectMenu\"" start_year="$yearOffsetPast" time="$dateApproved"}</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="title" required="true" key="plugins.generic.thesis.form.title"}</td>
	<td class="value"><input type="text" name="title" value="{$title|escape}" size="40" id="title" maxlength="255" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="url" key="plugins.generic.thesis.form.url"}</td>
	<td class="value"><input type="text" name="url" value="{$url|escape}" size="40" id="url" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.urlInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="abstract" required="true" key="plugins.generic.thesis.form.abstract"}</td>
	<td class="value"><textarea name="abstract" id="abstract" cols="40" rows="6" class="textArea" />{$abstract|escape}</textarea>
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.abstractInstructions"}</span>
	</td>
</tr>
{if $uploadCodeEnabled}
<tr valign="top">
	<td class="label">{fieldLabel name="uploadCode" key="plugins.generic.thesis.form.uploadCode"}</td>
	<td class="value"><input type="text" name="uploadCode" value="{$uploadCode|escape}" size="15" id="uploadCode" maxlength="24" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.uploadCodeInstructions"}</span>
	</td>
</tr>
{/if}
<tr valign="top">
	<td class="label">{fieldLabel name="comment" key="plugins.generic.thesis.form.comment"}</td>
	<td class="value"><textarea name="comment" id="comment" cols="40" rows="6" class="textArea" />{$comment|escape}</textarea>
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.commentInstructions"}</span>
	</td>
</tr>
</table>
</div>
<div class="separator"></div>
<div id="author">
<h3>{translate key="plugins.generic.thesis.form.author"}</h3>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="studentFirstName" required="true" key="plugins.generic.thesis.form.studentFirstName"}</td>
	<td width="80%" class="value"><input type="text" name="studentFirstName" value="{$studentFirstName|escape}" size="40" id="studentFirstName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentMiddleName" key="plugins.generic.thesis.form.studentMiddleName"}</td>
	<td class="value"><input type="text" name="studentMiddleName" value="{$studentMiddleName|escape}" size="40" id="studentMiddleName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentLastName" required="true" key="plugins.generic.thesis.form.studentLastName"}</td>
	<td class="value"><input type="text" name="studentLastName" value="{$studentLastName|escape}" size="40" id="studentLastName" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentEmail" required="true" key="plugins.generic.thesis.form.studentEmail"}</td>
	<td class="value"><input type="text" name="studentEmail" value="{$studentEmail|escape}" size="40" id="studentEmail" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentEmailPublish" key="plugins.generic.thesis.form.studentEmailPublish"}</td>
	<td class="value"><input type="checkbox" name="studentEmailPublish" id="studentEmailPublish" value="1"{if $studentEmailPublish} checked="checked"{/if} />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.studentEmailPublishInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="studentBio" key="plugins.generic.thesis.form.studentBio"}</td>
	<td class="value"><textarea name="studentBio" id="studentBio" cols="40" rows="6" class="textArea" />{$studentBio|escape}</textarea>
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.studentBioInstructions"}</span>
	</td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorFirstName" required="true" key="plugins.generic.thesis.form.supervisorFirstName"}</td>
	<td class="value"><input type="text" name="supervisorFirstName" value="{$supervisorFirstName|escape}" size="40" id="supervisorFirstName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorMiddleName" key="plugins.generic.thesis.form.supervisorMiddleName"}</td>
	<td class="value"><input type="text" name="supervisorMiddleName" value="{$supervisorMiddleName|escape}" size="40" id="supervisorMiddleName" maxlength="40" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorLastName" required="true" key="plugins.generic.thesis.form.supervisorLastName"}</td>
	<td class="value"><input type="text" name="supervisorLastName" value="{$supervisorLastName|escape}" size="40" id="supervisorLastName" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="supervisorEmail" required="true" key="plugins.generic.thesis.form.supervisorEmail"}</td>
	<td class="value"><input type="text" name="supervisorEmail" value="{$supervisorEmail|escape}" size="40" id="supervisorEmail" maxlength="90" class="textField" /></td>
</tr>
</table>
</div>
<div class="separator"></div>
<div id="indexing">
<h3>{translate key="plugins.generic.thesis.form.indexing"}</h3>
<p>{translate key="plugins.generic.thesis.form.indexingDescription"}</p>

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="discipline" key="plugins.generic.thesis.form.discipline"}</td>
	<td width="80%" class="value"><input type="text" name="discipline" value="{$discipline|escape}" size="40" id="discipline" maxlength="255" class="textField" /></td>
</tr>
{if $currentJournal->getLocalizedSetting('metaDisciplineExamples')}
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
	<td class="label">{fieldLabel name="subjectClass" key="plugins.generic.thesis.form.subjectClass"}</td>
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
	<td class="label">{fieldLabel name="keyword" key="plugins.generic.thesis.form.keyword"}</td>
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
	<td class="label">{fieldLabel name="coverageGeo" key="plugins.generic.thesis.form.coverageGeo"}</td>
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
	<td class="label">{fieldLabel name="coverageChron" key="plugins.generic.thesis.form.coverageChron"}</td>
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
	<td class="label">{fieldLabel name="coverageSample" key="plugins.generic.thesis.form.coverageSample"}</td>
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
	<td class="label">{fieldLabel name="method" key="plugins.generic.thesis.form.method"}</td>
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
	<td class="label">{fieldLabel name="language" key="plugins.generic.thesis.form.language"}</td>
	<td class="value"><input type="text" name="language" value="{$language|escape|default:en}" size="5" id="language" maxlength="10" class="textField" />
		<br />
		<span class="instruct">{translate key="plugins.generic.thesis.form.languageInstructions"}</span>
	</td>
</tr>
</table>
</div>
<p><input type="submit" value="{translate key="plugins.generic.thesis.submitButton"}" class="button defaultButton" /><input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="thesis" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
