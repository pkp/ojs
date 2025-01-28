<?php

/**
 * @file plugins/importexport/csv/classes/processors/UsersProcessor.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2003-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsersProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Process the users data into the database.
 */

namespace PKP\Plugins\ImportExport\CSV\Classes\Processors;

use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedDaos;
use PKP\Plugins\ImportExport\CSV\Classes\CachedAttributes\CachedEntities;

class UsersProcessor
{
    /**
	 * Process data for Users
	 *
	 * @param object $data
	 * @param string $locale
	 *
	 * @return \User
	 */
	public static function process($data, $locale)
    {
		$userDao = CachedDaos::getUserDao();

        $user = $userDao->newDataObject();
        $user->setGivenName($data->firstname, $locale);
        $user->setFamilyName($data->lastname, $locale);
        $user->setEmail($data->email);
        $user->setAffiliation($data->affiliation, $locale);
        $user->setCountry($data->country);
        $user->setUsername($data->username);
        $user->setMustChangePassword(true);
        $user->setDateRegistered(\Core::getCurrentDate());
        $user->setPassword(\Validation::encryptCredentials($data->username, $data->tempPassword));

        $userDao->insertObject($user);

        return $user;
	}

	/**
	 * Get a valid username for a user.
	 *
	 * @param string $firstname
	 * @param string $lastname
	 *
	 * @return string|null
	 */
    public static function getValidUsername($firstname, $lastname)
    {
        $letters = range('a', 'z');

        do {
            $randomLetters = '';
            for ($i = 0; $i < 3; $i++) {
                $randomLetters .= $letters[array_rand($letters)];
            }

            $username = mb_strtolower(mb_substr($firstname, 0, 1) . $lastname . $randomLetters);

            $existingUser = CachedEntities::getCachedUserByUsername($username);

        } while (!is_null($existingUser));

        return $username;
    }
}
