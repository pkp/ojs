{**
 * @file templates/settings.tpl
 *
 * Copyright (c) 2024 Université de Montréal
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Page de paramètres du plugin Aide à la soumission Premium
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <div class="pkp_page_content">
        {include file="common/formErrors.tpl"}
        
        <div class="pkp_content_panel">
            <div class="pkp_helpers_align_center">
                <h2>{translate key="plugins.generic.premiumHelper.settings"}</h2>
                <p class="description">
                    {translate key="plugins.generic.premiumHelper.description"}
                </p>
            </div>
            
            {if $settingsForm->getPages()|@count > 1}
                <div class="pkp_controllers_tabs">
                    <ul>
                        {foreach from=$settingsForm->getPages() item=page key=pageKey}
                            <li>
                                <a href="{url op="settings" path=$pageKey}" {if $currentPageKey == $pageKey}class="current"{/if}>
                                    {$page->getLabel()}
                                </a>
                            </li>
                        {/foreach}
                    </ul>
                </div>
            {/if}
            
            <form class="pkp_form" method="post" action="{$settingsForm->getAction()}">
                {csrf}
                
                {foreach from=$settingsForm->getPages() item=page key=pageKey}
                    <div id="{$pageKey}" class="settings_page" {if $currentPageKey != $pageKey}style="display: none;"{/if}>
                        {foreach from=$page->getGroups() item=group}
                            <div class="settings_group">
                                <h3>{$group->getLabel()}</h3>
                                
                                {foreach from=$group->getFields() item=field}
                                    <div class="field">
                                        <div class="label">
                                            {fieldLabel name=$field->getName() key=$field->getLabel() required=$field->isRequired()}
                                            {if $field->getDescription()}
                                                <span class="instruct">
                                                    {translate key=$field->getDescription()}
                                                </span>
                                            {/if}
                                        </div>
                                        <div class="value">
                                            {field name=$field->getName() type=$field->getType() id=$field->getName() 
                                                value=$field->getValue()|escape size=$field->getSize() 
                                                maxlength=$field->getMaxLength() options=$field->getOptions() 
                                                disabled=$field->isDisabled()}
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        {/foreach}
                    </div>
                {/foreach}
                
                <div class="buttons">
                    {fbvFormButtons submitText="common.save"}
                </div>
            </form>
        </div>
    </div>
{/block}

{* JavaScript pour la navigation par onglets *}
{literal}
<script type="text/javascript">
    $(function() {
        // Gestion des onglets
        $('.pkp_controllers_tabs a').on('click', function(e) {
            e.preventDefault();
            
            // Mettre à jour l'onglet actif
            $('.pkp_controllers_tabs a').removeClass('current');
            $(this).addClass('current');
            
            // Afficher la page correspondante
            var target = $(this).attr('href').split('#').pop();
            $('.settings_page').hide();
            $('#' + target).show();
            
            // Mettre à jour l'URL sans recharger la page
            history.pushState(null, null, '{/literal}{url op="settings" path=""}{literal}' + target);
        });
        
        // Gestion du chargement initial avec hash dans l'URL
        if (window.location.hash) {
            var initialTab = window.location.hash.substring(1);
            if ($('#' + initialTab).length) {
                $('.pkp_controllers_tabs a').removeClass('current');
                $('.pkp_controllers_tabs a[href$="' + initialTab + '"]').addClass('current');
                $('.settings_page').hide();
                $('#' + initialTab).show();
            }
        }
        
        // Validation du formulaire
        $('form').on('submit', function() {
            var isValid = true;
            
            // Valider les champs requis
            $('[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            // Valider les nombres
            $('input[type="number"]').each(function() {
                var min = $(this).attr('min');
                var max = $(this).attr('max');
                var value = parseInt($(this).val());
                
                if ((min && value < min) || (max && value > max)) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!isValid) {
                alert('{/literal}{translate|escape:javascript key="form.errors"}{literal}');
                return false;
            }
            
            return true;
        });
    });
</script>
{/literal}

{* Styles CSS pour la page des paramètres *}
{literal}
<style type="text/css">
    .settings_group {
        margin-bottom: 2rem;
        padding: 1rem;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .settings_group h3 {
        margin-top: 0;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
        color: #007ab2;
    }
    
    .field {
        margin: 1rem 0;
    }
    
    .field .label {
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    
    .field .instruct {
        display: block;
        font-size: 0.85rem;
        color: #666;
        font-weight: normal;
        margin-top: 0.25rem;
    }
    
    .field input[type="text"],
    .field input[type="number"],
    .field textarea,
    .field select {
        width: 100%;
        max-width: 500px;
    }
    
    .field textarea {
        min-height: 100px;
    }
    
    .buttons {
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
        text-align: right;
    }
    
    .error {
        border-color: #d9534f !important;
    }
    
    @media (min-width: 768px) {
        .field {
            display: flex;
            align-items: flex-start;
        }
        
        .field .label {
            width: 250px;
            padding-right: 1rem;
            text-align: right;
        }
        
        .field .value {
            flex: 1;
        }
    }
</style>
{/literal}
