<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'ce_kickstarter',
    'description' => 'A kickstarter for content elements',
    'category' => 'misc',
    'author' => 'Michael Paffrath',
    'author_email' => 'michael.paffrath@gmail.com',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'LEPAFF\\CeKickstarter\\' => 'Classes/',
        ],
    ],
];
