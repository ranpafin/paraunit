<?php

declare(strict_types=1);

namespace Paraunit\Filter;

if (! class_exists('SebastianBergmann\FileIterator\Facade')) {
    \class_alias('\File_Iterator_Facade', 'SebastianBergmann\FileIterator\Facade');
}

use Paraunit\Configuration\PHPUnitConfig;
use Paraunit\Proxy\PHPUnitUtilXMLProxy;
use SebastianBergmann\FileIterator\Facade;

class Filter
{
    /** @var PHPUnitUtilXMLProxy */
    private $utilXml;

    /** @var Facade */
    private $fileIteratorFacade;

    /** @var PHPUnitConfig */
    private $configFile;

    /** @var string | null */
    private $relativePath;

    /** @var string | null */
    private $testSuiteFilter;

    /** @var string | null */
    private $stringFilter;

    public function __construct(
        PHPUnitUtilXMLProxy $utilXml,
        Facade $fileIteratorFacade,
        PHPUnitConfig $configFile,
        string $testSuiteFilter = null,
        string $stringFilter = null
    ) {
        $this->utilXml = $utilXml;
        $this->fileIteratorFacade = $fileIteratorFacade;
        $this->configFile = $configFile;
        $this->relativePath = $configFile->getBaseDirectory() . DIRECTORY_SEPARATOR;
        $this->testSuiteFilter = $testSuiteFilter;
        $this->stringFilter = $stringFilter;
    }

    /**
     * @throws \RuntimeException
     *
     * @return string[]
     */
    public function filterTestFiles(): array
    {
        $aggregatedFiles = [];

        $document = $this->utilXml->loadFile($this->configFile->getFileFullPath());
        $xpath = new \DOMXPath($document);

        /** @var \DOMElement $testSuiteNode */
        foreach ($xpath->query('testsuites/testsuite') as $testSuiteNode) {
            if ($this->testSuitePassFilter($testSuiteNode, $this->testSuiteFilter)) {
                $this->addTestsFromTestSuite($testSuiteNode, $aggregatedFiles);
            }
        }

        return $this->filterByString($aggregatedFiles, $this->stringFilter);
    }

    private function testSuitePassFilter(\DOMElement $testSuiteNode, string $testSuiteFilter = null): bool
    {
        if ($testSuiteFilter === null) {
            return true;
        }

        return \in_array(
            $this->getDOMNodeAttribute($testSuiteNode, 'name'),
            explode(',', $testSuiteFilter),
            true
        );
    }

    /**
     * @return string[]
     */
    private function addTestsFromTestSuite(\DOMElement $testSuiteNode, array &$aggregatedFiles): array
    {
        $excludes = $this->getExcludesArray($testSuiteNode);

        $this->addTestsFromDirectoryNodes($testSuiteNode, $aggregatedFiles, $excludes);
        $this->addTestsFromFileNodes($testSuiteNode, $aggregatedFiles);

        return $aggregatedFiles;
    }

    /**
     * @return string[]
     */
    private function getExcludesArray(\DOMElement $testSuiteNode): array
    {
        $excludes = [];
        foreach ($testSuiteNode->getElementsByTagName('exclude') as $excludeNode) {
            $excludes[] = (string) $excludeNode->nodeValue;
        }

        return $excludes;
    }

    /**
     * @param string[] $aggregatedFiles
     * @param string[] $excludes
     */
    private function addTestsFromDirectoryNodes(\DOMElement $testSuiteNode, array &$aggregatedFiles, array $excludes): void
    {
        foreach ($testSuiteNode->getElementsByTagName('directory') as $directoryNode) {
            $directory = (string) $directoryNode->nodeValue;

            $files = $this->fileIteratorFacade->getFilesAsArray(
                $this->relativePath . $directory,
                $this->getDOMNodeAttribute($directoryNode, 'suffix', 'Test.php'),
                $this->getDOMNodeAttribute($directoryNode, 'prefix', ''),
                $excludes
            );

            foreach ($files as $fileName) {
                $this->addFileToAggregateArray($aggregatedFiles, $fileName);
            }
        }
    }

    /**
     * @param string[] $aggregatedFiles
     */
    private function addTestsFromFileNodes(\DOMElement $testSuiteNode, array &$aggregatedFiles): void
    {
        foreach ($testSuiteNode->getElementsByTagName('file') as $fileNode) {
            $fileName = $this->relativePath . $fileNode->nodeValue;
            $this->addFileToAggregateArray($aggregatedFiles, $fileName);
        }
    }

    private function addFileToAggregateArray(array &$aggregatedFiles, string $fileName): void
    {
        // optimized array_unique
        $aggregatedFiles[$fileName] = $fileName;
    }

    private function getDOMNodeAttribute(
        \DOMElement $testSuiteNode,
        string $nodeName,
        string $defaultValue = null
    ): string {
        foreach ($testSuiteNode->attributes as $attrName => $attrNode) {
            if ($attrName === $nodeName) {
                return $attrNode->value;
            }
        }

        return $defaultValue ?? '';
    }

    /**
     * @return string[]
     */
    private function filterByString(array $aggregatedFiles, ?string $stringFilter): array
    {
        if ($stringFilter !== null) {
            $aggregatedFiles = array_filter($aggregatedFiles, function ($value) use ($stringFilter) {
                return stripos($value, $stringFilter) !== false;
            });
        }

        return array_values($aggregatedFiles);
    }
}
