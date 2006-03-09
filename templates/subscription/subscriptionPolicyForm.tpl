{**
 * subscriptionPolicyForm.tpl
 *
 * Copyright (c) 2003-2006 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Setup subscription policies.
 *
 * $Id$
 *}

{assign var="pageTitle" value="manager.subscriptionPolicies"}
{assign var="pageId" value="manager.subscriptionPolicies"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="subscriptions"}">{translate key="manager.subscriptions"}</a></li>
	<li><a href="{url op="subscriptionTypes"}">{translate key="manager.subscriptionTypes"}</a></li>
	<li class="current"><a href="{url op="subscriptionPolicies"}">{translate key="manager.subscriptionPolicies"}</a></li>
</ul>

{if $subscriptionPoliciesSaved}
<br/>
{translate key="manager.subscriptionPolicies.subscriptionPoliciesSaved"}<br />
{/if}

<form method="post" action="{url op="saveSubscriptionPolicies"}">
{include file="common/formErrors.tpl"}

<h3>{translate key="manager.subscriptionPolicies.openAccessOptions"}</h3>
<p>{translate key="manager.subscriptionPolicies.openAccessOptionsDescription"}</p>

	<h4>{translate key="manager.subscriptionPolicies.delayedOpenAccess"}</h4>
	<input type="checkbox" name="enableDelayedOpenAccess" id="enableDelayedOpenAccess" value="1"{if $enableDelayedOpenAccess} checked="checked"{/if} />&nbsp;
	<label for="enableDelayedOpenAccess">{translate key="manager.subscriptionPolicies.delayedOpenAccessDescription1"}</label>
	<select name="delayedOpenAccessDuration" id="delayedOpenAccessDuration" class="selectMenu" />{html_options options=$validDuration selected=$delayedOpenAccessDuration}</select>
	{translate key="manager.subscriptionPolicies.delayedOpenAccessDescription2"}


<p>
	<h4>{translate key="manager.subscriptionPolicies.authorSelfArchive"}</h4>
	<input type="checkbox" name="enableAuthorSelfArchive" id="enableAuthorSelfArchive" value="1"{if $enableAuthorSelfArchive} checked="checked"{/if} />&nbsp;
	<label for="enableAuthorSelfArchive">{translate key="manager.subscriptionPolicies.authorSelfArchiveDescription"}</label>
</p>
<p>
	<textarea name="authorSelfArchivePolicy" id="authorSelfArchivePolicy" rows="12" cols="60" class="textArea">{$authorSelfArchivePolicy|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.subscriptionPolicies.htmlInstructions"}</span>
</p>


<div class="separator"></div>

<h3>{translate key="manager.subscriptionPolicies.subscriptionAdditionalInformation"}</h3>
<p>{translate key="manager.subscriptionPolicies.subscriptionAdditionalInformationDescription"}</p>
<p>
	<textarea name="subscriptionAdditionalInformation" id="subscriptionAdditionalInformation" rows="12" cols="60" class="textArea">{$subscriptionAdditionalInformation|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.subscriptionPolicies.htmlInstructions"}</span>
</p>

<div class="separator"></div>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="subscriptionPolicies" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
