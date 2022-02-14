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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for creating a new TYPO3 content element
 */
class ContentelementCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setDescription('Create a TYPO3 extension');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $packageName = (string)$io->ask(
            'Enter the composer package name (e.g. "vendor/awesome")',
            null,
            [$this, 'validatePackageKey']
        );

        [$vendorName,$packageKey] = explode('/', $packageName);
        $upperCamelCaseName = ucfirst($vendorName) . ucfirst($packageKey);

        $extensionKey = (string)$io->ask(
            'Enter the extension key',
            str_replace('-', '_', $packageKey)
        );

        $contentElement = $io->ask(
            'Enter a key for the content element',
            null,
            [$this, 'validateElementKey']
        );

        $contentElementTitle = $io->ask(
            'What will the content element be called?',
            null,
            [$this, 'answerRequired']
        );

        $contentElementDescription = $io->ask(
            'Enter a short description for the content element',
            null,
            [$this, 'answerRequired']
        );

        $directory = $io->ask(
            'Where is the extension located?',
            '/LocalPackages/',
            [$this, 'answerRequired']
        );

        $io->note('Input part ready.');

        $currentExtPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ce_kickstarter');
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey)) {
            $destExtPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey);
        } else {
            $destExtPath = getcwd() . $directory . $extensionKey .'/';
        }

        $tcaOverridesFolder = $destExtPath . parent::CONFIGURATION_DIRECTORY . parent::OVERRIDES_DIRECTORY;
        $tsConfigPageFolder = $destExtPath . parent::CONFIGURATION_DIRECTORY . parent::TSCONFIG_PAGE_DIRECTORY;
        $tsSetupTtContentFolder = $destExtPath . parent::CONFIGURATION_DIRECTORY . parent::TYPOSCRIPT_SETUP_DIRECTORY . parent::TYPOSCRIPT_TTCONTENT_DIRECTORY;
        $templatesContentElementsFolder = $destExtPath . parent::TEMPLATES_CE_DIRECTORY;
        $folders = [
            $tcaOverridesFolder,
            $tsConfigPageFolder,
            $tsSetupTtContentFolder,
            $templatesContentElementsFolder
        ];
        foreach($folders as $folder) {
            if (!file_exists($folder)) {
                try {
                    GeneralUtility::mkdir_deep($folder);
                } catch (\Exception $e) {
                    $io->error('Creating of directory ' . $folder . ' failed');
                    return 1;
                }
            }
        }
        $io->note('Folder creation ready.');

        // Create/add translation(s)
        $this->addTranslation($io, $destExtPath, 'contentelement.' . $contentElement . '.name', $contentElementTitle);
        $this->addTranslation($io, $destExtPath, 'contentelement.' . $contentElement . '.description', $contentElementDescription);

        // Pages TSconfig Overrides file
        $config = [
            'templatePath' => $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::OVERRIDES_DIRECTORY,
            'templatePath' => $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::OVERRIDES_DIRECTORY,
            'templateFilename' => 'pages.tsconfig.php',
            'filename' => 'pages.tsconfig.php',
            'folder' => $tcaOverridesFolder,
            'currentExtPath' => $currentExtPath,
            'extensionKey' => $extensionKey,
            'upperCamelCaseName' => $upperCamelCaseName,
            'markers' => [
                'EXT_KEY' => $extensionKey,
                'EXT_UPPERCAMELCASE' => $upperCamelCaseName,
            ]
        ];
        if (!$this->createExtFile($io, $config)) {
            $io->error('Creating extension file "' . $config['filename'] . '" failed');
            return 1;
        }
        // TT_content TSconfig Overrides file
        $config = [
            'templatePath' => $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::OVERRIDES_DIRECTORY,
            'templateFilename' => 'tt_content.contentelement.php',
            'filename' => 'tt_content.' . $contentElement . '.php',
            'title' => $contentElement,
            'folder' => $tcaOverridesFolder,
            'currentExtPath' => $currentExtPath,
            'extensionKey' => $extensionKey,
            'upperCamelCaseName' => $upperCamelCaseName,
            'markers' => [
                'CE_TITLE' => $contentElement,
                'EXT_KEY' => $extensionKey
            ]
        ];
        if (!$this->createExtFile($io, $config)) {
            $io->error('Creating extension file "' . $config['filename'] . '" failed');
            return 1;
        }
        // TT_content TypoScript file
        $config = [
            'templatePath' => $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::TYPOSCRIPT_SETUP_DIRECTORY . parent::TYPOSCRIPT_TTCONTENT_DIRECTORY,
            'templateFilename' => 'tt_content.contentelement.typoscript',
            'filename' => 'tt_content.' . $contentElement . '.typoscript',
            'title' => $contentElement,
            'folder' => $tsSetupTtContentFolder,
            'currentExtPath' => $currentExtPath,
            'markers' => [
                'UC_CE_TITLE' => ucfirst($contentElement),
                'CE_TITLE' => $contentElement,
                'EXT_KEY' => $extensionKey
            ]
        ];
        if (!$this->createExtFile($io, $config)) {
            $io->error('Creating extension file "' . $config['filename'] . '" failed');
            return 1;
        }
        // TsConfig/Page files begin
        // Tab file
        $config = [
            'templatePath' => $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::TSCONFIG_PAGE_DIRECTORY,
            'templateFilename' => 'All.typoscript',
            'filename' => 'All.typoscript',
            'title' => $contentElement,
            'folder' => $tsConfigPageFolder,
            'currentExtPath' => $currentExtPath,
            'markers' => [
                'CE_TITLE' => $contentElement,
                'EXT_KEY' => $extensionKey
            ],
            'addLine' => '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $extensionKey . '/' . parent::CONFIGURATION_DIRECTORY . parent::TSCONFIG_PAGE_DIRECTORY . ucfirst($extensionKey) . ucfirst($contentElement) . '.typoscript">'
        ];
        if (!$this->createExtFile($io, $config)) {
            $io->error('Creating extension file "' . $config['filename'] . '" failed');
            return 1;
        }
        // Contentelement file
        $config = [
            'templatePath' => $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::TSCONFIG_PAGE_DIRECTORY,
            'templateFilename' => 'ContentElement.typoscript',
            'filename' => ucfirst($extensionKey) . ucfirst($contentElement) . '.typoscript',
            'title' => $contentElement,
            'folder' => $tsConfigPageFolder,
            'currentExtPath' => $currentExtPath,
            'markers' => [
                'CE_TITLE' => $contentElement,
                'EXT_KEY' => $extensionKey
            ]
        ];
        if (!$this->createExtFile($io, $config)) {
            $io->error('Creating extension file "' . $config['filename'] . '" failed');
            return 1;
        }
        // TsConfig/Page files end


        // Template file
        $config = [
            'templatePath' => $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::TEMPLATES_CE_DIRECTORY,
            'templateFilename' => 'CeTemplate.html',
            'filename' => ucfirst($contentElement) . '.html',
            'title' => $contentElement,
            'folder' => $templatesContentElementsFolder,
            'currentExtPath' => $currentExtPath,
            'markers' => [
                'TEMPLATE_NAME' => $destExtPath . parent::TEMPLATES_CE_DIRECTORY . ucfirst($contentElement) . '.html'
            ]
        ];
        if (!$this->createExtFile($io, $config)) {
            $io->error('Creating extension file "' . $config['filename'] . '" failed');
            return 1;
        }
        if (file_exists($destExtPath . parent::TEMPLATES_CE_DIRECTORY . parent::GITKEEPFILE)) {
            unlink($destExtPath . parent::TEMPLATES_CE_DIRECTORY . parent::GITKEEPFILE);
        }

        $io->success('Configuration files successfully generated.');
        $io->warning('Clear the Cache!');
        $io->note('If the static template of the extension is included, the content element should be usable now.');

        return 0;
    }

    protected function createExtFile($io, $config) {
        $file = $config['folder'] . $config['filename'];
        $template = $config['templatePath'] . $config['templateFilename'];
        $newfile = GeneralUtility::getUrl($template);
        if (!$newfile) {
            $io->error('Reading ' . $template . ' template file failed');
            return false;
        }
        $newfileContent = $newfile;
        foreach($config['markers'] as $markerKey => $marker) {
            $newfileContent = str_replace('###' . $markerKey . '###', $marker, $newfileContent);
        }
        if (isset($config['addLine'])) {
            $newfileContent .= '
' . $config['addLine'];
        }
        if (file_exists($file)
            && !$io->confirm('A ' . $config['filename'] . ' does already exist. Do you want to override it?', true)
        ) {
            $io->note('Creating ' . $config['filename'] . ' skipped');
        } elseif (!GeneralUtility::writeFile($file, $newfileContent)) {
            $io->error('Creating ' . $file . ' failed');
            return false;
        }

        return true;
    }
}
