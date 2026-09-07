<?php

namespace Tests\Feature\Providers;

use App\Services\Notifications\Contracts\BusinessNotificationSender;
use App\Services\Notifications\NullBusinessNotificationSender;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_null_mail_driver_resolves_the_null_sender(): void
    {
        config()->set('medicina_laboral.mail.driver', null);
        $this->app->forgetInstance(BusinessNotificationSender::class);

        $sender = $this->app->make(BusinessNotificationSender::class);

        $this->assertInstanceOf(NullBusinessNotificationSender::class, $sender);
    }

    public function test_null_string_mail_driver_resolves_the_null_sender(): void
    {
        config()->set('medicina_laboral.mail.driver', 'null');
        $this->app->forgetInstance(BusinessNotificationSender::class);

        $sender = $this->app->make(BusinessNotificationSender::class);

        $this->assertInstanceOf(NullBusinessNotificationSender::class, $sender);
    }
}
