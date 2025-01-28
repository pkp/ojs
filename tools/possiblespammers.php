<?php

# version: 20241202.1054
/**
 * @file tools/possibleSpammers.php
 *
 * @brief CLI tool to identify possible spammers in an installation.
 */

use APP\core\Application;
use APP\facades\Repo;
use PKP\cliTool\CommandLineTool;
use PKP\db\DAORegistry;
use PKP\security\Role;
use PKP\user\User;

require dirname(__FILE__) . '/bootstrap.php';

class possibleSpammersTool extends CommandLineTool
{
    private array $blockList = [];

    private function fetchBlockList($url)
    {
        try {
            $content = file_get_contents($url);
            if ($content === false) {
                throw new Exception('Failed to fetch block list.');
            }

            $this->blockList = array_filter(array_map('trim', explode("\n", $content)));
        } catch (Exception $e) {
            fwrite(STDERR, 'Warning: Could not fetch block list. Reason: ' . $e->getMessage() . "\n");
            $this->blockList = [];
        }
    }

    private function getAllJournalIds($journalDao)
    {
        $journalIterator = $journalDao->getAll();
        $allJournalIds = [];

        while ($journal = $journalIterator->next()) {
            $allJournalIds[] = $journal->getId();
        }
        return $allJournalIds;
    }

    private function toDelimitedString($array, $delimiter = ',', $quoteChar = '"', $escape = true)
    {
        if ($escape && !empty($quoteChar)) {
            $array = array_map(function ($value) use ($quoteChar) {
                return str_replace($quoteChar, $quoteChar . $quoteChar, $value);
            }, $array);
        }

        $values = array_map(function ($value) use ($quoteChar) {
            return $quoteChar . $value . $quoteChar;
        }, $array);

        return implode($delimiter, $values);
    }

    private function outputCsv($spammers)
    {
        /** @var SiteDAO */
        $siteDAO = DAORegistry::getDAO('SiteDAO');
        $locale = $siteDAO->getSite()->getPrimaryLocale();

        $csv[] = [
            'user_id', 'username', 'email', 'url', 'given_name',
            'family_name', 'affiliation', 'country', 'date_registered',
            'date_last_login', 'reason'
        ];


        foreach ($spammers as $row) {
            $user = $row[0];
            $reason = $row[1];
            $csv[] = [
                $user->getId(),
                $user->getUsername(),
                $user->getEmail(),
                $user->getUrl(),
                $user->getGivenName($locale),
                $user->getFamilyName($locale),
                $user->getAffiliation($locale),
                $user->getCountry($locale),
                $user->getDateRegistered(),
                $user->getDateLastLogin(),
                $reason
            ];
        }
        foreach ($csv as $line) {
            echo $this->toDelimitedString($line) . "\n";
        }
    }

    private function checkForNoJournalAssociation($allJournalIds, $user)
    {
        foreach ($allJournalIds as $journalId) {
            if (Repo::userGroup()->userInGroup($user->getId(), $journalId)) {
                return false;
            }
        }
        return true;
    }

    private function checkForNeverLoggedIn($user)
    {
        $lastLogin = $user->getDateLastLogin();
        $registered = $user->getDateRegistered();
        return $lastLogin === $registered;
    }

    private function checkForValidRole($allJournalIds, $user)
    {
        $roles = [
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SITE_ADMIN,
            Role::ROLE_ID_SUB_EDITOR,
            Role::ROLE_ID_REVIEWER,
            Role::ROLE_ID_ASSISTANT,
            Role::ROLE_ID_SUBSCRIPTION_MANAGER,
        ];

        foreach ($allJournalIds as $journalId) {
            if ($user->hasRole($roles, $journalId)) {
                return true;
            }
        }
        return false;
    }

    private function checkForUserURL($user)
    {
        $userUrl = $user->getUrl();
        $exceptions = [
            '://orcid.org/',
            '://www.orcid.org/',
            '://doi.org/',
            '://www.doi.org/',
            '://dx.doi.org/',
            '://www.dx.doi.org/',
            '://publons.com/',
            '://www.publons.com/',
        ];
        foreach ($exceptions as $exception) {
            if (strpos($userUrl, $exception) !== false) {
                return false;
            }
        }
        return (bool)preg_match('~[0-9]+~', $userUrl);
    }

    private function checkForBlockedEmail($user)
    {
        $email = $user->getEmail();
        $domain = substr(strrchr($email, '@'), 1);

        if (!$domain || empty($this->blockList)) {
            return false;
        }

        return in_array($domain, $this->blockList, true);
    }

    private function checkForDisabledForLong(User $user): bool
    {
        $disabled = $user->getDisabled();
        if ($disabled) {
            $disabledSince = strtotime($user->getDateRegistered());
            $now = time();
            $diff = $now - $disabledSince;

            // Check if the user has been disabled for more than 15 days
            if ($diff > 60 * 60 * 24 * 15) {
                return true;
            }
        }
        return false;
    }


    public function execute()
    {
        $blockListUrl = 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/refs/heads/main/disposable_email_blocklist.conf';
        $this->fetchBlockList($blockListUrl);

        $journalDao = Application::getContextDAO();

        $possibleSpammers = [];

        $allJournalIds = $this->getAllJournalIds($journalDao);
        $allUsers = Repo::user()->getCollector()->getMany();

        foreach ($allUsers as $user) {
            $isDisableForLong = $this->checkForDisabledForLong($user);
            $isOrphan = $this->checkForNoJournalAssociation($allJournalIds, $user);
            $neverLoggedIn = $this->checkForNeverLoggedIn($user);
            $hasValidRole = $this->checkForValidRole($allJournalIds, $user);
            $hasUrl = $this->checkForUserURL($user);
            $isBlockedEmail = $this->checkForBlockedEmail($user);

            $reason = '';
            $reason .= ($isDisableForLong ? 'D' : '');
            $reason .= ($neverLoggedIn ? 'N' : '');
            $reason .= ($isOrphan ? 'O' : '');
            $reason .= ($hasUrl ? 'U' : '');
            $reason .= ($isBlockedEmail ? 'B' : '');

            if (($isOrphan || $neverLoggedIn || $hasUrl || $isDisableForLong || $isBlockedEmail) && !$hasValidRole) {
                $possibleSpammers[$user->getId()] = [$user, $reason];
            }
        }

        ksort($possibleSpammers);

        $this->outputCsv($possibleSpammers);

        exit();
    }
}

// Hides warning from error exit
error_reporting(E_ERROR | E_PARSE);

$tool = new possibleSpammersTool($argv ?? []);
$tool->execute();
