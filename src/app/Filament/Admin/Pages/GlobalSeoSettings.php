<?php

namespace App\Filament\Admin\Pages;

use App\Models\GlobalSeoSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GlobalSeoSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'SEO & Analytics';

    protected static string $view = 'filament.admin.pages.global-seo-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = GlobalSeoSetting::current();

        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('SEO & Analytics')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Global Defaults')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Site Identity')
                                    ->schema([
                                        Forms\Components\TextInput::make('site_name')
                                            ->label('Site Name')
                                            ->required()
                                            ->maxLength(80)
                                            ->helperText('Displayed in the browser tab and used in social cards.'),

                                        Forms\Components\TextInput::make('title_template')
                                            ->label('Title Template')
                                            ->required()
                                            ->maxLength(120)
                                            ->helperText('Use %s as a placeholder for the page title. E.g. "%s | NegosyoHub".'),
                                    ])->columns(2),

                                Forms\Components\Section::make('Default Meta Tags')
                                    ->description('Used when a page does not define its own meta tags.')
                                    ->schema([
                                        Forms\Components\Textarea::make('default_description')
                                            ->label('Default Meta Description')
                                            ->maxLength(320)
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\FileUpload::make('default_og_image')
                                            ->label('Default Open Graph Image')
                                            ->image()
                                            ->disk('public')
                                            ->directory('seo')
                                            ->helperText('Recommended size: 1200 × 630 px. Used when no specific OG image is defined.')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Twitter / X Cards')
                                    ->schema([
                                        Forms\Components\TextInput::make('twitter_site')
                                            ->label('Twitter / X Username')
                                            ->prefix('@')
                                            ->maxLength(50)
                                            ->helperText('Your platform\'s Twitter/X handle.'),

                                        Forms\Components\Select::make('twitter_card')
                                            ->label('Card Style')
                                            ->options([
                                                'summary' => 'Summary (small image)',
                                                'summary_large_image' => 'Summary with Large Image',
                                            ])
                                            ->required(),
                                    ])->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Analytics')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('Google Analytics 4')
                                    ->schema([
                                        Forms\Components\TextInput::make('google_analytics_id')
                                            ->label('GA4 Measurement ID')
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->helperText('Leave blank to disable Google Analytics tracking.'),
                                    ]),

                                Forms\Components\Section::make('Google Tag Manager')
                                    ->schema([
                                        Forms\Components\TextInput::make('google_tag_manager_id')
                                            ->label('GTM Container ID')
                                            ->placeholder('GTM-XXXXXXXX')
                                            ->helperText('Use GTM to manage analytics tags, custom scripts, and third-party pixels. Preferred over individual integrations.'),
                                    ]),

                                Forms\Components\Section::make('Meta (Facebook) Pixel')
                                    ->schema([
                                        Forms\Components\TextInput::make('facebook_pixel_id')
                                            ->label('Pixel ID')
                                            ->placeholder('123456789012345')
                                            ->helperText('Leave blank to disable Meta Pixel tracking.'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Crawling')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Forms\Components\Section::make('robots.txt')
                                    ->description('Controls how search engine bots crawl the site. Available at /robots.txt.')
                                    ->schema([
                                        Forms\Components\Toggle::make('sitemap_enabled')
                                            ->label('Enable XML Sitemap')
                                            ->helperText('When enabled, a sitemap link is automatically appended to robots.txt.')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('robots_txt_content')
                                            ->label('robots.txt Content')
                                            ->rows(8)
                                            ->placeholder("User-agent: *\nAllow: /\n\nDisallow: /moon/")
                                            ->helperText('Leave blank to use the default "Allow all" policy. The Sitemap directive is appended automatically when the sitemap is enabled.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = GlobalSeoSetting::current();
        $settings->update($data);

        Notification::make()
            ->title('SEO & Analytics settings saved')
            ->success()
            ->send();
    }
}
