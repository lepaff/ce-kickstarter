<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "lepaff/ce_kickstarter" by Michael Paffrath <michael.paffrath@gmail.com>.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace LEPAFF\CeKickstarter\Command;

use LEPAFF\CeKickstarter\Environment\Variables;
use LEPAFF\CeKickstarter\Exception\EmptyAnswerException;
use LEPAFF\CeKickstarter\Exception\InvalidPackageNameException;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract command with basic functionalities
 */
abstract class AbstractCommand extends Command
{
    protected const CLASSES_DIRECTORY              = 'Classes/';
    protected const CODE_TEMPLATES_DIRECTORY       = 'Resources/Private/CodeTemplates/';
    protected const CONFIGURATION_DIRECTORY        = 'Configuration/';
    protected const CONTENT_ELEMENTS_DIRECTORY     = 'ContentElements/';
    protected const GITKEEPFILE                    = '.gitkeep';
    protected const LAYOUTS_DIRECTORY              = 'Layouts/';
    protected const OVERRIDES_DIRECTORY            = 'TCA/Overrides/';
    protected const PARTIALS_DIRECTORY             = 'Partials/';
    protected const PRIVATE_DIRECTORY              = 'Resources/Private/';
    protected const SETUP_DIRECTORY                = 'Setup/';
    protected const TSCONFIG_PAGE_DIRECTORY        = 'TsConfig/Page/';
    protected const TEMPLATES_CE_DIRECTORY         = 'Resources/Private/Templates/ContentElements/';
    protected const TEMPLATES_DIRECTORY            = 'Templates/';
    protected const TYPOSCRIPT_DIRECTORY           = 'TypoScript/';
    protected const TYPOSCRIPT_SETUP_DIRECTORY     = 'TypoScript/Setup/';
    protected const TYPOSCRIPT_TTCONTENT_DIRECTORY = 'tt_content/';

    protected function getProposalFromEnvironment(string $key, string $default = ''): string
    {
        return Variables::has($key) ? Variables::get($key) : $default;
    }

    /**
     * @param mixed|string $answer
     */
    public function answerRequired($answer): string
    {
        $answer = (string)$answer;

        if (trim($answer) === '') {
            throw new EmptyAnswerException('Answer can not be empty.', 1639664759);
        }

        return $answer;
    }

    /**
     * @param mixed|string $answer
     *
     * @see https://getcomposer.org/doc/04-schema.md#name
     */
    public function validatePackageKey($answer): string
    {
        $answer = $this->answerRequired($answer);

        if (!preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$/', $answer)) {
            throw new InvalidPackageNameException(
                'Package key does not match the allowed pattern. More information are available on https://getcomposer.org/doc/04-schema.md#name.',
                1639664760
            );
        }

        return $answer;
    }

    /**
     * @param mixed|string $answer
     *
     * @see https://getcomposer.org/doc/04-schema.md#name
     */
    public function validateElementKey($answer): string
    {
        $answer = $this->answerRequired($answer);

        if (!preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*$/', $answer)) {
            throw new InvalidPackageNameException(
                'Element key does not match the allowed pattern. More information @todo',
                1639664760
            );
        }

        return $answer;
    }

    /**
     * @param string $destPath
     * @param string $id
     * @param string $value
     *
     * Creates translation file if missing
     * Adds a translation node to translation file
     */
    public function addTranslation($io, $destPath, $id, $value) {
        $destinationFolder = $destPath . 'Resources/Private/Language/';
        if (!file_exists($destinationFolder)) {
            try {
                GeneralUtility::mkdir_deep($destinationFolder);
            } catch (\Exception $e) {
                echo 'Writing language folder failed!';
                $io->error('Writing language folder failed!');

                return false;
            }
        }
        $destination = $destinationFolder . 'Tca.xlf';
        if (file_exists($destination)) {
            $langFile = GeneralUtility::getUrl($destination);
            $langFileXml = simplexml_load_string($langFile);
            if (count($langFileXml->xpath('file/body/trans-unit[@id="' . $id . '"]')) > 0) {
                $tu = $langFileXml->xpath('file/body/trans-unit[@id="' . $id . '"]')[0];
                $tu->source = $value;
            } else {
                $tu = $langFileXml->file->body->addChild('trans-unit','');
                $tu->addAttribute('id', $id);
                $tu->addAttribute('resname', $id);
                $tu->addChild('source', $value);
            }
        } else {
            $currentExtPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ce_kickstarter');
            $xmlString = GeneralUtility::getUrl($currentExtPath . 'Resources/Private/CodeTemplates/Resources/Private/Language/Tca.xlf');
            $langFileXml = simplexml_load_string($xmlString);
            $tu = $langFileXml->file->body->addChild('trans-unit','');
            $tu->addAttribute('id', $id);
            $tu->addAttribute('resname', $id);
            $tu->addChild('source', $value);
        }

        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($langFileXml->asXML());
        $dom->save($destination);
    }
}
