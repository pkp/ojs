{**
 * step5.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 5 of author submit.
 *
 * $Id$
 *}

{assign var="pageTitle" value="author.submit"}
{include file="common/header.tpl"}

<div><a href="{$pageUrl}/author/submit/4">&lt;&lt; {translate key="manager.setup.previousStep"}</a> | <span class="disabledText">{translate key="manager.setup.nextStep"} &gt;&gt;</span></div>

<br />

<div class="subTitle">{translate key="manager.setup.stepNumber" step=5}: {translate key="author.submit.confirmation"}</div>

<br />

<form method="post" action="{$pageUrl}/author/saveSetup/5" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

<div class="formSectionTitle">5.1 {translate key="author.submit.confirmation"}</div>
<div class="formSection">
<div class="formSectionDesc">{translate key="author.submit.confirmationDescription"}</div>
<div class="formSubSectionTitle">{translate key="author.submit.fileSummary"}</div>


<br />

<table class="form">
<tr>
	<td></td>
	<td class="formField"><input type="submit" value="{translate key="author.submit.finishSubmission"}" class="formButton" /> <input type="button" value="{translate key="common.cancel"}" class="formButtonPlain" onclick="document.location.href='{$pageUrl}/manager/setup'" /></td>
</tr>
</table>

</form>

{include file="common/footer.tpl"}