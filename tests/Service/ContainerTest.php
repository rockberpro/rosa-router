<?php

use PHPUnit\Framework\TestCase;
use Rockberpro\RestRouter\Service\Container;

class ContainerTest extends TestCase
{
    public function testGetInstanceReturnsSingleton(): void
    {
        $this->assertSame(Container::getInstance(), Container::getInstance());
    }

    public function testSetAndGetReturnsSameService(): void
    {
        $service = new stdClass();
        $container = Container::getInstance();
        $container->set('svc.plain', $service);

        $this->assertTrue($container->has('svc.plain'));
        $this->assertSame($service, $container->get('svc.plain'));
    }

    public function testHasReturnsFalseForUnknownService(): void
    {
        $this->assertFalse(Container::getInstance()->has('svc.unknown'));
    }

    public function testGetThrowsForUnknownService(): void
    {
        $this->expectException(\RuntimeException::class);
        Container::getInstance()->get('svc.missing');
    }

    public function testClosureFactoryIsResolvedLazilyAndCached(): void
    {
        $calls = 0;
        $container = Container::getInstance();
        $container->set('svc.factory', function () use (&$calls) {
            $calls++;
            return new stdClass();
        });

        // not invoked until first get()
        $this->assertSame(0, $calls);

        $first = $container->get('svc.factory');
        $second = $container->get('svc.factory');

        $this->assertSame(1, $calls, 'factory closure must run exactly once');
        $this->assertSame($first, $second, 'resolved instance must be cached');
    }

    public function testSetInstanceReplacesTheContainer(): void
    {
        $original = Container::getInstance();
        $replacement = new class implements \Rockberpro\RestRouter\Service\ContainerInterface {
            public function set(string $id, object $service): void {}
            public function get(string $id) { return 'stub'; }
            public function has(string $id): bool { return true; }
        };

        try {
            Container::setInstance($replacement);
            $this->assertSame($replacement, Container::getInstance());
            $this->assertSame('stub', Container::getInstance()->get('anything'));
        } finally {
            // restore so we don't pollute other tests sharing the process
            Container::setInstance($original);
        }
    }
}
