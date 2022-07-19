<?php

namespace Softspring\Component\ResponseHeaders\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseHeadersListener implements EventSubscriberInterface
{
    protected array $headers;

    protected ?ExpressionLanguage $expressionLanguage;

    protected array $globalConditions;

    public function __construct(array $headers, ?ExpressionLanguage $expressionLanguage = null, array $globalConditions = [])
    {
        $this->headers = $headers;
        $this->expressionLanguage = $expressionLanguage;
        $this->globalConditions = $globalConditions;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponseAddHeaders',
        ];
    }

    public function onKernelResponseAddHeaders(ResponseEvent $event): void
    {
        foreach ($this->headers as $headerKey => $headerConfig) {
            if ($this->apply($headerKey, $event)) {
                $headerConfig = $this->normalizeConfig($headerConfig);
                $event->getResponse()->headers->set($headerKey, $headerConfig['value'], $headerConfig['replace']);
            }
        }
    }

    protected function normalizeConfig($headerConfig): array
    {
        $normalizedConfiguration = [];

        if (is_string($headerConfig)) {
            $normalizedConfiguration['value'] = [$headerConfig];
        } elseif (isset($headerConfig['value'])) {
            if (is_string($headerConfig['value'])) {
                $normalizedConfiguration['value'] = [$headerConfig['value']];
            } else {
                $normalizedConfiguration['value'] = $headerConfig['value'];
            }
        } else {
            $normalizedConfiguration['value'] = $headerConfig;
        }

        $normalizedConfiguration['value'] = implode('; ', $normalizedConfiguration['value']);
        $normalizedConfiguration['replace'] = $headerConfig['replace'] ?? true;
        $normalizedConfiguration['condition'] = $headerConfig['condition'] ?? null;

        return $normalizedConfiguration;
    }

    protected function apply(string $headerKey, ResponseEvent $event): bool
    {
        $headerConfig = $this->headers[$headerKey];

        if (!$this->expressionLanguage) {
            if (!empty($this->globalConditions)) {
                throw new \Exception(sprintf('There are some global conditions witch needs symfony/expression-language component to be evaluated. If you already installed the component check this component documentation to see how to enable it.', $headerKey));
            }

            if (!empty($headerConfig['condition'])) {
                throw new \Exception(sprintf('The %s header rule needs symfony/expression-language component to be evaluated. If you already installed the component check this component documentation to see how to enable it.', $headerKey));
            }

            return true;
        }

        $context = [
            'mainRequest' => $event->isMainRequest(),
            'request' => $event->getRequest(),
            'response' => $event->getResponse(),
        ];

        foreach ($this->globalConditions as $condition) {
            if (false === (bool) $this->expressionLanguage->evaluate($condition, $context)) {
                return false;
            }
        }

        if (empty($headerConfig['condition'])) {
            return true;
        }

        return (bool) $this->expressionLanguage->evaluate($headerConfig['condition'], $context);
    }
}
