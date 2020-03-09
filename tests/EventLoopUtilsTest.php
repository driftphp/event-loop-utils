<?php

namespace Drift\EventLoop\Tests;

use Drift\EventLoop\EventLoopUtils;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;
use React\Promise\Promise;
use function Clue\React\Block\await;
use function React\Promise\resolve;


class EventLoopUtilsTest extends TestCase
{
    /**
     * Test simple usage
     */
    public function testSimpleUsage()
    {
        $loop = Factory::create();
        $value = false;

        new Promise(function($resolver) use ($loop, &$value) {
            $loop->futureTick(function() use ($resolver, $loop, &$value) {
                $resolver(await(
                    resolve()->then(function() use (&$value) {
                        $value=true;
                    }),
                    $loop
                ));
            });
        });

        EventLoopUtils::runLoop($loop);
        $this->assertTrue($value);
    }

    /**
     * Test simple usage with two awaits in the same tick
     */
    public function testSimpleUsage2sameTick()
    {
        $loop = Factory::create();
        $value1 = false;
        $value2 = false;

        new Promise(function($resolver) use ($loop, &$value1, &$value2) {
            $loop->futureTick(function() use ($resolver, $loop, &$value1) {
                $resolver(await(
                    resolve()->then(function() use (&$value1) {
                        $value1 = true;
                    }),
                    $loop
                ));
            });
            $loop->futureTick(function() use ($resolver, $loop, &$value2) {
                $resolver(await(
                    resolve()->then(function() use (&$value2) {
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
     * Test simple usage with two awaits in different ticks
     */
    public function testSimpleUsage2differentTicks()
    {
        $loop = Factory::create();
        $value1 = false;

        new Promise(function($resolver) use ($loop, &$value1) {
            $loop->futureTick(function() use ($resolver, $loop, &$value1) {
                await(resolve(), $loop);

                new Promise(function($resolver) use ($loop, &$value1) {
                    $loop->futureTick(function() use ($resolver, $loop, &$value1) {
                        await(resolve(), $loop);
                        $value1 = true;
                    });
                });

                $resolver();
            });
        });

        EventLoopUtils::runLoop($loop);
        $this->assertFalse($value1);
    }

    /**
     * Test simple usage with two awaits in different ticks and iterations 2
     */
    public function testSimpleUsage2differentTicksWithIterations2()
    {
        $loop = Factory::create();
        $value1 = false;

        new Promise(function($resolver) use ($loop, &$value1) {
            $loop->futureTick(function() use ($resolver, $loop, &$value1) {
                await(resolve(), $loop);

                new Promise(function($resolver) use ($loop, &$value1) {
                    $loop->futureTick(function() use ($resolver, $loop, &$value1) {
                        await(resolve(), $loop);
                        $value1 = true;
                    });
                });

                $resolver();
            });
        });

        EventLoopUtils::runLoop($loop, 2);
        $this->assertTrue($value1);
    }

    /**
     * Test force stop
     */
    public function testForceStop()
    {
        $loop = Factory::create();
        $value1 = false;
        $forceStop = false;

        new Promise(function($resolver) use ($loop, &$value1, &$forceStop) {
            $loop->futureTick(function() use ($resolver, $loop, &$value1, &$forceStop) {
                await(resolve(), $loop);
                $forceStop = true;

                new Promise(function($resolver) use ($loop, &$value1) {
                    $loop->futureTick(function() use ($resolver, $loop, &$value1) {
                        await(resolve(), $loop);
                        $value1 = true;
                    });
                });

                $resolver();
            });
        });

        EventLoopUtils::runLoop($loop, 2, $forceStop);
        $this->assertFalse($value1);
    }
}