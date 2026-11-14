<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class VendorContractService
{
    /**
     * The master agreement PDF template path.
     */
    public const TEMPLATE_FILENAME = 'ZYADA_Vendor_Agreement_Final.pdf';

    /**
     * Get the master contract template path.
     */
    public static function getMasterTemplatePath(): string
    {
        $storagePath = storage_path('app/' . self::TEMPLATE_FILENAME);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        $publicStoragePath = storage_path('app/public/store/contracts/' . self::TEMPLATE_FILENAME);
        if (file_exists($publicStoragePath)) {
            return $publicStoragePath;
        }

        return base_path(self::TEMPLATE_FILENAME);
    }

    /**
     * Generate an individualized PDF contract for the given Store and Vendor.
     *
     * @param Store $store
     * @param Vendor $vendor
     * @return string Generated filename
     */
    public function generate(Store $store, Vendor $vendor): string
    {
        $templatePath = self::getMasterTemplatePath();

        if (!file_exists($templatePath)) {
            \Log::error("VendorContractService: Master contract template not found at {$templatePath}");
            throw new \RuntimeException("Contract template not found: " . self::TEMPLATE_FILENAME);
        }

        // Determine vendor legal name and trade license number
        $vendorLegalName = trim(($vendor->f_name ?? '') . ' ' . ($vendor->l_name ?? ''));
        if (!empty($store->name)) {
            $vendorLegalName .= ' (' . $store->name . ')';
        }

        $tradeLicenseNumber = $store->trade_license_number ?: ($store->tin ?: 'N/A');

        $acceptedAt = $store->store_contract_accepted_at ?: now();
        $formattedDate = $acceptedAt->format('d/m/Y H:i:s T');

        // Create Mpdf instance with US-Letter format matching the master PDF (215.9mm x 279.4mm)
        $mpdf = new Mpdf([
            'format' => [215.9, 279.4],
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'tempDir' => storage_path('app/temp'),
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $pageCount = $mpdf->setSourceFile($templatePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $mpdf->importPage($pageNo);
            $mpdf->AddPage();
            $mpdf->useTemplate($tplId, 0, 0, 215.9, 279.4);

            // Page 1: Overlay the Second Party details (replacing placeholders)
            if ($pageNo === 1) {
                $overlayHtml = '
                <div style="position: absolute; left: 19.1mm; top: 119mm; width: 175mm; height: 18mm; background-color: #ffffff; font-family: Helvetica, Arial, sans-serif; font-size: 10pt; line-height: 1.6; color: #000000;">
                    <div><span style="font-weight: bold;">Registered Business Name:</span> ' . htmlspecialchars($vendorLegalName, ENT_QUOTES, 'UTF-8') . '</div>
                    <div style="margin-top: 2.2mm;"><span style="font-weight: bold;">Trade License Number:</span> ' . htmlspecialchars($tradeLicenseNumber, ENT_QUOTES, 'UTF-8') . '</div>
                </div>';
                $mpdf->WriteHTML($overlayHtml);
            }

            // Page 5: Add digital acceptance stamp / verification box
            if ($pageNo === 5) {
                $stampHtml = '
                <div style="position: absolute; left: 19.1mm; top: 148mm; width: 175mm; padding: 10px; border: 1.5px solid #0052cc; background-color: #f4f8ff; font-family: Helvetica, Arial, sans-serif; font-size: 9pt; line-height: 1.5; color: #172b4d; border-radius: 4px;">
                    <div style="font-weight: bold; color: #0052cc; font-size: 10pt; margin-bottom: 4px;">
                        &#10004; DIGITALLY ACCEPTED & SIGNED ELECTRONICALLY
                    </div>
                    <div><strong>Accepted By:</strong> ' . htmlspecialchars($vendorLegalName, ENT_QUOTES, 'UTF-8') . '</div>
                    <div><strong>Trade License No:</strong> ' . htmlspecialchars($tradeLicenseNumber, ENT_QUOTES, 'UTF-8') . '</div>
                    <div><strong>Email:</strong> ' . htmlspecialchars($vendor->email ?? '', ENT_QUOTES, 'UTF-8') . ' | <strong>Phone:</strong> ' . htmlspecialchars($vendor->phone ?? '', ENT_QUOTES, 'UTF-8') . '</div>
                    <div><strong>Date & Time of Acceptance:</strong> ' . htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') . '</div>
                    <div><strong>Platform:</strong> Zyada Digital Solutions - F.Z.C. (Store ID #' . $store->id . ')</div>
                </div>';
                $mpdf->WriteHTML($stampHtml);
            }
        }

        // Output file to storage/app/public/store/contracts
        $destDir = storage_path('app/public/store/contracts');
        if (!File::isDirectory($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        $filename = 'vendor_contract_' . $store->id . '_' . Str::random(12) . '.pdf';
        $fullPath = $destDir . '/' . $filename;

        $mpdf->Output($fullPath, Destination::FILE);

        // Update the store record
        $store->store_contract_pdf = $filename;
        if (!$store->store_contract_accepted_at) {
            $store->store_contract_accepted_at = $acceptedAt;
        }
        $store->store_contract_template_accepted = self::TEMPLATE_FILENAME;
        $store->save();

        return $filename;
    }
}

