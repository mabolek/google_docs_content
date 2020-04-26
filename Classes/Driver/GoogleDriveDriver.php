<?php


namespace GeorgRinger\GoogleDocsContent\Driver;


use GeorgRinger\GoogleDocsContent\Api\Client;
use GuzzleHttp\Psr7\StreamWrapper;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\PathUtility;

class GoogleDriveDriver extends AbstractHierarchicalFilesystemDriver
{
    const DRIVER_TYPE = 'GeorgRingerGoogleDrive';

    const EXTENSION_KEY = 'google_docs_content';

    const EXTENSION_NAME = 'Google Docs Content';

    /**
     * @var Client
     */
    protected $googleDriveClient = null;

    /**
     * Stream wrapper protocol: Will be set in the constructor
     *
     * @var string
     */
    protected $streamWrapperProtocol = '';

    /**
     * The identifier map used for renaming
     *
     * @var array
     */
    protected $identifierMap = [];

    /**
     * Object meta data is cached here as array or null
     * $identifier => [meta info as array]
     *
     * @var array[]
     */
    protected $metaInfoCache = [];

    /**
     * Array of list queries
     *
     * @var array[]
     */
    protected $listQueryCache = [];

    /**
     * Object permissions are cached here in subarrays like:
     * $identifier => ['r' => bool, 'w' => bool]
     *
     * @var array
     */
    protected $objectPermissionsCache = [];

    /**
     * Processing folder
     *
     * @var string
     */
    protected $processingFolder = '';

    /**
     * Default processing folder
     *
     * @var string
     */
    protected $processingFolderDefault = '_processed_';

    /**
     * @var ResourceStorage
     */
    protected $storage = null;

    /**
     * @var array
     */
    protected static $settings = null;

    /**
     * Additional fields to request from Google API
     *
     * @var array
     */
    protected $additionalFields = [
        //'fields' => ['createdTime'=>'','id'=>'','mimeType'=>'','modifiedTime'=>'','name'=>'','parents'=>'','size'=>'']
        'fields' => 'files/createdTime,files/id,files/mimeType,files/modifiedTime,files/name,files/parents,files/size'
    ];

    /**
     * @var array
     */
    protected $temporaryPaths = [];

    public function __construct(array $configuration = [], Client $googleDriveClient = null)
    {
        parent::__construct($configuration);
        // The capabilities default of this driver. See CAPABILITY_* constants for possible values
        $this->capabilities =
            ResourceStorage::CAPABILITY_BROWSABLE
            | ResourceStorage::CAPABILITY_WRITABLE;
        $this->streamWrapperProtocol = 'googleDrive-' . substr(md5(uniqid()), 0, 7);
        $this->googleDriveClient = $googleDriveClient;
    }

    public function __destruct()
    {
        foreach ($this->temporaryPaths as $temporaryPath) {
            @unlink($temporaryPath);
        }
    }

    public function processConfiguration()
    {
    }

    public function initialize()
    {
        $this->initializeClient();
    }

    public function mergeConfigurationCapabilities($capabilities)
    {
        $this->capabilities &= $capabilities;
        return $this->capabilities;
    }

    public function getRootLevelFolder()
    {
        return $this->configuration['rootIdentifier'] ?? 'root';
    }

    public function getDefaultFolder()
    {
        return $this->getRootLevelFolder();
    }

    public function getPublicUrl($identifier)
    {
        return '';
    }

    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false)
    {
        // TODO: Implement createFolder() method.
    }

    public function renameFolder($folderIdentifier, $newName)
    {
        // TODO: Implement renameFolder() method.
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false)
    {
        // TODO: Implement deleteFolder() method.
    }

    protected function getObjectByIdentifier($identifier)
    {
        if ($identifier === null) {
            return null;
        }

        if ($identifier === '') {
            $identifier = $this->getRootLevelFolder();
        }

        if (isset($this->metaInfoCache[$identifier])) {
            return $this->metaInfoCache[$identifier];
        }

        $googleClient = $this->googleDriveClient->getClient();
        $service = new \Google_Service_Drive($googleClient);

        try {
            $record = $service->files->get($identifier, ['fields' => 'createdTime,id,mimeType,modifiedTime,name,parents,size']);
        } catch (\Google_Service_Exception $e) {
            if ($e->getCode() === 404) {
                return null;
            }

            throw $e;
        }

        if (!$record instanceof \Google_Service_Drive_DriveFile) {
            return null;
        }

        $this->metaInfoCache[$identifier] = $record;

        return $record;
    }

    protected function objectExists($identifier, bool $isFolder = false)
    {
        $record = $this->getObjectByIdentifier($identifier);

        if ($record === null) {
            return false;
        }

        if (!$isFolder || ($isFolder && $record['mimeType'] === 'application/vnd.google-apps.folder')) {
            return true;
        }

        return false;
    }

    public function fileExists($fileIdentifier)
    {
        return $this->objectExists($fileIdentifier);
    }

    public function folderExists($folderIdentifier)
    {
        return $this->objectExists($folderIdentifier, true);
    }

    public function isFolderEmpty($folderIdentifier)
    {
        return $this->countFilesInFolder($folderIdentifier) + $this->countFoldersInFolder($folderIdentifier) > 0;
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true)
    {
        // TODO: Implement addFile() method.
    }

    public function createFile($fileName, $parentFolderIdentifier)
    {
        // TODO: Implement createFile() method.
    }

    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName)
    {
        // TODO: Implement copyFileWithinStorage() method.
    }

    public function renameFile($fileIdentifier, $newName)
    {
        // TODO: Implement renameFile() method.
    }

    public function replaceFile($fileIdentifier, $localFilePath)
    {
        // TODO: Implement replaceFile() method.
    }

    public function deleteFile($fileIdentifier)
    {
        // TODO: Implement deleteFile() method.
    }

    public function hash($fileIdentifier, $hashAlgorithm)
    {
        return $this->hashIdentifier($fileIdentifier);
    }

    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        // TODO: Implement moveFileWithinStorage() method.
    }

    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        // TODO: Implement moveFolderWithinStorage() method.
    }

    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName)
    {
        // TODO: Implement copyFolderWithinStorage() method.
    }

    public function getFileContents($fileIdentifier)
    {
        // TODO: Implement getFileContents() method.
    }

    public function setFileContents($fileIdentifier, $contents)
    {
        // TODO: Implement setFileContents() method.
    }

    public function fileExistsInFolder($fileName, $folderIdentifier)
    {
        // TODO: Implement fileExistsInFolder() method.
    }

    public function folderExistsInFolder($folderName, $folderIdentifier)
    {
        // TODO: Implement folderExistsInFolder() method.
    }

    public function getFileForLocalProcessing($fileIdentifier, $writable = true)
    {
        // TODO: Implement getFileForLocalProcessing() method.
    }

    public function getPermissions($identifier)
    {
        return ['r' => true, 'w' => false];
    }

    public function dumpFileContents($identifier)
    {
        // TODO: Implement dumpFileContents() method.
    }

    public function isWithin($folderIdentifier, $identifier)
    {
        // TODO: Implement isWithin() method.
    }

    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = [])
    {
        $record = $this->metaInfoCache[$fileIdentifier];

        $metaInfo = [
            'name' => $record['name'],
            'identifier' => $record['id'],
            'ctime' => $this->convertGoogleDateTimeStringToTimestamp($record['createdTime'])->getTimestamp(),
            'mtime' => $this->convertGoogleDateTimeStringToTimestamp($record['modifiedTime'])->getTimestamp(),
            'identifier_hash' => $this->hashIdentifier($record['id']),
            'folder_hash' => $this->hashIdentifier($record['parents'][0]['id'] ?? 'root'),
            'extension' => PathUtility::pathinfo($record['name'], PATHINFO_EXTENSION),
            'storage' => $this->storageUid,
        ];

        if (count($propertiesToExtract) > 0) {
            $metaInfo = array_intersect_key($metaInfo, array_flip($propertiesToExtract));
        }

        return $metaInfo;
    }

    public function getFolderInfoByIdentifier($folderIdentifier)
    {
        if ($folderIdentifier === '') {
            $folderIdentifier = $this->getRootLevelFolder();
        }

        $record = $this->getObjectByIdentifier($folderIdentifier);

        $metaInfo = [
            'name' => $record['name'],
            'identifier' => $record['id'],
            'storage' => $this->storageUid,
        ];

        return $metaInfo;
    }

    public function getFileInFolder($fileName, $folderIdentifier)
    {
        // TODO: Implement getFileInFolder() method.
    }

    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        if ($folderIdentifier === '') {
            $folderIdentifier = $this->getRootLevelFolder();
        }

        $parameters = $this->additionalFields;
        $parameters['q'] = '\'' . $folderIdentifier . '\' in parents and trashed = false and not mimeType=\'application/vnd.google-apps.folder\'';
        $parametersHash = md5(serialize($parameters));

        if (isset($this->listQueryCache[$parametersHash])) {
            $records = $this->listQueryCache[$parametersHash];
        } else {
            $googleClient = $this->googleDriveClient->getClient();
            $service = new \Google_Service_Drive($googleClient);
            $records = $service->files->listFiles($parameters)->getFiles();

            foreach ($records as $record) {
                $this->metaInfoCache[$record->id] = $record;
            }

            $this->listQueryCache[$parametersHash] = $records;
        }

        $folders = [];

        foreach ($records as $record) {
            $folders[$record->id] = $record->id;
        }

        return $folders;
    }

    public function getFolderInFolder($folderName, $folderIdentifier)
    {
        // TODO: Implement getFolderInFolder() method.
    }

    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ) {
        if ($folderIdentifier === '') {
            $folderIdentifier = $this->getRootLevelFolder();
        }

        $parameters = $this->additionalFields;
        $parameters['q'] = '\'' . $folderIdentifier . '\' in parents and trashed = false and mimeType=\'application/vnd.google-apps.folder\'';
        $parametersHash = md5(serialize($parameters));

        if (isset($this->listQueryCache[$parametersHash])) {
            $records = $this->listQueryCache[$parametersHash];
        } else {
            $googleClient = $this->googleDriveClient->getClient();
            $service = new \Google_Service_Drive($googleClient);
            $records = $service->files->listFiles($parameters)->getFiles();

            foreach ($records as $record) {
                $this->metaInfoCache[$record->id] = $record;
            }

            $this->listQueryCache[$parametersHash] = $records;
        }

        $folders = [];

        foreach ($records as $record) {
            $folders[$record->id] = $record->id;
        }

        return $folders;
    }

    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = [])
    {
        if ($folderIdentifier === '') {
            $folderIdentifier = $this->getRootLevelFolder();
        }

        $parameters = $this->additionalFields;
        $parameters['q'] = '\'' . $folderIdentifier . '\' in parents and trashed = false and not mimeType=\'application/vnd.google-apps.folder\'';
        $parametersHash = md5(serialize($parameters));

        if (isset($this->listQueryCache[$parametersHash])) {
            $records = $this->listQueryCache[$parametersHash];
        } else {
            $googleClient = $this->googleDriveClient->getClient();
            $service = new \Google_Service_Drive($googleClient);
            $records = $service->files->listFiles($parameters)->getFiles();

            foreach ($records as $record) {
                $this->metaInfoCache[$record->id] = $record;
            }

            $this->listQueryCache[$parametersHash] = $records;
        }

        return count($records);
    }

    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = [])
    {
        if ($folderIdentifier === '') {
            $folderIdentifier = $this->getRootLevelFolder();
        }

        $parameters = $this->additionalFields;
        $parameters['q'] = '\'' . $folderIdentifier . '\' in parents and trashed = false and mimeType=\'application/vnd.google-apps.folder\'';
        $parametersHash = md5(serialize($parameters));

        if (isset($this->listQueryCache[$parametersHash])) {
            $records = $this->listQueryCache[$parametersHash];
        } else {
            $googleClient = $this->googleDriveClient->getClient();
            $service = new \Google_Service_Drive($googleClient);
            $records = $service->files->listFiles($parameters)->getFiles();

            foreach ($records as $record) {
                $this->metaInfoCache[$record->id] = $record;
            }

            $this->listQueryCache[$parametersHash] = $records;
        }

        return count($records);
    }

    public function getParentFolderIdentifierOfIdentifier($identifier)
    {
        if ($identifier === $this->getRootLevelFolder()) {
            throw new ResourceDoesNotExistException();
        }

        $record = $this->getObjectByIdentifier($identifier);

        if ($record === null || count($record['parents']) === 0) {
            throw new ResourceDoesNotExistException();
        }

        return $record['parents'][0];
    }

    protected function initializeClient()
    {
        if (!$this->googleDriveClient) {
            $this->googleDriveClient = new Client();
            StreamWrapper::register($this->googleDriveClient, $this->streamWrapperProtocol);
        }

        return $this;
    }

    protected function convertGoogleDateTimeStringToTimestamp($dateTimeString) {
        return \DateTime::createFromFormat('Y-m-d\TH:i:s.???\Z', $dateTimeString);
    }
}