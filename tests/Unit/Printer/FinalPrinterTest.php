<?php
declare(strict_types=1);

namespace Tests\Unit\Printer;

use Paraunit\Printer\FinalPrinter;
use Paraunit\TestResult\TestResultContainer;
use Paraunit\TestResult\TestResultList;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\Stopwatch\Stopwatch;
use Tests\BaseUnitTestCase;
use Tests\Stub\UnformattedOutputStub;

/**
 * Class FailuresPrinterTest
 * @package Tests\Unit\Printer
 */
class FinalPrinterTest extends BaseUnitTestCase
{
    public function testOnEngineEndPrintsTheRightCountSummary()
    {
        ClockMock::register(Stopwatch::class);
        ClockMock::register(__CLASS__);
        $output = new UnformattedOutputStub();

        $testResultContainer = $this->prophesize(TestResultContainer::class);
        $testResultContainer->countTestResults()
            ->willReturn(3);
        $testResultContainer->getTestResults()
            ->willReturn(array_fill(0, 3, $this->mockTestResult()));
        $testResultContainer->getTestResultFormat()
            ->willReturn($this->mockTestFormat());
        $testResultContainer->getFileNames()
            ->willReturn(['Test.php']);

        $testResultList = $this->prophesize(TestResultList::class);
        $testResultList->getTestResultContainers()
            ->willReturn(array_fill(0, 15, $testResultContainer->reveal()));

        $printer = new FinalPrinter($testResultList->reveal(), $output);

        ClockMock::withClockMock(true);

        $printer->onEngineStart();
        $printer->onProcessTerminated();
        $printer->onProcessTerminated();
        $printer->onProcessTerminated();
        $printer->onProcessTerminated();
        $printer->onProcessTerminated();
        $printer->onProcessToBeRetried();
        $printer->onProcessTerminated();
        sleep(60.4);
        $printer->onEngineEnd();

        ClockMock::withClockMock(false);

        $this->assertContains('Execution time -- 00:01:00', $output->getOutput());
        $this->assertContains('Executed: 5 test classes, 45 tests (1 retried)', $output->getOutput());
    }

    public function testOnEngineEndHandlesEmptyMessagesCorrectly()
    {
        $testResultContainer = $this->prophesize(TestResultContainer::class);
        $testResultContainer->countTestResults()
            ->willReturn(3);
        $testResultContainer->getTestResults()
            ->willReturn(array_fill(0, 3, $this->mockTestResult()));
        $testResultContainer->getTestResultFormat()
            ->willReturn($this->mockTestFormat());
        $testResultContainer->getFileNames()
            ->willReturn(['Test.php']);

        $testResultList = $this->prophesize(TestResultList::class);
        $testResultList->getTestResultContainers()
            ->willReturn(array_fill(0, 15, $testResultContainer->reveal()));
        $output = new UnformattedOutputStub();

        $printer = new FinalPrinter($testResultList->reveal(), $output);

        $printer->onEngineStart();
        $printer->onEngineEnd();

        $this->assertNotContains('output', $output->getOutput());
    }
}
