<?php

declare(strict_types=1);

/*
 * This file is part of TYPO3 CMS-based extension "lepaff/ce_kickstarter" by Michael Paffrath <michael.paffrath@gmail.com>.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace LEPAFF\CeKickstarter\IO;

/**
 * Abstract for configuration classes, performing IO operations
 */
abstract class AbstractConfiguration implements ConfigurationInterface
{
    /** @var string */
    protected $packagePath = '';

    /** @var array */
    protected $configuration = [];

    public function __construct(string $packagePath)
    {
        $this->packagePath = rtrim($packagePath, '/') . '/';
        $this->configuration = $this->load();
    }

    abstract protected function load(): array;

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): ConfigurationInterface
    {
        $this->configuration = $configuration;
        return $this;
    }
}
