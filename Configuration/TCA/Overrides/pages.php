<?php


call_user_func(
    function ($table) {
        $newsDoktype = 71;


        $columns = [
            'google_docs_id' => [
                'label' => 'google_docs_id',
                'config' => [
                    'type' => 'input'
                ]
            ],
            'google_docs_content' => [
                'label' => 'google_docs_content',
                'config' => [
                    'type' => 'text',
                    'readOnly' => true
                ]
            ],
            'google_docs_force_update' => [
                'label' => 'google_docs_force_update',
                'config' => [
                    'type' => 'check',
                ]
            ],

        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $columns);
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages',
            'google_docs_id,google_docs_content,google_docs_force_update');


        // Add new page type as possible select item:
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
            $table,
            'doktype',
            [
                'Google Docs Content',
                $newsDoktype
            ],
            '6',
            'after'
        );

//        // Add icon for new page type:
//        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
//            $GLOBALS['TCA']['pages'],
//            [
//                'ctrl' => [
//                    'typeicon_classes' => [
//                        $newsDoktype => 'apps-pagetree-google-docs',
//                    ],
//                ],
//            ]
//        );
    },
    'pages'
);
