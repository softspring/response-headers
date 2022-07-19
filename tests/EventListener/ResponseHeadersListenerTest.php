<?php

namespace Softspring\Component\ResponseHeaders\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Softspring\Component\ResponseHeaders\EventListener\ResponseHeadersListener;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ResponseHeadersListenerTest extends TestCase
{
    public function createEvent(bool $mainRequest = true): ResponseEvent
    {
        $request = new Request();
        $response = new Response();
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, $request, $mainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST, $response);
    }

    public function testValueString(): void
    {
        $headers = [
            'X-Test' => 'value',
        ];

        $event = $this->createEvent();
        $eventListener = new ResponseHeadersListener($headers, null);
        $eventListener->onKernelResponseAddHeaders($event);

        $this->assertEquals('value', $event->getResponse()->headers->get('x-test'));
    }

    public function testValueArray(): void
    {
        $headers = [
            'X-Test' => [
                'value1',
                'value2',
            ],
        ];

        $event = $this->createEvent();
        $eventListener = new ResponseHeadersListener($headers, null);
        $eventListener->onKernelResponseAddHeaders($event);

        $this->assertEquals('value1; value2', $event->getResponse()->headers->get('x-test'));
    }

    public function testValueFieldString(): void
    {
        $headers = [
            'X-Test' => [
                'value' => 'value-is-string',
            ],
        ];

        $event = $this->createEvent();
        $eventListener = new ResponseHeadersListener($headers, null);
        $eventListener->onKernelResponseAddHeaders($event);

        $this->assertEquals('value-is-string', $event->getResponse()->headers->get('x-test'));
    }

    public function testValueFieldArray(): void
    {
        $headers = [
            'X-Test' => [
                'value' => ['value', 'is', 'array'],
            ],
        ];

        $event = $this->createEvent();
        $eventListener = new ResponseHeadersListener($headers, null);
        $eventListener->onKernelResponseAddHeaders($event);

        $this->assertEquals('value; is; array', $event->getResponse()->headers->get('x-test'));
    }

    public function testReplaceDefault(): void
    {
        $headers = [
            'X-Test' => [
                'value' => 'override-value-as-default-behaviour',
            ],
        ];

        $event = $this->createEvent();
        $event->getResponse()->headers->set('X-Test', 'initial-value');
        $eventListener = new ResponseHeadersListener($headers, null);
        $eventListener->onKernelResponseAddHeaders($event);

        $this->assertEquals('override-value-as-default-behaviour', $event->getResponse()->headers->get('x-test'));
    }

    public function testReplace(): void
    {
        $headers = [
            'X-Test' => [
                'value' => 'override-value-as-configured',
                'replace' => true,
            ],
        ];

        $event = $this->createEvent();
        $event->getResponse()->headers->set('X-Test', 'initial-value');
        $eventListener = new ResponseHeadersListener($headers, null);
        $eventListener->onKernelResponseAddHeaders($event);

        $this->assertEquals('override-value-as-configured', $event->getResponse()->headers->get('x-test'));
    }

    public function testDoNotReplace(): void
    {
        $headers = [
            'X-Test' => [
                'value' => 'override-value',
                'replace' => false,
            ],
        ];

        $event = $this->createEvent();
        $event->getResponse()->headers->set('X-Test', 'initial-value');
        $eventListener = new ResponseHeadersListener($headers, null);
        $eventListener->onKernelResponseAddHeaders($event);

        $this->assertEquals('initial-value', $event->getResponse()->headers->get('x-test'));
    }

    public function testMissingExpressionLanguageOnGlobal(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('There are some global conditions witch needs symfony/expression-language component to be evaluated. If you already installed the component check this component documentation to see how to enable it.');

        $headers = [
            'X-Test' => 'value',
        ];

        $event = $this->createEvent();
        $eventListener = new ResponseHeadersListener($headers, null, ['dummy.expression']);
        $eventListener->onKernelResponseAddHeaders($event);
    }

    public function testMissingExpressionLanguageOnHeader(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The X-Test header rule needs symfony/expression-language component to be evaluated. If you already installed the component check this component documentation to see how to enable it.');

        $headers = [
            'X-Test' => [
                'value' => 'value',
                'condition' => 'dummy.expression',
            ],
        ];

        $event = $this->createEvent();
        $eventListener = new ResponseHeadersListener($headers, null, []);
        $eventListener->onKernelResponseAddHeaders($event);
    }

    public function testExpressionLanguageGlobalRule(): void
    {
        $headers = [
            'X-Test' => 'value',
        ];

        $globalConditions = [
            'mainRequest',
        ];

        $eventListener = new ResponseHeadersListener($headers, new ExpressionLanguage(), $globalConditions);

        // test with main-request (old master-request)
        $mainEvent = $this->createEvent(true);
        $eventListener->onKernelResponseAddHeaders($mainEvent);
        $this->assertEquals('value', $mainEvent->getResponse()->headers->get('x-test'));

        // test with sub-request
        $subRequestEvent = $this->createEvent(false);
        $eventListener->onKernelResponseAddHeaders($subRequestEvent);
        $this->assertNull($subRequestEvent->getResponse()->headers->get('x-test'));
    }

    public function testExpressionLanguage(): void
    {
        $headers = [
            'X-Test-Success' => [
                'value' => 'success',
                'condition' => 'not request.query.has("missing")',
            ],
            'X-Test-Ignored' => [
                'value' => 'ignored',
                'condition' => 'request.query.has("missing")',
            ],
        ];

        $eventListener = new ResponseHeadersListener($headers, new ExpressionLanguage(), []);

        $event = $this->createEvent();
        $eventListener->onKernelResponseAddHeaders($event);
        $this->assertEquals('success', $event->getResponse()->headers->get('x-test-success'));
        $this->assertNull($event->getResponse()->headers->get('x-test-ignored'));
    }
}
