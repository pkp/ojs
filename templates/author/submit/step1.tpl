{**
 * step1.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 1 of journal author submit.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit"}
{include file="common/header.tpl"}

<div><span class="disabledText">&lt;&lt; {translate key="manager.setup.previousStep"}</span> | <a href="{$pageUrl}/author/submit/2">{translate key="manager.setup.nextStep"} &gt;&gt;</a></div>

<br />

<div class="subTitle">{translate key="manager.setup.stepNumber" step=1}: {translate key="author.submit.start"}</div>

<br />
<div> {translate key="author.submit.howToSubmit"} </div>
<br />
<span class="formRequired">(* {translate key="common.required"})</span>

<br /><br />

<form method="post" action="{$pageUrl}/author/saveSubmit/1">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">1.1 {translate key="author.submit.journalSection"}</div>
<div class="formSection">

<div class="formSectionDesc">{translate key="author.submit.journalSectionDescription"}</div>


<table class="form">
<tr>	
	<td class="formLabel"><span class="formRequired">*</span> {formLabel name="section"}{translate key="author.submit.section"}:{/formLabel}</td>
	<td class="formField"></td>
</tr>
	
</table>
</div>

<br />

<div class="formSectionTitle">1.2 {translate key="author.submit.submissionChecklist"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.submissionChecklistDescription"}</div>
<table class="form">
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="check1" value="1"{if $check1} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.check1"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="check2" value="1"{if $check2} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.check2"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="check3" value="1"{if $check3} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.check3"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="check4" value="1"{if $check4} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.check4"}</td>
</tr>
<tr>
	<td class="formFieldLeft"><input type="checkbox" name="check5" value="1"{if $check5} checked="checked"{/if} /></td>
	<td class="formLabelRightPlain">{translate key="author.submit.check5"}</td>
</tr>
</table>

</div>

<br />

<div class="formSectionTitle">1.3 {translate key="author.submit.commentsForEditor"}</div>
<div class="formSection">
<table class="form">

<tr>
	<td class="formLabel">{formLabel name="comments"}{translate key="author.submit.comments"}:{/formLabel}</td>
	<td class="formField"><textarea name="comments" rows="3" cols="60" class="textArea">{$comments|escape}</textarea></td>
</tr>

</table>
</div>

<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="common.save"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/setup'" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}
