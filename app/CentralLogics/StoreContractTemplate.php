<?php

namespace App\CentralLogics;

class StoreContractTemplate
{
    public static function forLocale(array $settings, ?string $locale): ?string
    {
        $language = strtolower(substr($locale ?: 'en', 0, 2));
        if (!in_array($language, ['ar', 'en'], true)) {
            $language = 'en';
        }

        $localizedTemplate = $settings['store_contract_template_' . $language] ?? null;
        if ($localizedTemplate) {
            return $localizedTemplate;
        }

        $englishTemplate = $settings['store_contract_template_en'] ?? null;
        if ($englishTemplate) {
            return $englishTemplate;
        }

        if (!empty($settings['store_contract_template'])) {
            return $settings['store_contract_template'];
        }

        // Default fallback to master contract agreement if present in public storage
        if (file_exists(storage_path('app/public/store/contracts/' . \App\Services\VendorContractService::TEMPLATE_FILENAME))) {
            return \App\Services\VendorContractService::TEMPLATE_FILENAME;
        }

        return null;
    }
}
