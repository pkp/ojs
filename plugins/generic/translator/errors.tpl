{**
 * errors.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Error list for a checked locale
 *
 * $Id$
 *}

{assign var="pageTitle" value="plugins.generic.translator.errors"}
{include file="common/header.tpl"}

<ul class="menu">
	<li><a href="{url op="edit" path=$locale}">{translate key="common.edit"}</a></li>
	<li class="current"><a href="{url op="check" path=$locale}">{translate key="plugins.generic.translator.check"}</a></li>
</ul>

<br/>

<form action="{url op="saveLocaleChanges" path=$locale}" method="post">
<input type="hidden" name="redirectUrl" value="{url op="translate"}" />

{foreach from=$errors key=type item=categoryErrors}
	{if !empty($categoryErrors)}
		<h2>{translate key="plugins.generic.translator.errors.$type.title"}</h2>
		<p>{translate key="plugins.generic.translator.errors.$type.description"}</p>
		<ul>
	{/if}
	{foreach from=$categoryErrors item=error}
		<li>
			{translate key="plugins.generic.translator.errors.$type.message" params=$error}
			{if $type == 'LOCALE_ERROR_DIFFERING_PARAMS'}
				<ul>
					{foreach from=$error.mismatch item=param}
						<li>{$param|escape}</li>
					{/foreach}
				</ul>
			{/if}
			{if $type == 'LOCALE_ERROR_EXTRA_KEY'}
				<br />
				{assign var=counter value=$counter+1}
				<input type="checkbox" name="deleteKey[]" id="checkbox-{$counter}" value="{$error.filename|escape:"url"|escape:"url"}/{$error.key|escape:"quotes"}" />
				<label for="checkbox-{$counter}">{translate key="plugins.generic.translator.deleteKey"}</label>
			{elseif $type == 'LOCALE_ERROR_MISSING_FILE'}
				{assign var=filenameEscaped value=$error.filename|escape:"url"|escape:"url"}
				{if in_array($error.filename, $localeFiles)}
					{url|assign:"redirectUrl" op="editLocaleFile" path=$locale|to_array:$filenameEscaped}
				{else}
					{url|assign:"redirectUrl" op="editMiscFile" path=$locale|to_array:$filenameEscaped}
				{/if}
				<a href="{url op="createFile" path=$locale|to_array:$filenameEscaped redirectUrl=$redirectUrl}" onclick='return confirm("{translate|escape:"quotes" key="plugins.generic.translator.saveBeforeContinuing"}")' class="action">{translate key="common.create"}</a>
			{else}
				{if $type == 'LOCALE_ERROR_MISSING_KEY'}
					{assign var=defaultValue value=$error.reference}
				{else}
					{assign var=defaultValue value=$error.value}
				{/if}
				<input type="hidden" name="stack[]" value="{$error.filename|escape:"quotes"}" />
				<input type="hidden" name="stack[]" value="{$error.key|escape:"quotes"}" />
				<br />
				{if ($defaultValue|explode:"\n"|@count > 1) || (strlen($defaultValue) > 80)}
					<textarea name="stack[]" class="textArea" cols="80" rows="5">{$defaultValue|escape}</textarea>
				{else}
					<input type="text" class="textField" name="stack[]" size="80" value="{$defaultValue|escape}" />
				{/if}
				<br />&nbsp;
			{/if}
		</li>
	{/foreach}
	</ul>
{/foreach}

{foreach from=$emailErrors key=type item=categoryErrors}
	{if !empty($categoryErrors)}
		<h2>{translate key="plugins.generic.translator.errors.$type.title"}</h2>
		<p>{translate key="plugins.generic.translator.errors.$type.description"}</p>
		<ul>
	{/if}
	{foreach from=$categoryErrors item=error}
		<li>
			{translate key="plugins.generic.translator.errors.$type.message" params=$error}
			{if $type == 'EMAIL_ERROR_EXTRA_EMAIL'}
				<br />
				{assign var=counter value=$counter+1}
				<input type="checkbox" name="deleteEmail[]" id="checkbox-{$counter}" value="{$error.key|escape:"quotes"}" />
				<label for="checkbox-{$counter}">{translate key="plugins.generic.translator.deleteEmail"}</label>
			{else}
				<a href="{url op="editEmail" path=$locale|to_array:$error.key returnToCheck=1}" class="action" onclick='return confirm("{translate|escape:"quotes" key="plugins.generic.translator.saveBeforeContinuing"}")'>{translate key="common.edit"}</a>
			{/if}
			{if $type == 'EMAIL_ERROR_DIFFERING_PARAMS'}
				<ul>
					{foreach from=$error.mismatch item=param}
						<li>{$param|escape}</li>
					{/foreach}
				</ul>
			{/if}
		</li>
	{/foreach}
	</ul>
{/foreach}

{if !empty($errors)}
	<input type="submit" class="button defaultButton" value="{translate key="common.save"}" />
{/if}

</form>

{include file="common/footer.tpl"}
