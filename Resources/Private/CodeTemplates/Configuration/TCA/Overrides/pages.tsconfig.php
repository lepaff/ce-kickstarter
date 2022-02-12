<?php
defined('TYPO3_MODE') || die();

call_user_func(function()
{
    /**
     * Default PageTS for Products
     */
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
        '###EXT_KEY###',
        'Configuration/TsConfig/Page/All.tsconfig',
        '###EXT_UPPERCAMELCASE###'
    );
});
