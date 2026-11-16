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

        $locale = app()->getLocale();
        $isArabic = ($locale === 'ar');

        // Create Mpdf instance with US-Letter format matching the master PDF (215.9mm x 279.4mm)
        $mpdf = new Mpdf([
            'format' => [215.9, 279.4],
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'tempDir' => storage_path('app/temp'),
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $viewName = $isArabic ? 'contracts.vendor-agreement-ar' : 'contracts.vendor-agreement-en';
        if (view()->exists($viewName)) {
            $html = view($viewName, [
                'vendor_legal_name' => $vendorLegalName,
                'trade_license_number' => $tradeLicenseNumber,
                'accepted_at_uae' => $formattedDate,
                'store' => $store,
                'vendor' => $vendor,
            ])->render();
            $mpdf->WriteHTML($html);
        } else {
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

                // Page 5: Add digital acceptance record / verification box (without Authorised Representative, Agreement Reference, Reference Version)
                if ($pageNo === 5) {
                    if ($isArabic) {
                        $stampHtml = '
                        <div dir="rtl" style="position: absolute; left: 20mm; top: 165mm; width: 175mm; padding: 14px 18px; border: 1.5px solid #0052cc; background-color: #f8faff; font-family: DejaVu Sans, Tahoma, Arial, sans-serif; border-radius: 6px; text-align: right;">
                            <div style="font-size: 12pt; font-weight: bold; color: #0052cc; margin-bottom: 6px;">
                                &#10004; سجل قبول الاتفاقية الإلكترونية
                            </div>
                            <div style="font-size: 8.5pt; color: #555555; margin-bottom: 10px; line-height: 1.4;">
                                تم قبول وتوثيق هذه الاتفاقية إلكترونياً عبر تطبيق منصة زيادة وفقاً للتشريعات والأنظمة السارية بدولة الإمارات العربية المتحدة.
                            </div>
                            <table style="width: 100%; font-size: 9pt; line-height: 1.8; color: #111111; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 38%; font-weight: bold; padding: 2px 0;">الاسم القانوني للتاجر / المنشأة:</td>
                                    <td style="padding: 2px 0;">' . htmlspecialchars($vendorLegalName, ENT_QUOTES, 'UTF-8') . '</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; padding: 2px 0;">رقم الرخصة التجارية:</td>
                                    <td style="padding: 2px 0;">' . htmlspecialchars($tradeLicenseNumber, ENT_QUOTES, 'UTF-8') . '</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; padding: 2px 0;">تاريخ ووقت القبول (توقيت الإمارات):</td>
                                    <td style="padding: 2px 0;">' . htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') . '</td>
                                </tr>
                            </table>
                            <div style="font-size: 7.5pt; color: #777777; margin-top: 8px; border-top: 1px solid #e1e7f0; padding-top: 5px;">
                                الطرف الأول: زيادة للحلول الرقمية ش.م.ح | وثيقة إلكترونية معتمدة وملزمة قانوناً (المتجر #' . $store->id . ')
                            </div>
                        </div>';
                    } else {
                        $stampHtml = '
                        <div style="position: absolute; left: 20mm; top: 165mm; width: 175mm; padding: 14px 18px; border: 1.5px solid #0052cc; background-color: #f8faff; font-family: Helvetica, Arial, sans-serif; border-radius: 6px;">
                            <div style="font-size: 12pt; font-weight: bold; color: #0052cc; margin-bottom: 6px; letter-spacing: 0.5px;">
                                &#10004; ELECTRONIC AGREEMENT & ACCEPTANCE RECORD
                            </div>
                            <div style="font-size: 8.5pt; color: #555555; margin-bottom: 10px; line-height: 1.4;">
                                This agreement has been electronically accepted and executed through the ZYADA application in accordance with the laws of the United Arab Emirates.
                            </div>
                            <table style="width: 100%; font-size: 9pt; line-height: 1.8; color: #111111; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 38%; font-weight: bold; padding: 2px 0;">Vendor Legal Name:</td>
                                    <td style="padding: 2px 0;">' . htmlspecialchars($vendorLegalName, ENT_QUOTES, 'UTF-8') . '</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; padding: 2px 0;">Trade License Number:</td>
                                    <td style="padding: 2px 0;">' . htmlspecialchars($tradeLicenseNumber, ENT_QUOTES, 'UTF-8') . '</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: bold; padding: 2px 0;">Acceptance Date & Time (UAE):</td>
                                    <td style="padding: 2px 0;">' . htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') . '</td>
                                </tr>
                            </table>
                            <div style="font-size: 7.5pt; color: #777777; margin-top: 8px; border-top: 1px solid #e1e7f0; padding-top: 5px;">
                                Platform: Zyada Digital Solutions - F.Z.C. | Digital Contract Acceptance Confirmation (Store #' . $store->id . ')
                            </div>
                        </div>';
                    }
                    $mpdf->WriteHTML($stampHtml);
                }
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

