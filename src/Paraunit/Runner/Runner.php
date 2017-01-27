<?php
declare(strict_types=1);

namespace Paraunit\Runner;

use Paraunit\Filter\Filter;
use Paraunit\Lifecycle\EngineEvent;
use Paraunit\Lifecycle\ProcessEvent;
use Paraunit\Process\ProcessFactory;
use Paraunit\Process\RetryAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Runner
 * @package Paraunit\Runner
 */
class Runner
{
    /** @var  ProcessFactory */
    private $processFactory;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Filter */
    private $filter;

    /** @var PipelineCollection */
    private $pipelineCollection;

    /** @var \SplQueue */
    private $queuedProcesses;

    /** @var int */
    private $exitCode;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ProcessFactory $processFactory
     * @param Filter $filter
     * @param PipelineCollection $pipelineCollection
     * @internal param int $maxProcessNumber
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ProcessFactory $processFactory,
        Filter $filter,
        PipelineCollection $pipelineCollection
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->processFactory = $processFactory;
        $this->filter = $filter;
        $this->pipelineCollection = $pipelineCollection;
        $this->queuedProcesses = new \SplQueue();
        $this->exitCode = 0;
    }

    /**
     * @return int The final exitcode: 0 if no failures, 10 otherwise
     */
    public function run(): int
    {
        $this->eventDispatcher->dispatch(EngineEvent::BEFORE_START);

        $this->createProcessQueue();

        $this->eventDispatcher->dispatch(EngineEvent::START);

        while (! $this->queuedProcesses->isEmpty()) {
            $this->executeQueuedProcesses();
            $this->pipelineCollection->waitForCompletion();
        }

        $this->eventDispatcher->dispatch(EngineEvent::END);

        return $this->exitCode;
    }

    /**
     * @param ProcessEvent $processEvent
     */
    public function onProcessCompleted(ProcessEvent $processEvent)
    {
        $process = $processEvent->getProcess();

        if ($process instanceof RetryAwareInterface && $process->isToBeRetried()) {
            $process->reset();
            $process->increaseRetryCount();

            $this->queuedProcesses->enqueue($process);
        } elseif ($process->getExitCode() !== 0) {
            $this->exitCode = 10;
        }
    }

    private function executeQueuedProcesses()
    {
        while (! $this->queuedProcesses->isEmpty()) {
            $this->pipelineCollection->push($this->queuedProcesses->dequeue());
        }
    }

    private function createProcessQueue()
    {
        foreach ($this->filter->filterTestFiles() as $file) {
            $process = $this->processFactory->createProcess($file);
            $this->queuedProcesses->enqueue($process);
        }
    }
}
