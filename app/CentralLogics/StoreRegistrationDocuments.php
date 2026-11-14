<?php

namespace App\CentralLogics;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreRegistrationDocuments
{
    public static function validationRules(bool $required = true): array
    {
        $fileRule = $required ? 'required' : 'nullable';
        return [
            'trade_license' => $fileRule . '|file|mimes:pdf,jpg,jpeg,png,webp|max:2048',
            'emirates_id' => $fileRule . '|image|max:2048',
            'passport_copy' => $fileRule . '|image|max:2048',
            'iban_letter' => $fileRule . '|file|mimes:pdf,jpg,jpeg,png,webp|max:2048',
        ];
    }

    public static function save(Store $store, Request $request, bool $replaceExisting = false): void
    {
        foreach (array_keys(self::validationRules()) as $document) {
            if (!$request->hasFile($document)) {
                continue;
            }

            $file = $request->file($document);
            $extension = $file->getClientOriginalExtension();
            $store->{$document} = $replaceExisting && $store->{$document}
                ? Helpers::update('store/', $store->{$document}, $extension, $file)
                : Helpers::upload('store/', $extension, $file);
        }
    }
}
