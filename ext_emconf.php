<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'ce_kickstarter',
    'description' => 'asdf',
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
