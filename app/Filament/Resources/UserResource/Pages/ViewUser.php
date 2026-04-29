<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.user-resource.pages.view-user';

    public function getTitle(): string
    {
        return 'Detalle de usuario #' . $this->getRecord()->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password'], $data['remember_token']);

        return $data;
    }
}
