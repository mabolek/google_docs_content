<?php
declare(strict_types=1);

namespace GeorgRinger\GoogleDocsContent\Api;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataHandlerHook
{

    /**
     * Fill path_segment/slug field with title
     *
     * @param string $status
     * @param string $table
     * @param string|int $id
     * @param array $fieldArray
     * @param DataHandler $parentObject
     */
    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, DataHandler $parentObject)
    {
        // @todo check for doktype
        if ($table === 'pages') {

            $googleDocsId = '';
            if ($status === 'new' && isset($fieldArray['google_docs_id'])) {
                $googleDocsId = $fieldArray['google_docs_id'];
            } elseif (!empty($fieldArray['google_docs_force_update'])) {
                $page = BackendUtility::getRecord($table, $id);
                if ($page && $page['google_docs_id']) {
                    $googleDocsId = $page['google_docs_id'];
                }
            }

            if ($googleDocsId) {

                $api = GeneralUtility::makeInstance(GoogleDocsApi::class);
                $fieldArray['google_docs_content'] = $api->getDoc($googleDocsId);
            }

            $fieldArray['google_docs_force_update'] = 0;
        }
    }

}
