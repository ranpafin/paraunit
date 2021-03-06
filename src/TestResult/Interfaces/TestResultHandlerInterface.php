<?php

declare(strict_types=1);

namespace Paraunit\TestResult\Interfaces;

use Paraunit\Process\AbstractParaunitProcess;

interface TestResultHandlerInterface
{
    public function handleTestResult(AbstractParaunitProcess $process, TestResultInterface $testResult): void;

    public function addProcessToFilenames(AbstractParaunitProcess $process): void;
}
