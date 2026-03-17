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
            return $this->translator->get($result->messageKey, $result->messageParams);
        }

        if ($result->template !== null) {
            return $this->view->make($result->template, $result->templateData)->render();
        }

        return null;
    }
}
