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

namespace Drift\EventLoop\Tests;

use Drift\EventLoop\EventLoopUtils;
use function Clue\React\Block\await;
use function React\Promise\resolve;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\Promise\Promise;

class EventLoopUtilsTest extends TestCase
{
    /**
     * Test simple usage.
     */
    public function testSimpleUsage()
    {
        $loop = Factory::create();
        $value = false;

        new Promise(function ($resolver) use ($loop, &$value) {
            $loop->futureTick(function () use ($resolver, $loop, &$value) {
                $resolver(await(
                    resolve(null)->then(function () use (&$value) {
                        $value = true;
                    }),
                    $loop
                ));
            });
        });

        EventLoopUtils::runLoop($loop);
        $this->assertTrue($value);
    }

    /**
     * Test simple usage with two awaits in the same tick.
     */
    public function testSimpleUsage2sameTick()
    {
        $loop = Factory::create();
        $value1 = false;
        $value2 = false;

        new Promise(function ($resolver) use ($loop, &$value1, &$value2) {
            $loop->futureTick(function () use ($resolver, $loop, &$value1) {
                $resolver(await(
                    resolve(null)->then(function () use (&$value1) {
                        $value1 = true;
                    }),
                    $loop
                ));
            });
            $loop->futureTick(function () use ($resolver, $loop, &$value2) {
                $resolver(await(
                    resolve(null)->then(function () use (&$value2) {
                        $value2 = true;
                    }),
                    $loop
                ));
            });
        });

        EventLoopUtils::runLoop($loop);
        $this->assertTrue($value1);
        $this->assertTrue($value2);
    }

    /**
     * Test simple usage with two awaits in different ticks.
     */
    public function testSimpleUsage2differentTicks()
    {
        $loop = Factory::create();
        $value1 = false;

        new Promise(function ($resolver) use ($loop, &$value1) {
            $loop->futureTick(function () use ($resolver, $loop, &$value1) {
                await(resolve(null), $loop);

                new Promise(function ($resolver) use ($loop, &$value1) {
                    $loop->futureTick(function () use ($resolver, $loop, &$value1) {
                        await(resolve(null), $loop);
                        $value1 = true;
                    });
                });

                $resolver(null);
            });
        });

        EventLoopUtils::runLoop($loop);
        $this->assertFalse($value1);
    }

    /**
     * Test simple usage with two awaits in different ticks and iterations 2.
     */
    public function testSimpleUsage2differentTicksWithIterations2()
    {
        $loop = Factory::create();
        $value1 = false;
        $it = 0;
        $its = [];

        new Promise(function ($resolver) use ($loop, &$value1) {
            $loop->futureTick(function () use ($resolver, $loop, &$value1) {
                await(resolve(null), $loop);

                new Promise(function ($resolver) use ($loop, &$value1) {
                    $loop->futureTick(function () use ($resolver, $loop, &$value1) {
                        await(resolve(null), $loop);
                        $value1 = true;
                    });
                });

                $resolver(null);
            });
        });

        EventLoopUtils::runLoop($loop, 2, function($iterations) use (&$it, &$its) {
            $it++;
            $its[] = $iterations;
        });

        $this->assertTrue($value1);
        $this->assertSame(2, $it);
        $this->assertEquals([1, 0], $its);
    }

    /**
     * Test force stop.
     */
    public function testForceStop()
    {
        $loop = Factory::create();
        $value1 = false;
        $forceStop = false;

        new Promise(function ($resolver) use ($loop, &$value1, &$forceStop) {
            $loop->futureTick(function () use ($resolver, $loop, &$value1, &$forceStop) {
                await(resolve(null), $loop);
                $forceStop = true;

                new Promise(function ($resolver) use ($loop, &$value1) {
                    $loop->futureTick(function () use ($resolver, $loop, &$value1) {
                        await(resolve(null), $loop);
                        $value1 = true;
                    });
                });

                $resolver(null);
            });
        });

        EventLoopUtils::runLoop($loop, 2, null, $forceStop);
        $this->assertFalse($value1);
    }
}
