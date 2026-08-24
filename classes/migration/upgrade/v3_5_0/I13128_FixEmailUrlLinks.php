<?php

/**
 * @file classes/migration/upgrade/v3_5_0/I13128_FixEmailUrlLinks.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I13128_FixEmailUrlLinks
 *
 * @brief Adds the replacements that only occur in the OJS translations
 */

namespace APP\migration\upgrade\v3_5_0;

class I13128_FixEmailUrlLinks extends \PKP\migration\upgrade\v3_5_0\I13128_FixEmailUrlLinks
{
    public function up(): void
    {
        parent::up();

        $this->replace('SUBSCRIPTION_PURCHASE_INDL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');
        $this->replace('SUBSCRIPTION_RENEW_INDL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');
        $this->replace('SUBSCRIPTION_RENEW_INSTL', '{$subscriptionUrl}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', 'href="{$subscriptionUrl}"');

        // In lt every variable is missplaced and the URL is missing.
        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            '„{contextName}“',
            '„{$contextName}“',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Institucija:<br />' . "\n" . '{$subscriptionType}<br />' . "\n" . '{$institutionName}',
            'Institucija:<br />' . "\n" . '{$institutionName}<br />' . "\n" . '{$institutionMailingAddress}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$institutionMailingAddress}',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$ipRanges}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$ipRanges}',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos adresas: {$membership}',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            '',
            'lt'
        );

        // Same problem in the individual renewal
        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            '„{contextName}“',
            '„{$contextName}“',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Vartotojas:<br />' . "\n" . '{$subscriptionType}',
            'Vartotojas:<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos adresas: {$membership}',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            '',
            'lt'
        );

        // Two translations translated the variable name, so it was never replaced
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '{$ګډون یو آر ایل}', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '', 'ps');
        $this->replace('USER_VALIDATE_SITE', '{$چالاککردنیUrl}', '<a href="{$activateUrl}">{$activateUrl}</a>', '', 'ckb');
    }

    public function down(): void
    {
        // Undo the lt steps first, in reverse order
        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            'Prenumeratos adresas: {$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$subscriberDetails}',
            'Asmuo susirašinėjimui:<br />' . "\n" . '{$ipRanges}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$ipRanges}',
            'IP intervalas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$domain}',
            'Domenas (jei jis numatytas):<br />' . "\n" . '{$institutionMailingAddress}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Institucija:<br />' . "\n" . '{$institutionName}<br />' . "\n" . '{$institutionMailingAddress}',
            'Institucija:<br />' . "\n" . '{$subscriptionType}<br />' . "\n" . '{$institutionName}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INSTL',
            '„{$contextName}“',
            '„{contextName}“',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos adresas: <a href="{$subscriptionUrl}">{$subscriptionUrl}</a>',
            'Prenumeratos adresas: {$membership}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$membership}',
            'Informacija apie narystę (jei numatyta):<br />' . "\n" . '{$subscriberDetails}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Vartotojas:<br />' . "\n" . '{$subscriberDetails}',
            'Vartotojas:<br />' . "\n" . '{$subscriptionType}',
            '',
            'lt'
        );
        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            'Prenumeratos rūšis:<br />' . "\n" . '{$subscriptionType}',
            'Prenumeratos rūšis:<br />' . "\n" . '{$contextName}',
            '',
            'lt'
        );

        $this->replace(
            'SUBSCRIPTION_RENEW_INDL',
            '„{$contextName}“',
            '„{contextName}“',
            '',
            'lt'
        );

        $this->replace('SUBSCRIPTION_PURCHASE_INDL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');
        $this->replace('SUBSCRIPTION_PURCHASE_INSTL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');
        $this->replace('SUBSCRIPTION_RENEW_INDL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');
        $this->replace('SUBSCRIPTION_RENEW_INSTL', '<a href="{$subscriptionUrl}">{$subscriptionUrl}</a>', '{$subscriptionUrl}');

        parent::down();
    }
}
