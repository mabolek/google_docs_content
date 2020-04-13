<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['google_docs_content'] =
    \GeorgRinger\GoogleDocsContent\Hooks\DataHandlerHook::class;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers'][\GeorgRinger\GoogleDocsContent\Driver\GoogleDriveDriver::DRIVER_TYPE] = [
    'class' => \GeorgRinger\GoogleDocsContent\Driver\GoogleDriveDriver::class,
    'flexFormDS' => 'FILE:EXT:google_docs_content/Configuration/FlexForm/GoogleDriveStorageConfigurationFlexForm.xml',
    'label' => 'Google Drive',
    'shortName' => \GeorgRinger\GoogleDocsContent\Driver\GoogleDriveDriver::EXTENSION_NAME,
];
