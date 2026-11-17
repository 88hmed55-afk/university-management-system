<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class DesignSystemPreview extends Component
{
    public string $previewLocale = 'ar';

    public int $interactionCount = 0;

    public function switchLocale(): void
    {
        $this->previewLocale = $this->previewLocale === 'ar' ? 'en' : 'ar';
    }

    public function recordAction(): void
    {
        $this->interactionCount++;
    }

    public function render(): View
    {
        return view('livewire.design-system-preview');
    }
}
