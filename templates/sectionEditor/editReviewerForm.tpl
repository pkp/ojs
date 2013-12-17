{**
 * editReviewerForm.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editors to update reviewer profile.
 *
 * Created at California Digital Library for eScholarship. BLH. Similar to createReviewerForm.tpl.
 *
 * $Id$
 *}

{strip}
{assign var="pageTitle" value="sectionEditor.review.editReviewer"}
{include file="common/header.tpl"}
{/strip}

{literal}
<script type="text/javascript">
        $(document).ready(function(){
                $("#interestsTextOnly").hide();
                $("#interests").tagit({
                        {/literal}{if $existingInterests}{literal}
                        // This is the list of interests in the system used to populate the autocomplete
                        availableTags: [{/literal}{foreach name=existingInterests from=$existingInterests item=interest}"{$interest|escape|escape:'javascript'}"{if !$smarty.foreach.existingInterests.last}, {/if}{/foreach}{literal}],{/literal}{/if}
                        // This is the list of the user's interests that have already been saved
                        {if $interestsKeywords}{literal}currentTags: [{/literal}{foreach name=currentInterests from=$interestsKeywords item=interest}"{$interest|escape|escape:'javascript'}"{if !$smarty.foreach.currentInterests.last}, {/if}{/foreach}{literal}]{/literal}
                        {else}{literal}currentTags: []{/literal}{/if}{literal}
                });
        });

        function toggleAffiliationOther(value) {
                if (value == 'Other') {
                        document.getElementById('affiliationOther').style.display = 'block';
                } else {
                        document.getElementById('affiliationOther').style.display = 'none';
                }
        }
</script>
{/literal}

<h3>{translate key="user.profile"}: {$firstName} {$middleName} {$lastName}</h3>

<form method="post" name="reviewerForm" action="{url op="editReviewer" path=$userId|to_array:$articleId:"update"}">
{include file="common/formErrors.tpl"}

<div id="editReviewerForm">
<table width="100%" class="data">
	<tr valign="top">
        	<td width="20%" class="label">{translate key="user.username"}:</td>
        	<td width="80%" class="value">{$username|escape}</td>
	</tr>
	<tr valign="top">
        	<td width="20%" class="label">{translate key="user.salutation"}:</td>
        	<td width="80%" class="value">{$salutation|escape}</td>
	</tr>
	<tr valign="top">
        	<td class="label">{translate key="user.firstName"}:</td>
        	<td class="value">{$firstName|escape}</td>
	</tr>
	<tr valign="top">
        	<td class="label">{translate key="user.middleName"}:</td>
        	<td class="value">{$middleName|escape}</td>
	</tr>
	<tr valign="top">
        	<td class="label">{translate key="user.lastName"}:</td>
        	<td class="value">{$lastName|escape}</td>
	</tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
                <td class="value">
                        <select name="affiliation[{$formLocale|escape}]" id="affiliation" class="selectMenu" onchange="toggleAffiliationOther(this.value)">
                                <option value="">Select Institution</option>
                                {html_options options=$institutionList selected=$affiliation[$formLocale]|escape}
                                <option value="Other" {if not in_array($affiliation[$formLocale], $institutionList) and $affiliation[$formLocale] != ''}selected{/if}>Other:</option>
                        </select>
                        <!--{** Only show "other" field if appropriate **} -->
                        {if not in_array($affiliation[$formLocale], $institutionList) and $affiliation[$formLocale] != ''}
                                {assign var="displayStyle" value=""}
                                {assign var="affiliationOther" value=$affiliation[$formLocale]|escape}
                        {else}
                                {assign var="displayStyle" value='style="display:none"'}
                                {assign var="affiliationOther" value=""}
                        {/if}
                        <input type="text" name="affiliationOther" id="affiliationOther" value="{$affiliationOther}" size="70" class="textField" {$displayStyle} />
                </td>
<!--{**
                <td class="value">
                        <textarea name="affiliation[{$formLocale|escape}]" id="affiliation" rows="5" cols="40" class="textArea">{$affiliation[$formLocale]|escape}</textarea><br/>
                        <span class="instruct">{translate key="user.affiliation.description"}</span>
                </td>
**}-->
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="professionalTitle" required="true" key="user.professionalTitle"}</td>
                <td class="value"><input type="text" name="professionalTitle[{$formLocale|escape}]" id="professionalTitle" value="{$professionalTitle[$formLocale]|escape}" size="30" maxlength="90" class="textField" /></td>
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
                <td class="value"><input type="text" name="email" id="email" value="{$email|escape}" size="30" maxlength="90" class="textField" /></td>
        </tr>
        <input type="hidden" name="email" id="email" value="{$email|escape}" />
        <tr valign="top">
                <td class="label">{fieldLabel name="userUrl" key="user.url"}</td>
                <td class="value"><input type="text" name="userUrl" id="userUrl" value="{$userUrl|escape}" size="30" maxlength="255" class="textField" /></td>
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="phone" key="user.phone"}</td>
                <td class="value"><input type="text" name="phone" id="phone" value="{$phone|escape}" size="15" maxlength="24" class="textField" /></td>
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="fax" key="user.fax"}</td>
                <td class="value"><input type="text" name="fax" id="fax" value="{$fax|escape}" size="15" maxlength="24" class="textField" /></td>
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel for="interests" key="user.interests"}</td>
                <td class="value">
                        <span class="interestDescription">{fieldLabel for="interests" key="user.interests.description"}</span><br />
                        <ul id="interests"><li></li></ul>
                        <textarea name="interests" id="interestsTextOnly" rows="5" cols="40" class="textArea">
                                {foreach name=currentInterests from=$interestsKeywords item=interest}{$interest|escape}{if !$smarty.foreach.currentInterests.last}, {/if}{/foreach}
                        </textarea>
                </td>
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="gossip" key="user.gossip"}</td>
                <td class="value"><textarea name="gossip[{$formLocale|escape}]" id="gossip" rows="3" cols="40" class="textArea">{$gossip[$formLocale]|escape}</textarea></td>
        </tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="mailingAddress" key="common.mailingAddress"}</td>
                <td class="value"><textarea name="mailingAddress" id="mailingAddress" rows="3" cols="40" class="textArea">{$mailingAddress|escape}</textarea></td>
        </tr>
<tr valign="top">
        <td class="label">{fieldLabel name="country" key="common.country"}</td>
        <td class="value">
                <select name="country" id="country" class="selectMenu">
                        <option value=""></option>
                        {html_options options=$countries selected=$country}
                </select>
        </td>
</tr>
        <tr valign="top">
                <td class="label">{fieldLabel name="biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
                <td class="value"><textarea name="biography[{$formLocale|escape}]" id="biography" rows="5" cols="40" class="textArea">{$biography[$formLocale]|escape}</textarea></td>
        </tr>
</table>

<p>
        <input type="submit" value="{translate key="common.save"}" class="button defaultButton" />
	<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="userProfile" path=$userId|to_array:$articleId escape=false}'" />
</p>
</div>
