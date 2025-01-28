<?php

/**
 * @file plugins/importexport/csv/classes/processors/UsersProcessor.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UsersProcessor
 *
 * @ingroup plugins_importexport_csv
 *
 * @brief Process the users data into the database.
 */

namespace APP\plugins\importexport\csv\classes\processors;

use APP\facades\Repo;
use APP\plugins\importexport\csv\classes\cachedAttributes\CachedEntities;
use PKP\core\Core;
use PKP\security\Validation;
use PKP\user\User;

class UsersProcessor
{
    /**
     * Process data for Users
     */
    public static function process(object $data, string $locale): User
    {
        $userDao = Repo::user()->dao;

        $user = $userDao->newDataObject();
        $user->setGivenName($data->firstname, $locale);
        $user->setFamilyName($data->lastname, $locale);
        $user->setEmail($data->email);
        $user->setAffiliation($data->affiliation, $locale);
        $user->setCountry($data->country);
        $user->setUsername($data->username);
        $user->setMustChangePassword(true);
        $user->setDateRegistered(Core::getCurrentDate());
        $user->setPassword(Validation::encryptCredentials($data->username, $data->tempPassword));

        $userDao->insert($user);

        return $user;
    }

    public static function getValidUsername(string $firstname, string $lastname): ?string
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
