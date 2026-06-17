<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class MultiDatePicker extends Field
{
    protected string $view = 'filament.forms.components.multi-date-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule('array');
    }
}
