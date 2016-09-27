<?php

namespace Kelunik\DaaS\Rendering;

use Twig_Environment as TwigEnvironment;
use Psr\Log\LoggerInterface as PsrLogger;
use Twig_TemplateInterface as TwigTemplate;

class Twig {
    private $twigEnvironment;
    private $templates;
    private $logger;

    public function __construct(TwigEnvironment $twigEnvironment, PsrLogger $logger) {
        $this->twigEnvironment = $twigEnvironment;
        $this->logger = $logger;
    }

    public function render(string $name, TwigContext $context) {
        assert($this->debug("Rendering '{$name}' with '" . json_encode($context->toArray()) . "'"));

        $template = $this->load($name);

        return $template->render($context->toArray());
    }

    private function load(string $name): TwigTemplate {
        if (!isset($this->templates[$name])) {
            $this->templates[$name] = $this->twigEnvironment->loadTemplate($name);
        }

        return $this->templates[$name];
    }

    private function debug(string $message, array $context = []) {
        $this->logger->debug($message, $context);

        // return true to always pass assertions
        return true;
    }
}