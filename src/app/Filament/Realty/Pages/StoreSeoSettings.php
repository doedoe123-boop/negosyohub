<?php

namespace App\Filament\Realty\Pages;

use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StoreSeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Store SEO';

    protected static string $view = 'filament.realty.pages.store-seo-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $store = auth()->user()->getStoreForPanel();

        $this->form->fill([
            'seo_title' => $store->seo_title,
            'seo_description' => $store->seo_description,
            'seo_keywords' => $store->seo_keywords,
            'og_image' => $store->og_image,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Search Engine Optimisation')
                    ->description('Customise how your store page appears in Google and other search engines. Leave blank to use the platform defaults.')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('Meta Title')
                            ->maxLength(70)
                            ->helperText('Appears in search results. Leave blank to use your store name.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('seo_description')
                            ->label('Meta Description')
                            ->maxLength(320)
                            ->rows(3)
                            ->helperText('A short summary shown under your title in search results (140–160 characters recommended).')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('seo_keywords')
                            ->label('Meta Keywords')
                            ->maxLength(255)
                            ->helperText('Comma-separated keywords. Most search engines ignore this, but it may help small directories.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Social Sharing')
                    ->description('Controls the image shown when your store link is shared on Facebook, Viber, Twitter/X, and other social platforms.')
                    ->schema([
                        Forms\Components\FileUpload::make('og_image')
                            ->label('Open Graph / Social Share Image')
                            ->image()
                            ->disk('public')
                            ->directory('stores/og')
                            ->helperText('Recommended size: 1200 × 630 px. Falls back to your store banner if not set.')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = auth()->user()->getStoreForPanel();

        $store->update([
            'seo_title' => $data['seo_title'] ?: null,
            'seo_description' => $data['seo_description'] ?: null,
            'seo_keywords' => $data['seo_keywords'] ?: null,
            'og_image' => $data['og_image'] ?? $store->og_image,
        ]);

        Notification::make()
            ->title('Store SEO settings saved')
            ->success()
            ->send();
    }
}
