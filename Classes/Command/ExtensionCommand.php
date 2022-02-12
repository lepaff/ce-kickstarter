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

use LEPAFF\CeKickstarter\Component\Extension;
use LEPAFF\CeKickstarter\IO\ServiceConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for creating a new TYPO3 extension
 */
class ExtensionCommand extends AbstractCommand
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

        [,$packageKey] = explode('/', $packageName);

        $extensionKey = (string)$io->ask(
            'Enter the extension key',
            str_replace('-', '_', $packageKey)
        );

        $psr4Prefix = (string)$io->ask(
            'Enter the PSR-4 namespace',
            str_replace(['_', '-'], [], ucwords($packageName, '/-_'))
        );

        $availableTypo3Versions = [
            '^10.4' => 'TYPO3 v10 LTS',
            '^11.5' => 'TYPO3 v11 LTS',
        ];
        $question = $io->askQuestion((new ChoiceQuestion(
            'Choose supported TYPO3 versions (comma separate for multiple)',
            array_combine([10, 11], array_values($availableTypo3Versions)),
            10
        ))->setMultiselect(true));

        $supportedTypo3Versions = [];
        foreach ($question as $resultPosition) {
            $versionConstraint = array_search($resultPosition, $availableTypo3Versions, true);
            $supportedTypo3Versions[$this->getMajorVersion($versionConstraint)] = $versionConstraint;
        }

        $description = $io->ask(
            'Enter a description of the extension',
            null,
            [$this, 'answerRequired']
        );

        $directory = (string)$io->ask(
            'Where should the extension be created?',
            $this->getProposalFromEnvironment('EXTENSION_DIR', 'LocalPackages/')
        );

        $extension = (new Extension())
            ->setPackageName($packageName)
            ->setPackageKey($packageKey)
            ->setExtensionKey($extensionKey)
            ->setPsr4Prefix($psr4Prefix)
            ->setTypo3Versions($supportedTypo3Versions)
            ->setDescription($description)
            ->setDirectory($directory);

        // Create extension directory
        $absoluteExtensionPath = $extension->getExtensionPath();
        if (!file_exists($absoluteExtensionPath)) {
            try {
                GeneralUtility::mkdir_deep($absoluteExtensionPath);
            } catch (\Exception $e) {
                $io->error('Creating of directory ' . $absoluteExtensionPath . ' failed');
                return 1;
            }
        }

        // Create composer.json
        $composerFile = rtrim($absoluteExtensionPath, '/') . '/composer.json';
        if (file_exists($composerFile)
            && !$io->confirm('A composer.json does already exist. Do you want to override it?', true)
        ) {
            $io->note('Creating composer.json skipped');
        } elseif (!GeneralUtility::writeFile($composerFile, json_encode($extension, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true)) {
            $io->error('Creating composer.json failed');
            return 1;
        }

        // Add basic service configuration if requested
        if ($io->confirm('May we add a basic service configuration for you?', true)) {
            $serviceConfiguration = new ServiceConfiguration($absoluteExtensionPath);
            if ($serviceConfiguration->getConfiguration() !== []
                && !$io->confirm('A service configuration does already exist. Do you want to override it?', true)
            ) {
                $io->note('Creating service configuration skipped');
            } else {
                $serviceConfiguration->createBasicServiceConfiguration($extension->getPsr4Prefix());
                if (!$serviceConfiguration->write()) {
                    $io->warning('Creating service configuration failed');
                    return 1;
                }
            }
        }

        // Add basic service configuration if requested
        if ($io->confirm('May we add a basic typoscript configuration for you?', true)) {
            $configFolder     = rtrim($absoluteExtensionPath, '/') . '/' . parent::CONFIGURATION_DIRECTORY;
            $typoscriptFolder = $configFolder . parent::TYPOSCRIPT_DIRECTORY;
            $setupFolder      = $typoscriptFolder . parent::SETUP_DIRECTORY;
            $overridesFolder  = rtrim($absoluteExtensionPath, '/') . '/' . parent::CONFIGURATION_DIRECTORY . parent::OVERRIDES_DIRECTORY;
            $templatesFolder  = rtrim($absoluteExtensionPath, '/') . '/' . parent::PRIVATE_DIRECTORY . parent::TEMPLATES_DIRECTORY . parent::CONTENT_ELEMENTS_DIRECTORY;
            $partialsFolder   = rtrim($absoluteExtensionPath, '/') . '/' . parent::PRIVATE_DIRECTORY . parent::PARTIALS_DIRECTORY . parent::CONTENT_ELEMENTS_DIRECTORY;
            $layoutsFolder    = rtrim($absoluteExtensionPath, '/') . '/' . parent::PRIVATE_DIRECTORY . parent::LAYOUTS_DIRECTORY . parent::CONTENT_ELEMENTS_DIRECTORY;

            $setupFile             = $typoscriptFolder . 'setup.typoscript';
            $contentTyposcriptFile = $setupFolder . 'content.typoscript';
            $sysTemplateFile       = $overridesFolder . 'sys_template.php';

            $currentExtPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ce_kickstarter');

            $setupFileTemplate   = $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::TYPOSCRIPT_DIRECTORY . 'setup.typoscript';
            $contentFileTemplate = $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::TYPOSCRIPT_SETUP_DIRECTORY . 'content.typoscript';
            $sysTemplateTemplate = $currentExtPath . parent::CODE_TEMPLATES_DIRECTORY . parent::CONFIGURATION_DIRECTORY . parent::OVERRIDES_DIRECTORY . 'sys_template.txt';

            $folders = [
                $setupFolder,
                $overridesFolder,
                $templatesFolder,
                $partialsFolder,
                $layoutsFolder
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
                // Add gitkeep file(s)
                if ($folder !== $setupFolder && $folder !== $overridesFolder) {
                    touch($folder . parent::GITKEEPFILE);
                }
            }

            // TypoScript setup file
            $newSetupFile = GeneralUtility::getUrl($setupFileTemplate);
            if (!$newSetupFile) {
                $io->error('Reading setup.typoscript template file failed');
                return 1;
            }
            $newSetupFileContent = str_replace('###EXT_KEY###', $extensionKey, $newSetupFile);
            if (file_exists($setupFile)
                && !$io->confirm('A basic typoscript configuration does already exist. Do you want to override it?', true)
            ) {
                $io->note('Creating basic typoscript configuration skipped');
            } elseif (!GeneralUtility::writeFile($setupFile, $newSetupFileContent)) {
                $io->error('Creating setup.typoscript failed');
                return 1;
            }
            // TypoScript content file
            $newContentFile = GeneralUtility::getUrl($contentFileTemplate);
            if (!$newContentFile) {
                $io->error('Reading content.typoscript template file failed');
                return 1;
            }
            $newContentFileContent = str_replace('###EXT_KEY###', $extensionKey, $newContentFile);
            if (file_exists($contentTyposcriptFile)
            && !$io->confirm('A content typoscript configuration does already exist. Do you want to override it?', true)
            ) {
                $io->note('Creating content typoscript configuration skipped');
            } elseif (!GeneralUtility::writeFile($contentTyposcriptFile, $newContentFileContent)) {
                $io->error('Creating content.typoscript failed');
                return 1;
            }

            // sys_template file
            $newSysTemplateFile = GeneralUtility::getUrl($sysTemplateTemplate);
            if (!$newSysTemplateFile) {
                $io->error('Reading content.typoscript template file failed');
                return 1;
            }
            $newSysTemplateFileContent = str_replace('###EXT_KEY###', $extensionKey, $newSysTemplateFile);
            $newSysTemplateFileContent = str_replace('###EXT_TITLE###', $packageName, $newSysTemplateFileContent);
            if (file_exists($sysTemplateFile)
                && !$io->confirm('A sys_template.php does already exist. Do you want to override it?', true)
            ) {
                $io->note('Creating sys_template.php skipped');
            } elseif (!GeneralUtility::writeFile($sysTemplateFile, $newSysTemplateFileContent)) {
                $io->error('Creating sys_template.php failed');
                return 1;
            }
        }

        // Add ext_emconf.php if TYPO3 v10 or requested (default=NO)
        if (isset($supportedTypo3Versions[10])
            || $io->confirm('May we create a ext_emconf.php for you?', false)
        ) {
            $extEmConfFile = rtrim($absoluteExtensionPath, '/') . '/ext_emconf.php';
            if (file_exists($extEmConfFile)
                && !$io->confirm('A ext_emconf.php does already exist. Do you want to override it?', true)
            ) {
                $io->note('Creating ext_emconf.php skipped');
            } elseif (!GeneralUtility::writeFile($extEmConfFile, (string)$extension)) {
                $io->error('Creating ' . $extEmConfFile . ' failed.');
                return 1;
            }
        }

        // Create the "Classes/" folder
        if (!file_exists($absoluteExtensionPath . parent::CLASSES_DIRECTORY)) {
            try {
                GeneralUtility::mkdir($absoluteExtensionPath . parent::CLASSES_DIRECTORY);
            } catch (\Exception $e) {
                $io->error('Creating of the "' . parent::CLASSES_DIRECTORY . '" folder in ' . $absoluteExtensionPath . ' failed');
                return 1;
            }
            touch($absoluteExtensionPath . parent::CLASSES_DIRECTORY . '.gitkeep');
        }

        $io->success('Successfully created the extension ' . $extension->getExtensionKey() . ' (' . $extension->getPackageName() . ').');
        $io->note('Depending on your installation, the extension now might have to be activated manually.');

        return 0;
    }

    protected function getMajorVersion(string $versionConstraint): int
    {
        return (int)preg_replace_callback(
            '/^\^([0-9]{1,2}).*$/',
            static function ($matches) { return $matches[1]; },
            $versionConstraint
        );
    }
}
