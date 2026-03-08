<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            [
                'name' => 'E-Commerce',
                'slug' => 'ecommerce',
                'template' => 'ecommerce',
                'description' => 'Online retail stores, marketplaces, and digital product sellers.',
                'icon' => 'heroicon-o-shopping-cart',
                'color' => 'orange',
                'registration_button_text' => 'Register as Online Store',
                'is_active' => true,
                'sort_order' => 1,
                'documents' => [
                    ['key' => 'dti_sec_registration', 'label' => 'DTI / SEC Registration',           'description' => 'Certificate of business name registration (DTI) or SEC incorporation papers.',        'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 1],
                    ['key' => 'business_permit',       'label' => "Mayor's Permit / Business Permit", 'description' => 'Valid local business permit from your city or municipality.',                        'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 2],
                    ['key' => 'bir_registration',      'label' => 'BIR Certificate of Registration',  'description' => 'BIR Form 2303 — Certificate of Registration.',                                       'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 3],
                    ['key' => 'store_front_photo',     'label' => 'Store / Warehouse Photo',          'description' => 'Clear photo of your physical store, warehouse, or fulfilment centre.',               'is_required' => false, 'mimes' => 'jpg,jpeg,png',     'sort_order' => 4],
                ],
            ],
            [
                'name' => 'Real Estate & Property',
                'slug' => 'real_estate',
                'template' => 'real_estate',
                'description' => 'Property listings, real estate services, and property management.',
                'icon' => 'heroicon-o-home-modern',
                'color' => 'emerald',
                'registration_button_text' => 'Join as Real Estate Agent',
                'is_active' => true,
                'sort_order' => 2,
                'documents' => [
                    ['key' => 'dti_sec_registration', 'label' => 'DTI / SEC Registration',           'description' => 'Certificate of business name registration (DTI) or SEC incorporation papers.',  'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 1],
                    ['key' => 'business_permit',       'label' => "Mayor's Permit / Business Permit", 'description' => 'Valid local business permit from your city or municipality.',                    'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 2],
                    ['key' => 'bir_registration',      'label' => 'BIR Certificate of Registration',  'description' => 'BIR Form 2303 — Certificate of Registration.',                                   'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 3],
                    ['key' => 'prc_broker_license',    'label' => 'PRC Real Estate Broker License',   'description' => 'Professional license from PRC for real estate brokerage.',                       'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 4],
                    ['key' => 'dhsud_license',         'label' => 'DHSUD License to Sell',            'description' => 'DHSUD license to sell for property developers.',                                 'is_required' => false, 'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 5],
                ],
            ],
            [
                'name' => 'Paupahan (Rentals)',
                'slug' => 'paupahan',
                'template' => 'rental',
                'description' => 'Residential rental listings — apartments, houses, bedspace, and condos for rent.',
                'icon' => 'heroicon-o-building-office-2',
                'color' => 'sky',
                'registration_button_text' => 'List Your Rental Property',
                'is_active' => true,
                'sort_order' => 3,
                'documents' => [
                    ['key' => 'bir_tin',       'label' => 'BIR TIN / Certificate of Registration', 'description' => 'Proof of BIR registration (Form 2303 or TIN card).',                    'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 1],
                    ['key' => 'government_id', 'label' => 'Valid Government-Issued ID',            'description' => 'Any government-issued photo ID (passport, driver\'s license, etc.).', 'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 2],
                    ['key' => 'proof_of_ownership', 'label' => 'Proof of Property Ownership',     'description' => 'TCT, CCT, tax declaration, or authorisation letter from the owner.',   'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Lipat Bahay (Moving Service)',
                'slug' => 'lipat_bahay',
                'template' => 'logistics',
                'description' => 'House-moving and relocation services — packing, transport, and unpacking.',
                'icon' => 'heroicon-o-truck',
                'color' => 'violet',
                'registration_button_text' => 'Register as Moving Service',
                'is_active' => true,
                'sort_order' => 4,
                'documents' => [
                    ['key' => 'dti_sec_registration', 'label' => 'DTI / SEC Registration',           'description' => 'Certificate of business name registration (DTI) or SEC incorporation papers.',    'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 1],
                    ['key' => 'business_permit',       'label' => "Mayor's Permit / Business Permit", 'description' => 'Valid local business permit from your city or municipality.',                      'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 2],
                    ['key' => 'bir_registration',      'label' => 'BIR Certificate of Registration',  'description' => 'BIR Form 2303 — Certificate of Registration.',                                     'is_required' => true,  'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 3],
                    ['key' => 'vehicle_photo',         'label' => 'Fleet / Vehicle Photo(s)',          'description' => 'Clear photos of at least one moving vehicle in your fleet.',                       'is_required' => true,  'mimes' => 'jpg,jpeg,png',     'sort_order' => 4],
                    ['key' => 'insurance_cert',        'label' => 'Cargo / Transit Insurance Certificate', 'description' => 'Insurance certificate covering customer belongings during transit.',          'is_required' => false, 'mimes' => 'pdf,jpg,jpeg,png', 'sort_order' => 5],
                ],
            ],
        ];

        foreach ($sectors as $sectorData) {
            $documents = $sectorData['documents'];
            unset($sectorData['documents']);

            $sector = Sector::updateOrCreate(['slug' => $sectorData['slug']], $sectorData);

            foreach ($documents as $doc) {
                $sector->documents()->updateOrCreate(['key' => $doc['key']], $doc);
            }
        }
    }
}
