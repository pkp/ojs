<?php

/**
 * @file classes/subscription/SubscriptionType.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Subscriptiontyoe
 * @ingroup subscription
 *
 * @see SubscriptionTypeDAO
 *
 * @brief Basic class describing a subscription type.
 */

namespace APP\subscription;

use PKP\db\DAORegistry;

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
     * @param $journalId int
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
     * @param $locale string
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
     * @param $name string
     * @param $locale string
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
     * @param $locale string
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
     * @param $description string
     * @param $locale string
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
     * @param $cost float
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
     * @param $currencyCodeAlpha string
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
        $isoCodes = new \Sokil\IsoCodes\IsoCodesFactory();
        $currency = $isoCodes->getCurrencies()->getByLetterCode($this->getData('currencyCodeAlpha'));
        return $currency ? $currency->getLocalName() : 'subscriptionTypes.currency';
    }

    /**
     * Get subscription type currency abbreviated string.
     *
     * @return int
     */
    public function getCurrencyStringShort()
    {
        $isoCodes = new \Sokil\IsoCodes\IsoCodesFactory();
        $currency = $isoCodes->getCurrencies()->getByLetterCode($this->getData('currencyCodeAlpha'));
        return $currency ? $currency->getLetterCode() : 'subscriptionTypes.currency';
    }

    /**
     * Get subscription type nonExpiring.
     *
     * @return boolean
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
     * @param $duration int
     */
    public function setDuration($duration)
    {
        return $this->setData('duration', $duration);
    }

    /**
     * Get subscription type duration in years and months.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getDurationYearsMonths($locale = null)
    {
        if (!$this->getDuration()) {
            return __('subscriptionTypes.nonExpiring', null, $locale);
        }

        $years = (int)floor($this->getDuration() / 12);
        $months = (int)fmod($this->getDuration(), 12);
        $yearsMonths = '';

        if ($years == 1) {
            $yearsMonths = '1 ' . __('subscriptionTypes.year', null, $locale);
        } elseif ($years > 1) {
            $yearsMonths = $years . ' ' . __('subscriptionTypes.years', null, $locale);
        }

        if ($months == 1) {
            $yearsMonths .= $yearsMonths == '' ? '1 ' : ' 1 ';
            $yearsMonths .= __('subscriptionTypes.month', null, $locale);
        } elseif ($months > 1) {
            $yearsMonths .= $yearsMonths == '' ? $months . ' ' : ' ' . $months . ' ';
            $yearsMonths .= __('subscriptionTypes.months', null, $locale);
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
     * @param $format int
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
     * @return boolean
     */
    public function getInstitutional()
    {
        return $this->getData('institutional');
    }

    /**
     * Set whether or not this subscription type is for an institution.
     *
     * @param $institutional boolean
     */
    public function setInstitutional($institutional)
    {
        return $this->setData('institutional', $institutional);
    }

    /**
     * Check if this subscription type requires a membership.
     *
     * @return boolean
     */
    public function getMembership()
    {
        return $this->getData('membership');
    }

    /**
     * Set whether or not this subscription type requires a membership.
     *
     * @param $membership boolean
     */
    public function setMembership($membership)
    {
        return $this->setData('membership', $membership);
    }

    /**
     * Check if this subscription type should be publicly visible.
     *
     * @return boolean
     */
    public function getDisablePublicDisplay()
    {
        return $this->getData('disable_public_display');
    }

    /**
     * Set whether or not this subscription type should be publicly visible.
     *
     * @param $disablePublicDisplay boolean
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
     * @param $sequence float
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
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO'); /* @var $subscriptionTypeDao SubscriptionTypeDAO */
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
