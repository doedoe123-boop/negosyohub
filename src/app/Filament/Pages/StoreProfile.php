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
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;

class StoreProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Store Profile';

    protected static string $view = 'filament.pages.store-profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $store = auth()->user()->getStoreForPanel();

        if (! $store) {
            abort(403);
        }

        $social = $store->social_links ?? [];
        $hours = $store->operating_hours ?? SetupWizard::defaultHours();

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
            'hours' => $hours,
            'payout_method' => $store->payout_method?->value,
            'payout_account_name' => $store->payout_details['account_name'] ?? null,
            'payout_account_number' => $store->payout_details['account_number'] ?? null,
            'payout_bank_name' => $store->payout_details['bank_name'] ?? null,
            'payout_mobile_number' => $store->payout_details['mobile_number'] ?? null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Information')
                    ->description('Your public-facing store details.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tagline')
                            ->label('Tagline')
                            ->placeholder('Your everyday marketplace')
                            ->maxLength(150)
                            ->helperText('A short phrase shown beneath your store name.'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(4)
                            ->placeholder('Describe your store to customers...')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('logo')
                            ->label('Store Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('stores/logos')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                            ->helperText('Recommended: square image, at least 256×256 px. Max 2 MB.'),

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

                        Forms\Components\TextInput::make('phone')
                            ->label('Business Phone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->placeholder('https://yourstore.com')
                            ->maxLength(500),
                    ])->columns(2),

                Forms\Components\Section::make('Address')
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
                    ->description('Add links so customers can find you on other platforms.')
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
                    ])->columns(2)
                    ->hidden(fn () => in_array('agent_profile', auth()->user()?->getStoreForPanel()?->sectorModel()?->supportedFeatures() ?? [], true)),

                Forms\Components\Section::make('Business Hours')
                    ->description('Toggle each day and set your opening and closing times.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('_hdr_day')->label('')->content('Day'),
                                Forms\Components\Placeholder::make('_hdr_open')->label('')->content('Opens'),
                                Forms\Components\Placeholder::make('_hdr_close')->label('')->content('Closes'),
                            ]),
                        ...$this->dayRow('monday', 'Monday'),
                        ...$this->dayRow('tuesday', 'Tuesday'),
                        ...$this->dayRow('wednesday', 'Wednesday'),
                        ...$this->dayRow('thursday', 'Thursday'),
                        ...$this->dayRow('friday', 'Friday'),
                        ...$this->dayRow('saturday', 'Saturday'),
                        ...$this->dayRow('sunday', 'Sunday'),
                    ])
                    ->hidden(fn () => in_array('agent_profile', auth()->user()?->getStoreForPanel()?->sectorModel()?->supportedFeatures() ?? [], true)),

                Forms\Components\Section::make('Payout Information')
                    ->description('How you receive your store earnings. This information is encrypted.')
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
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = auth()->user()->getStoreForPanel();

        $store->collections()->sync($data['collection_ids'] ?? []);

        $payoutDetails = array_filter([
            'account_name' => $data['payout_account_name'] ?? null,
            'account_number' => $data['payout_account_number'] ?? null,
            'bank_name' => $data['payout_bank_name'] ?? null,
            'mobile_number' => $data['payout_mobile_number'] ?? null,
        ]) ?: null;

        $store->update([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'logo' => $data['logo'] ?? $store->logo,
            'banner' => $data['banner'] ?? $store->banner,
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
            'operating_hours' => $data['hours'] ?? SetupWizard::defaultHours(),
            'payout_method' => $data['payout_method'] ?? null,
            'payout_details' => $payoutDetails,
        ]);

        Notification::make()
            ->title('Store profile updated')
            ->success()
            ->send();
    }

    /**
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
}
