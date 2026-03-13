<?php

namespace App\Filament\Realty\Pages;

use App\SectorTemplate;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class MortgageSettings extends Page implements HasForms
{
    /**
     * Rental stores do not use the mortgage calculator.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->getStoreForPanel()?->template() !== SectorTemplate::Rental;
    }

    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Mortgage Calculator';

    protected static string $view = 'filament.realty.pages.mortgage-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $store = auth()->user()->getStoreForPanel();

        $this->form->fill([
            'default_interest_rate' => $store->default_interest_rate ?? 6.50,
            'default_loan_term_years' => $store->default_loan_term_years ?? 20,
            'default_down_payment_percent' => $store->default_down_payment_percent ?? 20.00,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Default Mortgage Rates')
                    ->description('These defaults will pre-fill the mortgage calculator on your public listing pages. Buyers can adjust them.')
                    ->schema([
                        Forms\Components\TextInput::make('default_interest_rate')
                            ->label('Annual Interest Rate')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0.01)
                            ->maxValue(30)
                            ->step(0.01)
                            ->required()
                            ->helperText('Current PH bank rates: ~6–8% for housing loans.'),

                        Forms\Components\TextInput::make('default_loan_term_years')
                            ->label('Loan Term')
                            ->numeric()
                            ->suffix('years')
                            ->minValue(1)
                            ->maxValue(30)
                            ->required()
                            ->helperText('Common terms: 5, 10, 15, 20, 25 years.'),

                        Forms\Components\TextInput::make('default_down_payment_percent')
                            ->label('Down Payment')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->required()
                            ->helperText('Typical range: 10–30% of property price.'),
                    ])->columns(3),

                Forms\Components\Section::make('Preview')
                    ->description('Based on a ₱5,000,000 property with your defaults above.')
                    ->schema([
                        Forms\Components\Placeholder::make('preview_note')
                            ->label('')
                            ->content('The mortgage calculator will be displayed on each listing page, allowing buyers to estimate their monthly payments. Your default values set above will be pre-filled for convenience.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = auth()->user()->getStoreForPanel();

        $store->update([
            'default_interest_rate' => $data['default_interest_rate'],
            'default_loan_term_years' => $data['default_loan_term_years'],
            'default_down_payment_percent' => $data['default_down_payment_percent'],
        ]);

        Notification::make()
            ->title('Mortgage settings updated')
            ->success()
            ->send();
    }
}
