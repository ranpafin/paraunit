<?php

declare(strict_types=1);

namespace Tests\Unit\Parser\JSON;

use Paraunit\Configuration\EnvVariables;
use Paraunit\Parser\JSON\LogPrinter;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use Tests\BaseUnitTestCase;

class LogPrinterTest extends BaseUnitTestCase
{
    public function testStartTestSuite(): void
    {
        $this->createPrinterAndStartTestSuite();

        $this->assertEquals($this->encodeWithStartTestSuite(), $this->getLogContent());
    }

    public function testAddError(): void
    {
        $printer = $this->createPrinterAndStartTestSuite();
        $test = $this->prophesize(Test::class)->reveal();

        $printer->startTest($test);
        $printer->addError($test, new \Exception('Exception message'), 1);
        $line = __LINE__ - 1;

        $expectedContent = $this->encodeWithStartTestSuite([
            [
                'event' => 'testStart',
                'suite' => get_class($this),
                'test' => get_class($test),
            ],
            [
                'event' => 'test',
                'suite' => get_class($this),
                'test' => get_class($test),
                'status' => 'error',
                'time' => 1,
                'trace' => __FILE__ . ':' . $line . "\n",
                'message' => "Exception: Exception message\n",
                'output' => '',
            ],
        ]);
        $this->assertEquals($expectedContent, $this->getLogContent());
    }

    public function testAddWarning(): void
    {
        $printer = $this->createPrinterAndStartTestSuite();
        $test = $this->prophesize(Test::class)->reveal();
        // has final methods, cannot be mocked
        $warning = new Warning('Warning message', 123, new \Exception());
        $line = __LINE__ - 1;

        $printer->startTest($test);
        $printer->addWarning($test, $warning, 1);

        $expectedContent = $this->encodeWithStartTestSuite([
            [
                'event' => 'testStart',
                'suite' => get_class($this),
                'test' => get_class($test),
            ],
            [
                'event' => 'test',
                'suite' => get_class($this),
                'test' => get_class($test),
                'status' => 'warning',
                'time' => 1,
                'trace' => __FILE__ . ':' . $line . "\n",
                'message' => "Warning message\n",
                'output' => '',
            ],
        ]);
        $this->assertEquals($expectedContent, $this->getLogContent());
    }

    public function testAddFailure(): void
    {
        $printer = $this->createPrinterAndStartTestSuite();
        $test = $this->prophesize(Test::class)->reveal();
        // has final methods, cannot be mocked
        $failure = new AssertionFailedError('Failure message', 123, new \Exception());
        $line = __LINE__ - 1;

        $printer->startTest($test);
        $printer->addFailure($test, $failure, 1);

        $expectedContent = $this->encodeWithStartTestSuite([
            [
                'event' => 'testStart',
                'suite' => get_class($this),
                'test' => get_class($test),
            ],
            [
                'event' => 'test',
                'suite' => get_class($this),
                'test' => get_class($test),
                'status' => 'fail',
                'time' => 1,
                'trace' => __FILE__ . ':' . $line . "\n",
                'message' => "Failure message\n",
                'output' => '',
            ],
        ]);
        $this->assertEquals($expectedContent, $this->getLogContent());
    }

    public function testAddIncompleteTest(): void
    {
        $printer = $this->createPrinterAndStartTestSuite();
        $test = $this->prophesize(Test::class)->reveal();
        // has final methods, cannot be mocked
        $failure = new \Exception('Incomplete message');
        $line = __LINE__ - 1;

        $printer->startTest($test);
        $printer->addIncompleteTest($test, $failure, 1);

        $expectedContent = $this->encodeWithStartTestSuite([
            [
                'event' => 'testStart',
                'suite' => get_class($this),
                'test' => get_class($test),
            ],
            [
                'event' => 'test',
                'suite' => get_class($this),
                'test' => get_class($test),
                'status' => 'error',
                'time' => 1,
                'trace' => __FILE__ . ':' . $line . "\n",
                'message' => 'Incomplete Test: Incomplete message',
                'output' => '',
            ],
        ]);
        $this->assertEquals($expectedContent, $this->getLogContent());
    }

    public function testAddRiskyTest(): void
    {
        $printer = $this->createPrinterAndStartTestSuite();
        $test = $this->prophesize(Test::class)->reveal();
        // has final methods, cannot be mocked
        $failure = new \Exception('Risky message');
        $line = __LINE__ - 1;

        $printer->startTest($test);
        $printer->addRiskyTest($test, $failure, 1);

        $expectedContent = $this->encodeWithStartTestSuite([
            [
                'event' => 'testStart',
                'suite' => get_class($this),
                'test' => get_class($test),
            ],
            [
                'event' => 'test',
                'suite' => get_class($this),
                'test' => get_class($test),
                'status' => 'error',
                'time' => 1,
                'trace' => __FILE__ . ':' . $line . "\n",
                'message' => 'Risky Test: Risky message',
                'output' => '',
            ],
        ]);
        $this->assertEquals($expectedContent, $this->getLogContent());
    }

    public function testAddSkippedTest(): void
    {
        $printer = $this->createPrinterAndStartTestSuite();
        $test = $this->prophesize(Test::class)->reveal();
        // has final methods, cannot be mocked
        $failure = new \Exception('Skipped message');
        $line = __LINE__ - 1;

        $printer->startTest($test);
        $printer->addSkippedTest($test, $failure, 1);

        $expectedContent = $this->encodeWithStartTestSuite([
            [
                'event' => 'testStart',
                'suite' => get_class($this),
                'test' => get_class($test),
            ],
            [
                'event' => 'test',
                'suite' => get_class($this),
                'test' => get_class($test),
                'status' => 'error',
                'time' => 1,
                'trace' => __FILE__ . ':' . $line . "\n",
                'message' => 'Skipped Test: Skipped message',
                'output' => '',
            ],
        ]);
        $this->assertEquals($expectedContent, $this->getLogContent());
    }

    public function testEndTest(): void
    {
        $printer = $this->createPrinterAndStartTestSuite();
        $test = $this->prophesize(Test::class)->reveal();

        $printer->startTest($test);
        $printer->endTest($test, 1);

        $expectedContent = $this->encodeWithStartTestSuite([
            [
                'event' => 'testStart',
                'suite' => get_class($this),
                'test' => get_class($test),
            ],
            [
                'event' => 'test',
                'suite' => get_class($this),
                'test' => get_class($test),
                'status' => 'pass',
                'time' => 1,
                'trace' => '',
                'message' => '',
                'output' => '',
            ],
        ]);
        $this->assertEquals($expectedContent, $this->getLogContent());

        $printer->endTestSuite($this->prophesize(TestSuite::class)->reveal());

        $this->assertEquals($expectedContent, $this->getLogContent());
    }

    private function createPrinterAndStartTestSuite(): LogPrinter
    {
        $this->createRandomTmpDir();
        putenv(EnvVariables::PROCESS_UNIQUE_ID . '=log-file-name');
        $printer = new LogPrinter();
        $testSuite = $this->prophesize(TestSuite::class);
        $testSuite->getName()
            ->willReturn(get_class($this));
        $testSuite->count()
            ->willReturn(1);

        $printer->startTestSuite($testSuite->reveal());

        return $printer;
    }

    private function getLogContent(): string
    {
        $logFilename = $this->getRandomTempDir() . 'log-file-name.json.log';
        $content = $this->getFileContent($logFilename);

        $parsedOutput = preg_replace('/\r\n/', "\n", $content);
        $this->assertNotNull($parsedOutput, 'Preg replace failed');

        return $parsedOutput;
    }

    private function encodeWithStartTestSuite(array $data = []): string
    {
        $logElements = [$this->getStartTestSuiteLog()];
        foreach ($data as $datum) {
            $logElements[] = $datum;
        }

        $result = '';
        foreach ($logElements as $logElement) {
            $result .= json_encode($logElement, JSON_PRETTY_PRINT);
        }

        return $result;
    }

    private function getStartTestSuiteLog(): array
    {
        return [
            'event' => 'suiteStart',
            'suite' => get_class($this),
            'tests' => 1,
        ];
    }
}
