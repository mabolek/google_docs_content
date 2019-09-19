<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Google Docs Content',
    'description' => '',
    'category' => 'backend',
    'author' => 'Georg Ringer',
    'author_email' => 'mail@ringer.it',
    'state' => 'alpha',
    'clearCacheOnLoad' => true,
    'version' => '0.1.0',
    'constraints' =>
        [
            'depends' => [
                'typo3' => '9.5.9-10.9.90',
            ],
            'conflicts' => [],
            'suggests' => [],
        ]
];
