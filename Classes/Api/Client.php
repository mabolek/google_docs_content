<?php
declare(strict_types=1);

namespace GeorgRinger\GoogleDocsContent\Api;

use Google_Service_Docs;
use Google_Service_Drive;
use phpDocumentor\Reflection\Types\Self_;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Client
{

    /** @var Registry */
    protected $registry;

    private const REGISTRY_KEY = 'google_docs_content';

    public const SCOPES = [
        Google_Service_Docs::DOCUMENTS_READONLY,
        Google_Service_Drive::DRIVE_FILE,
        Google_Service_Drive::DRIVE_METADATA,
        Google_Service_Drive::DRIVE];

    public function __construct()
    {
        $this->registry = GeneralUtility::makeInstance(Registry::class);
    }

    public function setAuthConfig($credentials)
    {
        $this->registry->set(self::REGISTRY_KEY, 'credentials', json_encode($credentials));
    }

    public function getAuthConfig()
    {
        $credentials = $this->registry->get(self::REGISTRY_KEY, 'credentials');
        if ($credentials) {
            return json_decode($credentials, true);
        }

        return [];
    }

    /**
     * Returns an authorized API client.
     * @return \Google_Client the authorized client object
     */
    public function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('Google Docs API PHP Quickstart');
        $client->setScopes(self::SCOPES);
        $client->setAccessType('offline');
        $client->setAuthConfig($this->getAuthConfig());

        $accessToken = $this->getToken();
        if (!$accessToken) {
            throw new \RuntimeException('No access token given', 1568871911);
        }
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $this->setToken($client->getAccessToken());
        }
        return $client;
    }

    public function getToken()
    {
        $token = $this->registry->get(self::REGISTRY_KEY, 'token');

        if ($token) {
            return json_decode($token, true);
        }
        return null;
    }

    public function setToken(array $token)
    {
        $this->registry->set(self::REGISTRY_KEY, 'token', json_encode($token));
    }

}
