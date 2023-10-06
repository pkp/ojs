<?php

namespace APP\orcid;

use APP\author\Author;
use APP\core\Application;
use APP\facades\Repo;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\Core;

class OrcidManager
{
    // TODO: Overall todos left
    //    [] Move locales to core and remove "plugins.generic" prefix
    public const ORCID_URL = 'https://orcid.org/';
    public const ORCID_URL_SANDBOX = 'https://sandbox.orcid.org/';
    public const ORCID_API_URL_PUBLIC = 'https://pub.orcid.org/';
    public const ORCID_API_URL_PUBLIC_SANDBOX = 'https://pub.sandbox.orcid.org/';
    public const ORCID_API_URL_MEMBER = 'https://api.orcid.org/';
    public const ORCID_API_URL_MEMBER_SANDBOX = 'https://api.sandbox.orcid.org/';

    public const OAUTH_TOKEN_URL = 'oauth/token';

    public const ORCID_API_SCOPE_PUBLIC = '/authenticate';
    public const ORCID_API_SCOPE_MEMBER = '/activities/update';

    public const ENABLED = 'orcidEnabled';
    public const PROFILE_API_PATH = 'orcidProfileAPIPath';
    public const CLIENT_ID = 'orcidClientId';
    public const CLIENT_SECRET = 'orcidClientSecret';
    public const SEND_MAIL_TO_AUTHORS_ON_PUBLICATION = 'orcidSendMailToAuthorsOnPublication';
    public const LOG_LEVEL = 'orcidLogLevel';
    public const IS_SANDBOX = 'orcidIsSandBox';
    public const COUNTRY = 'orcidCountry';
    public const CITY = 'orcidCity';
    public const API_TYPE = 'orcidApiType';
    public const API_PUBLIC_PRODUCTION = 'publicProduction';
    public const API_PUBLIC_SANDBOX = 'publicSandbox';
    public const API_MEMBER_PRODUCTION = 'memberProduction';
    public const API_MEMBER_SANDBOX = 'memberSandbox';

    /**
     * Check if there exist a valid orcid configuration section in the global config.inc.php of OJS.
     *
     * @return boolean True, if the config file has api_url, client_id and client_secret set in an [orcid] section
     */
    public static function isGloballyConfigured(): bool
    {
        $site = Application::get()->getRequest()->getSite();
        $apiType = $site->getData(self::API_TYPE);
        $clientId = $site->getData(self::CLIENT_ID);
        $clientSecret = $site->getData(self::CLIENT_SECRET);
        return isset($apiType) && trim($apiType) && isset($clientId) && trim($clientId) &&
            isset($clientSecret) && trim($clientSecret);
    }

    /**
     * Return a string of the ORCiD SVG icon
     *
     */
    public static function getIcon(): string
    {
        $path = Core::getBaseDir() . '/' . PKP_LIB_PATH . '/templates/images/orcid.svg';
        return file_exists($path) ? file_get_contents($path) : '';
    }

    public static function isEnabled(?Context $context = null): bool
    {
        if ($context === null) {
            $context = Application::get()->getRequest()->getContext();
        }

        return (bool) $context?->getData(self::ENABLED);
    }

    public static function getOrcidUrl(?Context $context = null): string
    {
        if (self::isGloballyConfigured()) {
            $apiType = Application::get()->getRequest()->getSite()->getData(self::API_TYPE);
        } else {
            if ($context === null) {
                $context = Application::get()->getRequest()->getContext();
            }
            $apiType = $context->getData(self::API_TYPE);
        }
        return in_array($apiType, [self::API_PUBLIC_PRODUCTION, self::API_MEMBER_PRODUCTION]) ? self::ORCID_URL : self::ORCID_URL_SANDBOX;
    }

    public static function getApiPath(?Context $context = null): string
    {
        if (self::isGloballyConfigured()) {
            $apiType = Application::get()->getRequest()->getSite()->getData(OrcidManager::API_TYPE);
        } else {
            if ($context === null) {
                $context = Application::get()->getRequest()->getContext();
            }
            $apiType = $context->getData(OrcidManager::API_TYPE);
        }

        return match ($apiType) {
            self::API_PUBLIC_SANDBOX => self::ORCID_API_URL_PUBLIC_SANDBOX,
            self::API_MEMBER_PRODUCTION => self::ORCID_API_URL_MEMBER,
            self::API_MEMBER_SANDBOX => self::ORCID_API_URL_MEMBER_SANDBOX,
            default => self::ORCID_API_URL_PUBLIC,
        };
    }

    public static function isSandbox(?Context $context = null): bool
    {
        if (self::isGloballyConfigured()) {
            $apiType = Application::get()->getRequest()->getSite()->getData(OrcidManager::API_TYPE);
        } else {
            if ($context === null) {
                $context = Application::get()->getRequest()->getContext();
            }
            $apiType = $context->getData(OrcidManager::API_TYPE);
        }

        return in_array($apiType, [self::API_PUBLIC_SANDBOX, self::API_MEMBER_SANDBOX]);
    }

    /**
     * TODO: update as needed for new API
     *
     * @param string $handlerMethod Previously: containting a valid method of the OrcidProfileHandler
     * @param array $redirectParams Additional request parameters for the redirect URL
     *
     * @throws \Exception
     */
    public static function buildOAuthUrl(string $handlerMethod, array $redirectParams): string
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if ($context === null) {
            throw new \Exception('OAuth URLs should can only be made in a Context, not site wide');
        }

        $scope = self::isMemberApiEnabled() ? self::ORCID_API_SCOPE_MEMBER : self::ORCID_API_SCOPE_PUBLIC;

        // TODO: This is the previous URL. Will be an API URL in new iteration
        // We need to construct a page url, but the request is using the component router.
        // Use the Dispatcher to construct the url and set the page router.
        $redirectUrl = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_PAGE,
            null,
            'orcid',
            $handlerMethod,
            null,
            $redirectParams
        );

        return self::getOauthPath() . 'authorize?' . http_build_query(
            [
                'client_id' => self::getClientId($context),
                'response_type' => 'code',
                'scope' => $scope,
                'redirect_uri' => $redirectUrl]
        );
    }

    public static function getCity(?Context $context = null): string
    {
        if ($context === null) {
            $context = Application::get()->getRequest()->getContext();
        }

        return $context->getData(self::CITY) ?? '';
    }

    public static function getCountry(?Context $context = null): string
    {
        if ($context === null) {
            $context = Application::get()->getRequest()->getContext();
        }
        return $context->getData(OrcidManager::COUNTRY) ?? '';
    }

    public static function isMemberApiEnabled(?Context $context = null): bool
    {
        if (self::isGloballyConfigured()) {
            $apiType = Application::get()->getRequest()->getSite()->getData(self::API_TYPE);
        } else {
            if ($context === null) {
                $context = Application::get()->getRequest()->getContext();
            }
            $apiType = $context->getData(OrcidManager::API_TYPE);
        }

        if (in_array($apiType, [self::API_MEMBER_PRODUCTION, self::API_MEMBER_SANDBOX])) {
            return true;
        } else {
            return false;
        }
    }

    public static function getLogLevel(?Context $context = null): string
    {
        if ($context === null) {
            $context = Application::get()->getRequest()->getContext();
        }

        return $context->getData(OrcidManager::LOG_LEVEL) ?? 'ERROR';
    }

    public static function shouldSendMailToAuthors(?Context $context = null): bool
    {
        if ($context === null) {
            $context = Application::get()->getRequest()->getContext();
        }

        return $context->getData(OrcidManager::SEND_MAIL_TO_AUTHORS_ON_PUBLICATION) ?? false;
    }

    public static function getOauthPath(): string
    {
        return self::getOrcidUrl() . 'oauth/';
    }

    public static function getClientId(?Context $context = null): string
    {
        if (self::isGloballyConfigured()) {
            return Application::get()->getRequest()->getSite()->getData(self::CLIENT_ID);
        } else {
            if ($context === null) {
                $context = Application::get()->getRequest()->getContext();
            }

            return $context->getData(self::CLIENT_ID) ?? '';
        }
    }

    public static function getClientSecret(?Context $context = null): string
    {
        if (self::isGloballyConfigured()) {
            return Application::get()->getRequest()->getSite()->getData(self::CLIENT_SECRET);
        } else {
            if ($context === null) {
                $context = Application::get()->getRequest()->getContext();
            }

            return $context->getData(self::CLIENT_SECRET) ?? '';
        }
    }

    /**
     * Remove all data fields, which belong to an ORCID access token from the
     * given Author object. Also updates fields in the db.
     *
     * @param bool $updateAuthor If true, update the author fields in the database.
     *      Use only if not called from a function, which will already update the author.
     */
    public static function removeOrcidAccessToken(Author $author, bool $updateAuthor = false): void
    {
        $author->setData('orcidAccessToken', null);
        $author->setData('orcidAccessScope', null);
        $author->setData('orcidRefreshToken', null);
        $author->setData('orcidAccessExpiresOn', null);
        $author->setData('orcidSandbox', null);

        if ($updateAuthor) {
            Repo::author()->dao->update($author);
        }
    }

    public static function logInfo(string $message): void
    {
        if (self::getLogLevel() !== 'INFO') {
            return;
        }
        self::writeLog($message, 'INFO');
    }
    public static function logError(string $message): void
    {
        if (self::getLogLevel() !== 'ERROR') {
            return;
        }
        self::writeLog($message, 'ERROR');
    }

    private static function writeLog(string $message, string $level): void
    {
        $fineStamp = date('Y-m-d H:i:s') . substr(microtime(), 1, 4);
        $logFilePath = Config::getVar('files', 'files_dir') . '/orcid.log';
        error_log("{$fineStamp} {$level} {$message}\n", 3, $logFilePath);
    }
}
