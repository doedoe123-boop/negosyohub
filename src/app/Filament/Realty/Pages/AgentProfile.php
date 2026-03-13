<?php

namespace App\Filament\Realty\Pages;

use App\SectorTemplate;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AgentProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.realty.pages.agent-profile';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return self::isRentalStore() ? 'Landlord Profile' : 'Agent Profile';
    }

    public function getTitle(): string
    {
        return self::isRentalStore() ? 'Landlord Profile' : 'Agent Profile';
    }

    private static function isRentalStore(): bool
    {
        return auth()->user()?->getStoreForPanel()?->template() === SectorTemplate::Rental;
    }

    public function mount(): void
    {
        $store = auth()->user()->getStoreForPanel();

        $this->form->fill([
            'agent_bio' => $store->agent_bio,
            'agent_photo' => $store->agent_photo,
            'agent_certifications' => $store->agent_certifications ?? [],
            'prc_license_number' => $store->prc_license_number,
            'agent_specializations' => $store->agent_specializations ?? [],
            'social_links' => $store->social_links ?? [],
        ]);
    }

    public function form(Form $form): Form
    {
        $isRental = self::isRentalStore();

        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->description($isRental ? 'Your public-facing landlord profile.' : 'Your public-facing agent profile.')
                    ->schema([
                        Forms\Components\Textarea::make('agent_bio')
                            ->label('Bio / About Me')
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder($isRental
                                ? 'Tell potential tenants about yourself and your rental properties...'
                                : 'Tell potential clients about yourself, your experience, and what makes you different...')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('agent_photo')
                            ->label('Profile Photo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('stores/agents')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->helperText('Recommended: square professional headshot, at least 256×256 px. Max 2 MB.'),

                        Forms\Components\TextInput::make('prc_license_number')
                            ->label('PRC License Number')
                            ->maxLength(50)
                            ->placeholder('e.g. 0012345')
                            ->visible(! $isRental),
                    ])->columns(2),

                Forms\Components\Section::make('Expertise')
                    ->schema([
                        Forms\Components\TagsInput::make('agent_certifications')
                            ->label($isRental ? 'Credentials' : 'Certifications & Licenses')
                            ->placeholder($isRental ? 'Add credential...' : 'Add certification...')
                            ->suggestions($isRental
                                ? [
                                    'Verified Property Owner',
                                    'DHSUD Registered Landlord',
                                    'Barangay Business Permit Holder',
                                    'BIR Registered Lessor',
                                    'Accredited Boarding House Operator',
                                ]
                                : [
                                    'Licensed Real Estate Broker',
                                    'Licensed Real Estate Appraiser',
                                    'Licensed Real Estate Consultant',
                                    'Certified International Property Specialist (CIPS)',
                                    'Accredited Buyer Representative (ABR)',
                                    'Seller Representative Specialist (SRS)',
                                    'Real Estate Negotiation Expert (RENE)',
                                    'DHSUD Accredited Broker',
                                ]),

                        Forms\Components\TagsInput::make('agent_specializations')
                            ->label('Specializations')
                            ->placeholder('Add specialization...')
                            ->suggestions($isRental
                                ? [
                                    'Apartment Rentals',
                                    'Room Rentals / Bedspacer',
                                    'Condo Leasing',
                                    'Boarding Houses',
                                    'Commercial Space Leasing',
                                    'Dormitory / Student Housing',
                                    'Short-Term Rentals',
                                    'Long-Term Leasing',
                                    'Furnished Rentals',
                                    'Pet-Friendly Rentals',
                                ]
                                : [
                                    'Residential Sales',
                                    'Commercial Leasing',
                                    'Luxury Properties',
                                    'Condominiums',
                                    'Land & Lots',
                                    'Industrial Properties',
                                    'Pre-Selling Projects',
                                    'Foreclosed Properties',
                                    'Vacation Homes',
                                    'Property Management',
                                    'Investment Consulting',
                                    'Relocation Services',
                                ]),
                    ])->columns(2),

                Forms\Components\Section::make('Social Media Links')
                    ->description('Your online presence (all optional).')
                    ->schema([
                        Forms\Components\TextInput::make('social_links.facebook')
                            ->label('Facebook')
                            ->url()
                            ->prefix('facebook.com/')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('social_links.instagram')
                            ->label('Instagram')
                            ->url()
                            ->prefix('instagram.com/')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('social_links.linkedin')
                            ->label('LinkedIn')
                            ->url()
                            ->prefix('linkedin.com/')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('social_links.tiktok')
                            ->label('TikTok')
                            ->url()
                            ->prefix('tiktok.com/')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('social_links.youtube')
                            ->label('YouTube')
                            ->url()
                            ->maxLength(500),

                        Forms\Components\TextInput::make('social_links.website')
                            ->label('Personal Website')
                            ->url()
                            ->maxLength(500),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = auth()->user()->getStoreForPanel();

        $store->update([
            'agent_bio' => $data['agent_bio'],
            'agent_photo' => $data['agent_photo'],
            'agent_certifications' => $data['agent_certifications'],
            'prc_license_number' => $data['prc_license_number'],
            'agent_specializations' => $data['agent_specializations'],
            'social_links' => $data['social_links'],
        ]);

        Notification::make()
            ->title('Profile updated')
            ->success()
            ->send();
    }
}
