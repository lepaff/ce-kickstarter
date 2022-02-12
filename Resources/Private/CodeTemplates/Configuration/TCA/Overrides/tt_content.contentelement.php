<?php

defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'LLL:EXT:###EXT_KEY###/Resources/Private/Language/Tca.xlf:contentelement.###CE_TITLE###.name',
        '###EXT_KEY###_###CE_TITLE###',
        'contentelement-###EXT_KEY###-###CE_TITLE###',
    ],
    '',
    ''
);

$GLOBALS['TCA']['tt_content']['types']['###EXT_KEY###_###CE_TITLE###'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access
    ',
];
