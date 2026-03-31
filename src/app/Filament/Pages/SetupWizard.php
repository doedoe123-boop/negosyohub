<?php

namespace App\Filament\Pages;

use App\PayoutMethod;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

class SetupWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.setup-wizard';

    protected static ?string $title = 'Welcome — Set Up Your Store Portal';

    protected static ?string $slug = 'setup';

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $store = auth()->user()->getStoreForPanel();

        if (! $store) {
            abort(403);
        }

        $social = $store->social_links ?? [];

        $this->form->fill([
            'name' => $store->name,
            'tagline' => $store->tagline,
            'description' => $store->description,
            'logo' => $store->logo,
            'banner' => $store->banner,
            'collection_ids' => $store->collections()->pluck('lunar_collections.id')->toArray(),
            'phone' => $store->phone,
            'website' => $store->website,
            'address' => $store->address ?? [],
            'social_facebook' => $social['facebook'] ?? null,
            'social_instagram' => $social['instagram'] ?? null,
            'social_tiktok' => $social['tiktok'] ?? null,
            'social_lazada' => $social['lazada'] ?? null,
            'social_shopee' => $social['shopee'] ?? null,
            'agent_bio' => $store->agent_bio,
            'agent_photo' => $store->agent_photo,
            'prc_license_number' => $store->prc_license_number,
            'agent_specializations' => $store->agent_specializations ?? [],
            'hours' => $store->operating_hours ?? static::defaultHours(),
            'payout_method' => $store->payout_method?->value,
            'payout_account_name' => $store->payout_details['account_name'] ?? null,
            'payout_account_number' => $store->payout_details['account_number'] ?? null,
            'payout_bank_name' => $store->payout_details['bank_name'] ?? null,
            'payout_mobile_number' => $store->payout_details['mobile_number'] ?? null,
        ]);

        if ($store->isLipatBahay()) {
            $this->form->fill([
                ...$this->form->getState(),
                'moving_base_price' => $store->moving_base_price ? $store->moving_base_price / 100 : null,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        $store = auth()->user()->getStoreForPanel();
        $hasAgentProfile = $store && in_array('agent_profile', $store->sectorModel()?->supportedFeatures() ?? [], true);

        $step3Label = $hasAgentProfile ? 'Agent Profile' : 'Business Hours';
        $step3Icon = $hasAgentProfile ? 'heroicon-o-identification' : 'heroicon-o-clock';
        $step3Desc = $hasAgentProfile ? 'Your agent credentials' : 'When you\'re open for business';
        $step3Schema = $hasAgentProfile ? $this->realEstateSchema() : $this->operatingHoursSchema();

        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Brand Identity')
                        ->icon('heroicon-o-building-storefront')
                        ->description('Your public-facing store details')
                        ->schema($this->brandIdentitySchema()),

                    Forms\Components\Wizard\Step::make('Contact & Presence')
                        ->icon('heroicon-o-globe-alt')
                        ->description('How customers reach you online')
                        ->schema($this->contactSchema()),

                    Forms\Components\Wizard\Step::make($step3Label)
                        ->icon($step3Icon)
                        ->description($step3Desc)
                        ->schema($step3Schema),

                    Forms\Components\Wizard\Step::make('Payout Info')
                        ->icon('heroicon-o-banknotes')
                        ->description('How you receive your earnings')
                        ->schema($this->payoutInfoSchema()),
                ])
                    ->submitAction(new HtmlString(Blade::render(
                        '<x-filament::button type="submit" size="lg" color="success" icon="heroicon-o-check-circle">Complete Setup</x-filament::button>'
                    ))),
            ])
            ->statePath('data');
    }

    /**
     * @return array<Forms\Components\Component>
     */
    private function brandIdentitySchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->label('Store Logo')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('stores/logos')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                        ->helperText('Recommended: square image, at least 256×256 px. Max 2 MB.')
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('banner')
                        ->label('Store Banner / Cover Photo')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('stores/banners')
                        ->maxSize(5120)
                        ->rules(['dimensions:min_width=800,min_height=200,max_width=3840,max_height=1080'])
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->helperText('Recommended: 1200x300 px (4:1 landscape). Min 800x200 px, max 5 MB. Displayed at the top of your store page.')
                        ->columnSpanFull(),

                    Forms\Components\CheckboxList::make('collection_ids')
                        ->label('Store Categories')
                        ->helperText('Select the categories that best describe your store.')
                        ->options(function (): array {
                            $group = CollectionGroup::where('handle', 'marketplace-categories')->first();

                            if (! $group) {
                                return [];
                            }

                            return Collection::where('collection_group_id', $group->id)
                                ->orderBy('sort')
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (Collection $c): array => [$c->id => $c->translateAttribute('name')])
                                ->toArray();
                        })
                        ->columns(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('name')
                        ->label('Store Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('tagline')
                        ->label('Tagline')
                        ->placeholder('Your everyday marketplace')
                        ->maxLength(150)
                        ->helperText('A short phrase shown beneath your store name.'),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Tell customers what makes your store special...')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])->columns(2),
        ];
    }

    /**
     * @return array<Forms\Components\Component>
     */
    private function contactSchema(): array
    {
        return [
            Forms\Components\Section::make('Contact Information')
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Business Phone')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('website')
                        ->label('Website')
                        ->url()
                        ->placeholder('https://yourstore.com')
                        ->maxLength(500),

                    Forms\Components\TextInput::make('moving_base_price')
                        ->label('Base Moving Rate (PHP)')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('PHP')
                        ->helperText('This is the provider-controlled base rate used for booking totals before add-ons.')
                        ->visible(fn (): bool => auth()->user()?->getStoreForPanel()?->isLipatBahay() ?? false),
                ])->columns(2),

            Forms\Components\Section::make('Store Address')
                ->schema([
                    Forms\Components\TextInput::make('address.line_one')
                        ->label('Street Address')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('address.city')
                        ->label('City')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('address.province')
                        ->label('Province')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('address.postcode')
                        ->label('Zip Code')
                        ->maxLength(10),
                ])->columns(2),

            Forms\Components\Section::make('Social Media')
                ->description('Add social media links so customers can find you on other platforms.')
                ->schema([
                    Forms\Components\TextInput::make('social_facebook')
                        ->label('Facebook')
                        ->url()
                        ->placeholder('https://facebook.com/yourpage')
                        ->prefixIcon('heroicon-o-link'),

                    Forms\Components\TextInput::make('social_instagram')
                        ->label('Instagram')
                        ->url()
                        ->placeholder('https://instagram.com/yourhandle')
                        ->prefixIcon('heroicon-o-link'),

                    Forms\Components\TextInput::make('social_tiktok')
                        ->label('TikTok')
                        ->url()
                        ->placeholder('https://tiktok.com/@yourhandle')
                        ->prefixIcon('heroicon-o-link'),

                    Forms\Components\TextInput::make('social_lazada')
                        ->label('Lazada Store')
                        ->url()
                        ->placeholder('https://lazada.com.ph/shop/...')
                        ->prefixIcon('heroicon-o-link'),

                    Forms\Components\TextInput::make('social_shopee')
                        ->label('Shopee Store')
                        ->url()
                        ->placeholder('https://shopee.ph/yourshop')
                        ->prefixIcon('heroicon-o-link'),
                ])->columns(2),
        ];
    }

    /**
     * @return array<Forms\Components\Component>
     */
    private function operatingHoursSchema(): array
    {
        return [
            Forms\Components\Section::make('Business Hours')
                ->description('Toggle each day and set your opening and closing times. Defaults are pre-filled — just adjust as needed.')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\Placeholder::make('_hdr_day')
                                ->label('')
                                ->content('Day'),
                            Forms\Components\Placeholder::make('_hdr_open')
                                ->label('')
                                ->content('Opens'),
                            Forms\Components\Placeholder::make('_hdr_close')
                                ->label('')
                                ->content('Closes'),
                        ]),
                    ...$this->dayRow('monday', 'Monday'),
                    ...$this->dayRow('tuesday', 'Tuesday'),
                    ...$this->dayRow('wednesday', 'Wednesday'),
                    ...$this->dayRow('thursday', 'Thursday'),
                    ...$this->dayRow('friday', 'Friday'),
                    ...$this->dayRow('saturday', 'Saturday'),
                    ...$this->dayRow('sunday', 'Sunday'),
                ]),
        ];
    }

    /**
     * Build one day-row: toggle + open time + close time.
     *
     * @return array<Forms\Components\Component>
     */
    private function dayRow(string $day, string $label): array
    {
        return [
            Forms\Components\Toggle::make("hours.{$day}.is_open")
                ->label($label)
                ->live()
                ->inline(false),

            Forms\Components\TimePicker::make("hours.{$day}.open")
                ->label('Opens')
                ->seconds(false)
                ->hidden(fn (Get $get): bool => ! $get("hours.{$day}.is_open")),

            Forms\Components\TimePicker::make("hours.{$day}.close")
                ->label('Closes')
                ->seconds(false)
                ->hidden(fn (Get $get): bool => ! $get("hours.{$day}.is_open")),
        ];
    }

    /**
     * Default Philippine business hours: Mon–Fri 8–18, Sat 9–15, Sun closed.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaultHours(): array
    {
        $weekday = ['is_open' => true, 'open' => '08:00', 'close' => '18:00'];
        $saturday = ['is_open' => true, 'open' => '09:00', 'close' => '15:00'];
        $closed = ['is_open' => false, 'open' => null, 'close' => null];

        return [
            'monday' => $weekday,
            'tuesday' => $weekday,
            'wednesday' => $weekday,
            'thursday' => $weekday,
            'friday' => $weekday,
            'saturday' => $saturday,
            'sunday' => $closed,
        ];
    }

    /**
     * @return array<Forms\Components\Component>
     */
    private function realEstateSchema(): array
    {
        return [
            Forms\Components\Section::make('Agent Profile')
                ->description('This information is shown to property buyers and renters.')
                ->schema([
                    Forms\Components\FileUpload::make('agent_photo')
                        ->label('Profile Photo')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('agents/photos')
                        ->maxSize(2048)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('agent_bio')
                        ->label('Bio')
                        ->placeholder('Tell buyers about your experience and areas of expertise...')
                        ->rows(5)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('prc_license_number')
                        ->label('PRC License Number')
                        ->maxLength(50)
                        ->placeholder('e.g. 0012345'),

                    Forms\Components\CheckboxList::make('agent_specializations')
                        ->label('Specializations')
                        ->options([
                            'residential' => 'Residential',
                            'commercial' => 'Commercial',
                            'industrial' => 'Industrial',
                            'land' => 'Land / Lot',
                            'foreclosed' => 'Foreclosed Properties',
                            'leasing' => 'Leasing & Rentals',
                        ])
                        ->columns(3),
                ])->columns(2),
        ];
    }

    /**
     * @return array<Forms\Components\Component>
     */
    private function payoutInfoSchema(): array
    {
        return [
            Forms\Components\Section::make('Payout Information')
                ->description('Tell us how you want to receive your store earnings. This information is encrypted and only visible to platform administrators.')
                ->schema([
                    Forms\Components\Select::make('payout_method')
                        ->label('Payout Method')
                        ->options(collect(PayoutMethod::cases())->mapWithKeys(
                            fn (PayoutMethod $m): array => [$m->value => $m->label()]
                        ))
                        ->native(false)
                        ->live(),

                    Forms\Components\TextInput::make('payout_bank_name')
                        ->label('Bank Name')
                        ->placeholder('e.g. BDO, BPI, Metrobank')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('payout_method') === PayoutMethod::BankTransfer->value),

                    Forms\Components\TextInput::make('payout_account_name')
                        ->label('Account Name')
                        ->placeholder('Name on the account')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => filled($get('payout_method'))),

                    Forms\Components\TextInput::make('payout_account_number')
                        ->label('Account Number')
                        ->placeholder('Bank account number')
                        ->maxLength(50)
                        ->visible(fn (Get $get): bool => $get('payout_method') === PayoutMethod::BankTransfer->value),

                    Forms\Components\TextInput::make('payout_mobile_number')
                        ->label('Mobile Number')
                        ->placeholder('09XX XXX XXXX')
                        ->tel()
                        ->maxLength(20)
                        ->visible(fn (Get $get): bool => in_array($get('payout_method'), [
                            PayoutMethod::GCash->value,
                            PayoutMethod::Maya->value,
                        ])),
                ])->columns(2),
        ];
    }

    public function completeSetup(): void
    {
        $data = $this->form->getState();
        $store = auth()->user()->getStoreForPanel();

        $logoPath = is_array($data['logo'] ?? null)
            ? (collect($data['logo'])->first() ?? $store->logo)
            : ($data['logo'] ?? $store->logo);

        $bannerPath = is_array($data['banner'] ?? null)
            ? (collect($data['banner'])->first() ?? $store->banner)
            : ($data['banner'] ?? $store->banner);

        $agentPhotoPath = is_array($data['agent_photo'] ?? null)
            ? (collect($data['agent_photo'])->first() ?? $store->agent_photo)
            : ($data['agent_photo'] ?? $store->agent_photo);

        $store->collections()->sync($data['collection_ids'] ?? []);

        $payoutDetails = array_filter([
            'account_name' => $data['payout_account_name'] ?? null,
            'account_number' => $data['payout_account_number'] ?? null,
            'bank_name' => $data['payout_bank_name'] ?? null,
            'mobile_number' => $data['payout_mobile_number'] ?? null,
        ]) ?: null;

        $payload = [
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'logo' => $logoPath,
            'banner' => $bannerPath,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? $store->address,
            'social_links' => array_filter([
                'facebook' => $data['social_facebook'] ?? null,
                'instagram' => $data['social_instagram'] ?? null,
                'tiktok' => $data['social_tiktok'] ?? null,
                'lazada' => $data['social_lazada'] ?? null,
                'shopee' => $data['social_shopee'] ?? null,
            ]) ?: null,
            'agent_bio' => $data['agent_bio'] ?? null,
            'agent_photo' => $agentPhotoPath,
            'prc_license_number' => $data['prc_license_number'] ?? null,
            'agent_specializations' => filled($data['agent_specializations'] ?? null)
                ? $data['agent_specializations']
                : null,
            'operating_hours' => $data['hours'] ?? static::defaultHours(),
            'payout_method' => $data['payout_method'] ?? null,
            'payout_details' => $payoutDetails,
            'setup_completed_at' => now(),
        ];

        if ($store->isLipatBahay()) {
            $payload['moving_base_price'] = isset($data['moving_base_price'])
                ? (int) round(((float) $data['moving_base_price']) * 100)
                : null;
        }

        $store->update($payload);

        Notification::make()
            ->title('Portal setup complete! 🎉')
            ->body('Your store portal is ready. Welcome aboard!')
            ->success()
            ->send();

        $this->redirect($store->dashboardPath());
    }
}
