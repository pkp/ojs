{**
 * templates/common/userDetails.tpl
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common user details form.
 *
 * Parameters:
 *   $disableUserNameSection: Disable UserName section
 *   $disableEmailSection: Disable Email section
 *   $disableAuthSourceSection: Disable Auth section
 *   $disablePasswordSection: Disable Password section
 *   $disableSendNotifySection: Disable SendNotify section
 *   $disableGenderSection: Disable Gender section
 *   $disableSalutationSection: Disable Salutation section
 *   $disableInitialsSection: Disable Initials section
 *   $disablePhoneSection: Disable Phone section
 *   $disableLocaleSection: Disable Locale section
 *   $disableInterestsSection: Disable Interests section
 *   $disableMailingSection: Disable Mailing section
 *   $disableSignatureSection: Disable Signature section
 *
 *   $countryRequired: Whether or not the country select is a required field
 *   $extraContentSectionUnfolded: Whether or not the extra content section is unfolded by default
 *}

{fbvFormArea id="userDetails"}
	{fbvFormSection title="user.name"}
		{fbvElement type="text" label="user.firstName" required="true" id="firstName" value=$firstName maxlength="40" inline=true size=$fbvStyles.size.SMALL}
		{fbvElement type="text" label="user.middleName" id="middleName" value=$middleName maxlength="40" inline=true size=$fbvStyles.size.SMALL}
		{fbvElement type="text" label="user.lastName" required="true" id="lastName" value=$lastName maxlength="40" inline=true size=$fbvStyles.size.SMALL}
	{/fbvFormSection}

	{if !$disableUserNameSection}
		{if !$userId}{capture assign="usernameInstruction"}{translate key="user.register.usernameRestriction"}{/capture}{/if}
		{fbvFormSection for="username" description=$usernameInstruction translate=false}
			{if !$userId}
				{fbvElement type="text" label="user.username" id="username" required="true" value=$username maxlength="32" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="button" label="common.suggest" id="suggestUsernameButton" inline=true class="default"}
			{else}
				{fbvFormSection title="user.username" suppressId="true"}
					{$username|escape}
				{/fbvFormSection}
			{/if}
		{/fbvFormSection}
	{/if}

	{if !$disableEmailSection}
		{fbvFormSection title="about.contact"}
			{fbvElement type="text" label="user.email" id="email" required="true" value=$email maxlength="90" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
	{/if}

	{if !$disableAuthSourceSection}
		{fbvFormSection title="grid.user.authSource" for="authId"}
			{fbvElement type="select" name="authId" id="authId" defaultLabel="" defaultValue="" from=$authSourceOptions translate="true" selected=$authId}
		{/fbvFormSection}
	{/if}

	{if !$disablePasswordSection}
		{if $userId}{capture assign="passwordInstruction"}{translate key="user.profile.leavePasswordBlank"} {translate key="user.register.form.passwordLengthRestriction" length=$minPasswordLength}{/capture}{/if}
		{fbvFormArea id="passwordSection" title="user.password"}
			{fbvFormSection for="password" description=$passwordInstruction translate=false}
				{fbvElement type="text" label="user.password" required=$passwordRequired name="password" id="password" password="true" value=$password maxlength="32" inline=true size=$fbvStyles.size.MEDIUM}
				{fbvElement type="text" label="user.repeatPassword" required=$passwordRequired name="password2" id="password2" password="true" value=$password2 maxlength="32" inline=true size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}

			{if !$userId}
				{fbvFormSection title="grid.user.generatePassword" for="generatePassword" list=true}
					{if $generatePassword}
						{assign var="checked" value=true}
					{else}
						{assign var="checked" value=false}
					{/if}
					{fbvElement type="checkbox" name="generatePassword" id="generatePassword" checked=$checked label="grid.user.generatePasswordDescription" translate="true"}
				{/fbvFormSection}
			{/if}
			{fbvFormSection title="grid.user.mustChangePassword" for="mustChangePassword" list=true}
				{if $mustChangePassword}
					{assign var="checked" value=true}
				{else}
					{assign var="checked" value=false}
				{/if}
				{fbvElement type="checkbox" name="mustChangePassword" id="mustChangePassword" checked=$checked label="grid.user.mustChangePasswordDescription" translate="true"}
			{/fbvFormSection}
		{/fbvFormArea}
	{/if}

	{if $countryRequired}
		{assign var="countryRequired" value=true}
	{else}
		{assign var="countryRequired" value=false}
	{/if}
	{fbvFormSection for="country" title="common.country"}
		{fbvElement type="select" label="common.country" name="country" id="country" required=$countryRequired defaultLabel="" defaultValue="" from=$countries selected=$country translate="0" size=$fbvStyles.size.MEDIUM}
	{/fbvFormSection}

	{if !$disableSendNotifySection}
		{fbvFormSection title="grid.user.notifyUser" for="sendNotify" list=true}
			{if $sendNotify}
				{assign var="checked" value=true}
			{else}
				{assign var="checked" value=false}
			{/if}
			{fbvElement type="checkbox" name="sendNotify" id="sendNotify" checked=$checked label="grid.user.notifyUserDescription" translate="true"}
		{/fbvFormSection}
	{/if}
{/fbvFormArea}
{call_hook name="Common::UserDetails::AdditionalItems"}
{capture assign="extraContent"}
	{fbvFormArea id="userFormExtendedLeft"}
		{fbvFormSection}
			{if !$disableGenderSection}
				{fbvElement type="select" label="user.gender" name="gender" id="gender" defaultLabel="" defaultValue="" from=$genderOptions translate="true" selected=$gender inline=true size=$fbvStyles.size.SMALL}
			{/if}
			{if !$disableSalutationSection}
				{fbvElement type="text" label="user.salutation" name="salutation" id="salutation" value=$salutation maxlength="40" inline=true size=$fbvStyles.size.SMALL}
			{/if}
			{fbvElement type="text" label="user.suffix" id="suffix" value=$suffix size=$fbvStyles.size.SMALL inline=true}
			{if !$disableInitialsSection}
				{fbvElement type="text" label="user.initials" name="initials" id="initials" value=$initials maxlength="5" inline=true size=$fbvStyles.size.SMALL}
			{/if}
		{/fbvFormSection}

		{fbvFormSection}
			{fbvElement type="text" label="user.url" name="userUrl" id="userUrl" value=$userUrl maxlength="255" inline=true size=$fbvStyles.size.SMALL}
			{if !$disablePhoneSection}
				{fbvElement type="text" label="user.phone" name="phone" id="phone" value=$phone maxlength="24" inline=true size=$fbvStyles.size.SMALL}
			{/if}
			{fbvElement type="text" label="user.orcid" name="orcid" id="orcid" value=$orcid maxlength="36" inline=true size=$fbvStyles.size.SMALL}
		{/fbvFormSection}

		{if !$disableLocaleSection && count($availableLocales) > 1}
			{fbvFormSection title="user.workingLanguages" list=true}
				{foreach from=$availableLocales key=localeKey item=localeName}
					{if $userLocales && in_array($localeKey, $userLocales)}
						{assign var="checked" value=true}
					{else}
						{assign var="checked" value=false}
					{/if}
					{fbvElement type="checkbox" name="userLocales[]" id="userLocales-$localeKey" value=$localeKey checked=$checked label=$localeName translate=false}
				{/foreach}
			{/fbvFormSection}
		{/if}

		{if !$disableInterestsSection}
			{fbvFormSection for="interests"}
				{fbvElement type="interests" id="interests" interests=$interests label="user.interests"}
			{/fbvFormSection}
		{/if}

		{fbvFormSection for="affiliation"}
			{fbvElement type="text" label="user.affiliation" multilingual="true" name="affiliation" id="affiliation" value=$affiliation inline=true size=$fbvStyles.size.LARGE}
		{/fbvFormSection}

		{fbvFormSection}
			{fbvElement type="textarea" label="user.biography" multilingual="true" name="biography" id="biography" rich=true value=$biography}
		{/fbvFormSection}

		{if !$disableMailingSection}
			{fbvFormSection}
				{fbvElement type="textarea" label="common.mailingAddress" name="mailingAddress" id="mailingAddress" rich=true value=$mailingAddress}
			{/fbvFormSection}
		{/if}

		{if !$disableSignatureSection}
			{fbvFormSection}
				{fbvElement type="textarea" label="user.signature" multilingual="true" name="signature" id="signature" value=$signature rich=true}
			{/fbvFormSection}
		{/if}
	{/fbvFormArea}
{/capture}

{if $extraContentSectionUnfolded}
	{fbvFormSection title="grid.user.userDetails"}
		{$extraContent}
	{/fbvFormSection}
{else}
	<div id="userExtraFormFields" class="left full">
		{include file="controllers/extrasOnDemand.tpl"
			id="userExtras"
			widgetWrapper="#userExtraFormFields"
			moreDetailsText="grid.user.moreDetails"
			lessDetailsText="grid.user.lessDetails"
			extraContent=$extraContent
		}
	</div>
{/if}
