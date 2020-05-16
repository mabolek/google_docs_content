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
        if (!$this->objectExists($fileIdentifier)) {
            return null;
        }

        $record = $this->getObjectByIdentifier($fileIdentifier);

        $metaInfo = [
            'name' => $record['name'],
            'identifier' => $record['id'],
            'ctime' => $this->convertGoogleDateTimeStringToTimestamp($record['createdTime']),
            'mtime' => $this->convertGoogleDateTimeStringToTimestamp($record['modifiedTime']),
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

    /**
     * Get files or folders within a folder
     *
     * Returns a list of files or folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param int $start
     * @param int $numberOfItems
     * @param bool $recursive
     * @param array $nameFilterCallbacks callbacks for filtering the items
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @param bool $isFolder Returns a list of folders if true. Otherwise, files.
     * @return array of FileIdentifiers
     */
    protected function getObjectsInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $nameFilterCallbacks = [],
        $sort = '',
        $sortRev = false,
        $isFolder = false
    )
    {
        if ($folderIdentifier === '' && $isFolder === true) {
            $folderIdentifier = $this->getRootLevelFolder();
        } elseif ($folderIdentifier === '') {
            return [];
        }

        $parameters = $this->additionalFields;
        $parameters['q'] = ' \'' . $folderIdentifier . '\' in parents and trashed = false and ';
        if (!$isFolder) {
            $parameters['q'] .= ' not ';
        }
        $parameters['q'] .= ' mimeType=\'application/vnd.google-apps.folder\' ';

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

        $objects = [];

        foreach ($records as $record) {
            if (
                !$this->applyFilterMethodsToDirectoryItem(
                    $nameFilterCallbacks,
                    $record['name'],
                    $record['id'],
                    $folderIdentifier
                )
            ) {
                continue;
            }

            $objects[$record->id] = $record->id;
        }

        return $objects;
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
        return $this->getObjectsInFolder(
            $folderIdentifier,
            $start,
            $numberOfItems,
            $recursive,
            $filenameFilterCallbacks,
            $sort,
            $sortRev
        );
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
        return $this->getObjectsInFolder(
            $folderIdentifier,
            $start,
            $numberOfItems,
            $recursive,
            $folderNameFilterCallbacks,
            $sort,
            $sortRev,
            true
        );
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

    /**
     * Applies a set of filter methods to a file name to find out if it should be used or not. This is e.g. used by
     * directory listings.
     *
     * @param array $filterMethods The filter methods to use
     * @param string $itemName
     * @param string $itemIdentifier
     * @param string $parentIdentifier
     * @throws \RuntimeException
     * @return bool
     */
    protected function applyFilterMethodsToDirectoryItem(
        array $filterMethods,
        $itemName,
        $itemIdentifier,
        $parentIdentifier
    )
    {
        foreach ($filterMethods as $filter) {
            if (is_array($filter)) {
                $result = call_user_func($filter, $itemName, $itemIdentifier, $parentIdentifier, [], $this);
                // We have to use -1 as the "don't include" return value, as call_user_func() will return FALSE
                // if calling the method succeeded and thus we can't use that as a return value.
                if ($result === -1) {
                    return false;
                } elseif ($result === false) {
                    throw new \RuntimeException('Could not apply file/folder name filter ' . $filter[0] . '::' . $filter[1]);
                }
            }
        }
        return true;
    }

    /**
     * Returns a unix timestamp from a Google DateTime string (zero if invalid)
     *
     * Example input: 2014-06-24T22:39:34.652Z
     *
     * @param $dateTimeString
     * @return int
     */
    protected function convertGoogleDateTimeStringToTimestamp($dateTimeString): int
    {
        $dateTime = $this->convertGoogleDateTimeStringToDateTime($dateTimeString);

        if ($dateTime === null) {
            return 0;
        }

        return $dateTime->getTimestamp();
    }

    /**
     * Returns a DateTime object from a Google DateTime string (null if invalid)
     *
     * Example input: 2014-06-24T22:39:34.652Z
     *
     * @param $dateTimeString
     * @return \DateTime|null
     */
    protected function convertGoogleDateTimeStringToDateTime($dateTimeString): ?\DateTime
    {
        return \DateTime::createFromFormat('Y-m-d\TH:i:s.???\Z', $dateTimeString) ?? null;
    }
}