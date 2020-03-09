<?php

/*
 * This file is part of the Drift Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\EventLoop;

use React\EventLoop\LoopInterface;

/**
 * Class EventLoopUtils.
 */
class EventLoopUtils
{
    /**
     * Run event loop.
     *
     * @param LoopInterface $loop
     * @param int           $iterations
     * @param bool          $forceVariable
     */
    public static function runLoop(
        LoopInterface $loop,
        int $iterations = 1,
        bool &$forceVariable = false
    ) {
        while ($iterations > 0 && !$forceVariable) {
            $loop->run();
            --$iterations;
        }
    }
}
