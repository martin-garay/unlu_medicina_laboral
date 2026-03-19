<?php

namespace App\Flows\Common;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Translation\Translator;

class MessageResolver
{
    public function __construct(
        private readonly Translator $translator,
        private readonly ViewFactory $view,
    ) {
    }

    public function resolve(StepResult $result): ?string
    {
        if ($result->message !== null) {
            return $result->message;
        }

        if ($result->messageKey !== null) {
            return $this->resolveKey($result->messageKey, $result->messageParams);
        }

        if ($result->template !== null) {
            return $this->resolveTemplate($result->template, $result->templateData);
        }

        return null;
    }

    public function resolveKey(string $messageKey, array $messageParams = []): string
    {
        return $this->translator->get($messageKey, $messageParams);
    }

    public function resolveTemplate(string $template, array $templateData = []): string
    {
        return $this->view->make($template, $templateData)->render();
    }
}
