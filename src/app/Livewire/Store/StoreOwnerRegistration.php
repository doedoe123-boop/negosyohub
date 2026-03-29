<?php

namespace App\Livewire\Store;

use App\Models\Sector;
use App\Models\Store;
use App\Models\User;
use App\PhilippineIdType;
use App\Rules\ValidateUploadContent;
use App\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.guest-fullwidth')]
class StoreOwnerRegistration extends Component
{
    use WithFileUploads;

    /**
     * The selected industry sector (from route parameter).
     */
    public string $sector = '';

    /**
     * Current step of the multi-step form (1-5).
     */
    public int $step = 1;

    /**
     * Total number of steps.
     */
    public const TOTAL_STEPS = 5;

    // --- Step 1: Account Info ---

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|max:20')]
    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    // --- Step 2: Store Info ---

    #[Validate('required|string|max:255')]
    public string $storeName = '';

    #[Validate('required|string|max:255|unique:stores,slug')]
    public string $slug = '';

    #[Validate('required|string|max:1000')]
    public string $description = '';

    // --- Step 3: Store Address ---

    #[Validate('required|string|max:255')]
    public string $addressLine = '';

    #[Validate('required|string|max:255')]
    public string $city = '';

    #[Validate('required|string|max:20')]
    public string $postcode = '';

    // --- Step 4: Identity Verification ---

    #[Validate('required|string|in:passport,drivers_license,national_id,sss,philhealth,postal_id')]
    public string $idType = '';

    #[Validate('required|string|max:100')]
    public string $idNumber = '';

    // --- Step 5: Sector Compliance Documents ---

    /** @var array<string, TemporaryUploadedFile|null> */
    public array $complianceFiles = [];

    /**
     * Mount the component and accept sector from route parameter.
     */
    public function mount(string $sector): void
    {
        $sectorModel = Sector::active()->where('slug', $sector)->first();

        if ($sectorModel) {
            $this->sector = $sector;
            $this->initComplianceFiles($sectorModel);
        } else {
            $this->redirect(route('register.sector'));
        }
    }

    /**
     * Initialize compliance file slots based on sector's documents from DB.
     */
    private function initComplianceFiles(?Sector $sectorModel = null): void
    {
        $sectorModel ??= Sector::active()->where('slug', $this->sector)->with('documents')->first();

        if (! $sectorModel) {
            return;
        }

        foreach ($sectorModel->documentsArray() as $doc) {
            $this->complianceFiles[$doc['key']] = null;
        }
    }

    /**
     * Get the resolved Sector model for the current sector slug.
     */
    public function getSectorModelProperty(): ?Sector
    {
        return Sector::active()->where('slug', $this->sector)->with('documents')->first();
    }

    /**
     * Get the required documents for the current sector from DB.
     *
     * @return array<int, array{key: string, label: string, description: string, required: bool, mimes: string}>
     */
    public function getSectorDocumentsProperty(): array
    {
        return $this->sectorModel?->documentsArray() ?? [];
    }

    /**
     * Validation rules for each step.
     *
     * @return array<int, list<string>>
     */
    private function stepFields(): array
    {
        return [
            1 => ['name', 'email', 'phone', 'password', 'password_confirmation'],
            2 => ['storeName', 'slug', 'description'],
            3 => ['addressLine', 'city', 'postcode'],
            4 => ['idType', 'idNumber'],
            5 => $this->getComplianceFieldNames(),
        ];
    }

    /**
     * Get the field names for compliance documents for validation.
     *
     * @return list<string>
     */
    private function getComplianceFieldNames(): array
    {
        $fields = [];

        foreach ($this->sectorDocuments as $doc) {
            $fields[] = "complianceFiles.{$doc['key']}";
        }

        return $fields;
    }

    /**
     * Step labels for the progress indicator.
     *
     * @return array<int, string>
     */
    public function getStepLabelsProperty(): array
    {
        return [
            1 => 'Account',
            2 => 'Store',
            3 => 'Address',
            4 => 'Identity',
            5 => 'Compliance',
        ];
    }

    /**
     * Dynamic validation rules for compliance documents.
     *
     * @return array<string, string>
     */
    public function getComplianceRulesProperty(): array
    {
        $rules = [];

        foreach ($this->sectorDocuments as $doc) {
            $required = $doc['required'] ? 'required' : 'nullable';
            $rules["complianceFiles.{$doc['key']}"] = [
                $required,
                'file',
                "mimes:{$doc['mimes']}",
                'max:5120',
                new ValidateUploadContent,
            ];
        }

        return $rules;
    }

    /**
     * Advance to the next step after validating current fields.
     */
    public function nextStep(): void
    {
        if (! $this->validateCurrentStep()) {
            return;
        }

        $this->step = min($this->step + 1, self::TOTAL_STEPS);
    }

    /**
     * Go back to the previous step.
     */
    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    /**
     * Navigate to a specific step (only if completed or current).
     */
    public function goToStep(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    /**
     * Validate only the fields belonging to the current step.
     */
    private function validateCurrentStep(): bool
    {
        $fields = $this->stepFields()[$this->step] ?? [];
        $rules = $this->stepRules($this->step);

        $this->resetValidation($fields);

        if ($rules !== []) {
            $this->validate($rules);
        }

        if ($this->step === 4) {
            return $this->validateIdNumberFormat();
        }

        return true;
    }

    /**
     * Get the validation rules for a specific step.
     *
     * @return array<string, mixed>
     */
    private function stepRules(int $step): array
    {
        if ($step === 5) {
            return $this->complianceRules;
        }

        return Arr::only($this->rules(), $this->stepFields()[$step] ?? []);
    }

    /**
     * Auto-generate a slug when the store name changes.
     */
    public function updatedStoreName(string $value): void
    {
        $this->slug = Str::slug($value);
    }

    /**
     * Get the format hint for the currently selected ID type.
     */
    public function getIdFormatHintProperty(): string
    {
        $idType = PhilippineIdType::tryFrom($this->idType);

        return $idType ? $idType->formatHint() : '';
    }

    /**
     * Validate the ID number against the selected ID type's regex pattern.
     * Returns false and adds an error if invalid, true if valid or no type selected.
     */
    private function validateIdNumberFormat(): bool
    {
        $idType = PhilippineIdType::tryFrom($this->idType);

        if ($idType && $this->idNumber && ! preg_match($idType->pattern(), $this->idNumber)) {
            $this->addError('idNumber', "Invalid format for {$idType->label()}. Expected: {$idType->formatHint()}");

            return false;
        }

        return true;
    }

    /**
     * Real-time validation when idNumber changes.
     */
    public function updatedIdNumber(): void
    {
        $this->resetValidation('idNumber');
        $this->validateOnly('idNumber');
        $this->validateIdNumberFormat();
    }

    /**
     * Clear idNumber error when idType changes so stale messages don't show.
     */
    public function updatedIdType(): void
    {
        $this->resetErrorBag('idNumber');
    }

    /**
     * Clear file validation errors as soon as a compliance document is uploaded.
     *
     * With WithFileUploads on an array property, Livewire 3 calls this hook
     * with the nested key (e.g. 'dti_sec_registration') after the temp file is set.
     * We reset both the specific key and scan all slots to clear any that now have a file.
     */
    public function updatedComplianceFiles(string $key = ''): void
    {
        // Clear the specific key if provided
        if ($key !== '') {
            $this->resetValidation("complianceFiles.{$key}");
        }

        // Also sweep all slots — clear errors for any slot that now has a file
        foreach ($this->complianceFiles as $slot => $file) {
            if ($file !== null) {
                $this->resetValidation("complianceFiles.{$slot}");
            }
        }
    }

    /**
     * Register the store owner and create their store.
     */
    public function register(): void
    {
        // Validate steps 1-4 (attributes) + step 5 (compliance docs)
        $this->validate(array_merge(
            $this->rules(),
            $this->complianceRules,
        ));

        // Validate ID number format based on selected type
        if (! $this->validateIdNumberFormat()) {
            return;
        }

        // Store compliance documents
        $compliancePaths = [];

        foreach ($this->sectorDocuments as $doc) {
            $file = $this->complianceFiles[$doc['key']] ?? null;

            if ($file) {
                $compliancePaths[$doc['key']] = [
                    'label' => $doc['label'],
                    'path' => $file->store("compliance/{$this->sector}", 'local'),
                    'required' => $doc['required'],
                ];
            }
        }

        $user = DB::transaction(function () use ($compliancePaths): User {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'role' => UserRole::StoreOwner,
            ]);

            Store::create([
                'user_id' => $user->id,
                'name' => $this->storeName,
                'slug' => $this->slug,
                'description' => $this->description,
                'sector' => $this->sector,
                'address' => [
                    'line_one' => $this->addressLine,
                    'city' => $this->city,
                    'postcode' => $this->postcode,
                ],
                'id_type' => $this->idType,
                'id_number' => $this->idNumber,
                'compliance_documents' => $compliancePaths,
            ]);

            $user->assignRole('store_owner');

            return $user;
        });

        try {
            event(new Registered($user));
        } catch (\Throwable $exception) {
            Log::warning('Store owner registration completed without verification email.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);

            session()->flash('warning', 'Your application was submitted, but we could not send the verification email right now. Please contact support if you do not receive it shortly.');
        }

        // Clear browser localStorage for this form
        $this->dispatch('registration-complete');

        session()->flash('success', 'Your store application has been submitted! We will review your documents and notify you via email within 3–5 business days.');

        $this->redirect(route('register.store-owner.success'));
    }

    /**
     * Override rules to include compliance documents.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'storeName' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:stores,slug',
            'description' => 'required|string|max:1000',
            'addressLine' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postcode' => 'required|string|max:20',
            'idType' => 'required|string|in:passport,drivers_license,national_id,sss,philhealth,postal_id',
            'idNumber' => 'required|string|max:100',
        ];
    }

    public function render(): View
    {
        return view('livewire.store.store-owner-registration');
    }
}
