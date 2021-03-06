<?php

declare(strict_types=1);

namespace Paraunit\TestResult;

class TestResultWithSymbolFormat extends TestResultFormat
{
    /** @var string */
    private $testResultSymbol;

    public function __construct(
        string $testResultSymbol,
        string $tag,
        string $title,
        bool $printTestOutput = true,
        bool $printFilesRecap = true
    ) {
        parent::__construct($tag, $title, $printTestOutput, $printFilesRecap);
        $this->testResultSymbol = $testResultSymbol;
    }

    public function getTestResultSymbol(): string
    {
        return $this->testResultSymbol;
    }
}
