<?php

namespace Tests\Feature;

use App\Livewire\DesignSystemPreview;
use Livewire\Livewire;
use Tests\TestCase;

class DesignSystemPreviewTest extends TestCase
{
    public function test_preview_route_renders_rtl_arabic_shell(): void
    {
        $this->withoutVite();

        $this->get(route('design-system.preview'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('lang="ar"', false)
            ->assertSee('نظام إدارة الجامعة')
            ->assertSee('PREMIUM INSTITUTIONAL TECHNOLOGY', false);
    }

    public function test_livewire_preview_switches_preview_direction(): void
    {
        Livewire::test(DesignSystemPreview::class)
            ->assertSet('previewLocale', 'ar')
            ->assertSee('معاينة الاتجاه')
            ->assertDontSee('Direction preview')
            ->call('switchLocale')
            ->assertSet('previewLocale', 'en')
            ->assertSee('Direction preview')
            ->assertDontSee('معاينة الاتجاه');
    }

    public function test_livewire_preview_records_interactions(): void
    {
        Livewire::test(DesignSystemPreview::class)
            ->assertSet('interactionCount', 0)
            ->call('recordAction')
            ->call('recordAction')
            ->assertSet('interactionCount', 2)
            ->assertSeeHtml('<span class="ltr" dir="ltr">2</span>');
    }

    public function test_design_system_styles_use_local_fonts_only(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString("--font-ar: 'Segoe UI'", $css);
        $this->assertStringContainsString('--color-brand-900: #0b2438', $css);
        $this->assertStringNotContainsString('Instrument Sans', $css);
        $this->assertStringNotContainsString('fonts.googleapis.com', $css);
        $this->assertStringNotContainsString('@font-face {', $css);
    }
}
