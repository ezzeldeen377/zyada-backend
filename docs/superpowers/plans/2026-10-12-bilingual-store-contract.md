# Bilingual Store Contract Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let admins maintain English and Arabic store contract templates and return the requested language from the existing config field.

**Architecture:** Store localized filenames in existing `business_settings` rows, retaining the old `store_contract_template` key as the English fallback. The admin form renders both language fields and previews stored PDFs; config and registration select the file by request locale and fall back to English.

**Tech Stack:** Laravel controllers, BusinessSetting key/value storage, Blade, localization middleware, public storage.

---

### Task 1: Admin upload and preview

**Files:**
- Modify: `app/Http/Controllers/Admin/BusinessSettingsController.php`
- Modify: `resources/views/admin-views/business-settings/store-index.blade.php`

- [ ] Add optional PDF/DOC/DOCX validation for `store_contract_template_en` and `store_contract_template_ar`.
- [ ] Save each supplied file under its language-specific setting key, deleting only the prior file for that same key.
- [ ] Render separate English and Arabic file inputs and preview the existing PDFs inline; provide an open link for Word documents.
- [ ] Keep the legacy template visible as the English current file when no localized English value exists.

### Task 2: Locale-aware config and registration acceptance

**Files:**
- Modify: `app/Http/Controllers/Api/V1/ConfigController.php`
- Modify: `app/Http/Controllers/VendorController.php`
- Modify: `resources/views/vendor-views/auth/general-info.blade.php`

- [ ] Include both localized setting keys in the cached config settings query.
- [ ] Return the selected localized URL as `store_contract_template_url`, use English when locale is absent or unsupported, and use legacy English when no new English setting exists.
- [ ] Send the landing page locale in the config request and registration submission so the accepted filename matches the displayed contract.
- [ ] Keep `store_contract_required` and the existing config response field unchanged.

### Task 3: Verification

**Files:** No new files.

- [ ] Run PHP syntax checks on the changed controllers.
- [ ] Run Blade view caching and `git diff --check`.
- [ ] Inspect the final diff to confirm language-specific replacement does not delete the other locale's file and the API URL field remains backward-compatible.
