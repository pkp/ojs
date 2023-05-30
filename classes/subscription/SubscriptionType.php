<?php

/**
 * @file classes/subscription/SubscriptionType.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionType
 *
 * @ingroup subscription
 *
 * @see SubscriptionTypeDAO
 *
 * @brief Basic class describing a subscription type.
 */

namespace APP\subscription;

use PKP\facades\Locale;

class SubscriptionType extends \PKP\core\DataObject
{
    // Subscription type formats
    public const SUBSCRIPTION_TYPE_FORMAT_ONLINE = 1;
    public const SUBSCRIPTION_TYPE_FORMAT_PRINT = 16;
    public const SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE = 17;

    //
    // Get/set methods
    //

    /**
     * Get the journal ID of the subscription type.
     *
     * @return int
     */
    public function getJournalId()
    {
        return $this->getData('journalId');
    }

    /**
     * Set the journal ID of the subscription type.
     *
     * @param int $journalId
     */
    public function setJournalId($journalId)
    {
        return $this->setData('journalId', $journalId);
    }

    /**
     * Get the localized subscription type name
     *
     * @return string
     */
    public function getLocalizedName()
    {
        return $this->getLocalizedData('name');
    }

    /**
     * Get subscription type name.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getName($locale)
    {
        return $this->getData('name', $locale);
    }

    /**
     * Set subscription type name.
     *
     * @param string $name
     * @param string $locale
     */
    public function setName($name, $locale)
    {
        return $this->setData('name', $name, $locale);
    }

    /**
     * Get the localized subscription type description
     *
     * @return string
     */
    public function getLocalizedDescription()
    {
        return $this->getLocalizedData('description');
    }

    /**
     * Get subscription type description.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getDescription($locale)
    {
        return $this->getData('description', $locale);
    }

    /**
     * Set subscription type description.
     *
     * @param string $description
     * @param string $locale
     */
    public function setDescription($description, $locale)
    {
        return $this->setData('description', $description, $locale);
    }

    /**
     * Get subscription type cost.
     *
     * @return float
     */
    public function getCost()
    {
        return $this->getData('cost');
    }

    /**
     * Set subscription type cost.
     *
     * @param float $cost
     */
    public function setCost($cost)
    {
        return $this->setData('cost', $cost);
    }

    /**
     * Get subscription type currency code.
     *
     * @return string
     */
    public function getCurrencyCodeAlpha()
    {
        return $this->getData('currencyCodeAlpha');
    }

    /**
     * Set subscription type currency code.
     *
     * @param string $currencyCodeAlpha
     */
    public function setCurrencyCodeAlpha($currencyCodeAlpha)
    {
        return $this->setData('currencyCodeAlpha', $currencyCodeAlpha);
    }

    /**
     * Get subscription type currency string.
     *
     * @return string
     */
    public function getCurrencyString()
    {
        $currency = Locale::getCurrencies()->getByLetterCode($this->getData('currencyCodeAlpha'));
        return $currency ? $currency->getLocalName() : 'subscriptionTypes.currency';
    }

    /**
     * Get subscription type currency abbreviated string.
     *
     * @return string
     */
    public function getCurrencyStringShort()
    {
        $currency = Locale::getCurrencies()->getByLetterCode($this->getData('currencyCodeAlpha'));
        return $currency ? $currency->getLetterCode() : 'subscriptionTypes.currency';
    }

    /**
     * Get subscription type nonExpiring.
     *
     * @return bool
     */
    public function getNonExpiring()
    {
        return $this->getDuration() == null;
    }

    /**
     * Get subscription type duration.
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->getData('duration');
    }

    /**
     * Set subscription type duration.
     *
     * @param int $duration
     */
    public function setDuration($duration)
    {
        return $this->setData('duration', $duration);
    }

    /**
     * Get subscription type duration in years and months.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getDurationYearsMonths($locale = null)
    {
        if (!$this->getDuration()) {
            return __('subscriptionTypes.nonExpiring', [], $locale);
        }

        $years = (int)floor($this->getDuration() / 12);
        $months = (int)fmod($this->getDuration(), 12);
        $yearsMonths = '';

        if ($years == 1) {
            $yearsMonths = '1 ' . __('subscriptionTypes.year', [], $locale);
        } elseif ($years > 1) {
            $yearsMonths = $years . ' ' . __('subscriptionTypes.years', [], $locale);
        }

        if ($months == 1) {
            $yearsMonths .= $yearsMonths == '' ? '1 ' : ' 1 ';
            $yearsMonths .= __('subscriptionTypes.month', [], $locale);
        } elseif ($months > 1) {
            $yearsMonths .= $yearsMonths == '' ? $months . ' ' : ' ' . $months . ' ';
            $yearsMonths .= __('subscriptionTypes.months', [], $locale);
        }

        return $yearsMonths;
    }

    /**
     * Get subscription type format.
     *
     * @return int
     */
    public function getFormat()
    {
        return $this->getData('format');
    }

    /**
     * Set subscription type format.
     *
     * @param int $format
     */
    public function setFormat($format)
    {
        return $this->setData('format', $format);
    }

    /**
     * Get subscription type format locale key.
     *
     * @return string
     */
    public function getFormatString()
    {
        switch ($this->getData('format')) {
            case self::SUBSCRIPTION_TYPE_FORMAT_ONLINE:
                return 'subscriptionTypes.format.online';
            case self::SUBSCRIPTION_TYPE_FORMAT_PRINT:
                return 'subscriptionTypes.format.print';
            case self::SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE:
                return 'subscriptionTypes.format.printOnline';
            default:
                return 'subscriptionTypes.format';
        }
    }

    /**
     * Check if this subscription type is for an institution.
     *
     * @return bool
     */
    public function getInstitutional()
    {
        return $this->getData('institutional');
    }

    /**
     * Set whether or not this subscription type is for an institution.
     *
     * @param bool $institutional
     */
    public function setInstitutional($institutional)
    {
        return $this->setData('institutional', $institutional);
    }

    /**
     * Check if this subscription type requires a membership.
     *
     * @return bool
     */
    public function getMembership()
    {
        return $this->getData('membership');
    }

    /**
     * Set whether or not this subscription type requires a membership.
     *
     * @param bool $membership
     */
    public function setMembership($membership)
    {
        return $this->setData('membership', $membership);
    }

    /**
     * Check if this subscription type should be publicly visible.
     *
     * @return bool
     */
    public function getDisablePublicDisplay()
    {
        return $this->getData('disable_public_display');
    }

    /**
     * Set whether or not this subscription type should be publicly visible.
     *
     * @param bool $disablePublicDisplay
     */
    public function setDisablePublicDisplay($disablePublicDisplay)
    {
        return $this->setData('disable_public_display', $disablePublicDisplay);
    }

    /**
     * Get subscription type display sequence.
     *
     * @return float
     */
    public function getSequence()
    {
        return $this->getData('sequence');
    }

    /**
     * Set subscription type display sequence.
     *
     * @param float $sequence
     */
    public function setSequence($sequence)
    {
        return $this->setData('sequence', $sequence);
    }

    /**
     * Get subscription type summary in the form: TypeName - Duration - Cost (CurrencyShort).
     *
     * @return string
     */
    public function getSummaryString()
    {
        return $this->getLocalizedName() . ' - ' . $this->getDurationYearsMonths() . ' - ' . sprintf('%.2f', $this->getCost()) . ' ' . $this->getCurrencyStringShort();
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\subscription\SubscriptionType', '\SubscriptionType');
    foreach ([
        'SUBSCRIPTION_TYPE_FORMAT_ONLINE',
        'SUBSCRIPTION_TYPE_FORMAT_PRINT',
        'SUBSCRIPTION_TYPE_FORMAT_PRINT_ONLINE',
    ] as $constantName) {
        define($constantName, constant('\SubscriptionType::' . $constantName));
    }
}
