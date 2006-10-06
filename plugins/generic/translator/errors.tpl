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
			{if $type != 'LOCALE_ERROR_MISSING_FILE' && $type != 'LOCALE_ERROR_EXTRA_KEY'}
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

{if !empty($errors)}
	<input type="submit" class="button defaultButton" value="{translate key="common.save"}" />
{/if}

{foreach from=$emailErrors key=type item=categoryErrors}
	{if !empty($categoryErrors)}
		<h2>{translate key="plugins.generic.translator.errors.$type.title"}</h2>
		<p>{translate key="plugins.generic.translator.errors.$type.description"}</p>
		<ul>
	{/if}
	{foreach from=$categoryErrors item=error}
		<li>
			{translate key="plugins.generic.translator.errors.$type.message" params=$error}
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

</form>

{include file="common/footer.tpl"}
