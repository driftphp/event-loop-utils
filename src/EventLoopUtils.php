<?php

namespace Drift\EventLoop;


use React\EventLoop\LoopInterface;

/**
 * Class EventLoopUtils
 */
class EventLoopUtils
{
    /**
     * Run event loop
     *
     * @param LoopInterface $loop
     * @param int $iterations
     * @param bool $forceVariable
     */
    public static function runLoop(
        LoopInterface $loop,
        int $iterations = 1,
        bool &$forceVariable = false
    )
    {
        while ($iterations > 0 && !$forceVariable) {
            $loop->run();
            $iterations--;
        }
    }
}