
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page {
        margin: 18mm 18mm 18mm 18mm;
        footer: html_contractFooter;
    }
    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 8.5pt;
        line-height: 1.5;
        color: #1a1a1a;
        text-align: justify;
    }
    h1 {
        font-size: 13pt;
        font-weight: bold;
        text-align: center;
        color: #0b1f3a;
        margin-top: 0;
        margin-bottom: 2px;
        line-height: 1.3;
    }
    .subtitle {
        font-size: 10pt;
        font-weight: bold;
        text-align: center;
        color: #0052cc;
        margin-bottom: 12px;
    }
    .preamble {
        font-size: 8.2pt;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 12px;
        color: #334155;
    }
    .party-box {
        margin-bottom: 10px;
        padding: 8px 12px;
        background-color: #ffffff;
        border-left: 3px solid #0052cc;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
    }
    .party-title {
        font-weight: bold;
        color: #0052cc;
        font-size: 9pt;
        margin-bottom: 4px;
    }
    .section-title {
        font-size: 9pt;
        font-weight: bold;
        color: #0b1f3a;
        margin-top: 10px;
        margin-bottom: 3px;
        border-bottom: 0.5px solid #cbd5e1;
        padding-bottom: 2px;
    }
    p {
        margin-top: 0;
        margin-bottom: 6px;
    }
    ul {
        margin-top: 2px;
        margin-bottom: 6px;
        padding-left: 18px;
    }
    li {
        margin-bottom: 3px;
    }
    .acceptance-box {
        margin-top: 14px;
        padding: 12px 16px;
        border: 1.5px solid #0052cc;
        background-color: #f8faff;
        border-radius: 6px;
    }
    .acceptance-title {
        font-size: 11pt;
        font-weight: bold;
        color: #0052cc;
        margin-bottom: 6px;
    }
    .acceptance-table {
        width: 100%;
        font-size: 8.5pt;
        line-height: 1.8;
        border-collapse: collapse;
        margin-top: 6px;
    }
    .acceptance-table td {
        padding: 3px 0;
        vertical-align: top;
    }
    .acceptance-table .label {
        width: 32%;
        font-weight: bold;
        color: #334155;
    }
    .footer-text {
        font-size: 7.5pt;
        color: #64748b;
        text-align: center;
        border-top: 0.5px solid #e2e8f0;
        padding-top: 4px;
    }
</style>
</head>
<body>

<htmlpagefooter name="contractFooter">
    <div class="footer-text">
        Terms & Conditions for Restaurants and Vendors Joining Zyada &nbsp;|&nbsp; Page {PAGENO} of {nbpg}
    </div>
</htmlpagefooter>

<h1>TERMS AND CONDITIONS FOR RESTAURANTS AND VENDORS JOINING ZYADA</h1>
<div class="subtitle">ZYADA DIGITAL SOLUTIONS - F.Z.C.</div>

<div class="preamble">
These Terms govern the commercial relationship between Zyada and the Vendor regarding product listings, order fulfilment, commission, and settlement of amounts due, in accordance with the applicable laws of the United Arab Emirates. The establishment and authorised representative details registered in the Vendor account, together with the uploaded documents, form part of the identification of the contracting party, and the Vendor's registered business name and trade licence number are displayed below, without requiring a handwritten signature.
</div>

<div class="party-box">
    <div class="party-title">FIRST PARTY</div>
    <div><strong>Zyada Digital Solutions - F.Z.C.</strong>, a free zone company with limited liability, licensed by the Ajman Free Zones Authority under Licence and Registration No. <strong>57982</strong>, with registered address at C.B. 1309649, Building 1C, Ajman Free Zone, Ajman, United Arab Emirates, represented by its Manager, <strong>Mohamed Mosaad Aly Ahmed</strong>, hereinafter referred to as <strong>"Zyada"</strong>.</div>
</div>

<div class="party-box">
    <div class="party-title">SECOND PARTY</div>
    <div><strong>Registered Business Name:</strong> {{ $vendor_legal_name ?? '......................................................................' }}</div>
    <div><strong>Trade License Number:</strong> {{ $trade_license_number ?? '..................................................' }}</div>
    <div style="margin-top: 4px; font-size: 8pt; color: #475569;">The Vendor is the establishment identified in the Vendor account on the application in accordance with its trade licence and registration documents and is represented by the account holder legally authorised to act on its behalf. Any person accepting these Terms confirms the accuracy of the information provided, confirms that he/she has the authority to bind the establishment, and undertakes to update the account information whenever it changes.</div>
</div>

<div class="section-title">1. PURPOSE AND BACKGROUND</div>
<p>The First Party operates the Zyada digital platform, which aims to help companies and commercial establishments sell eligible products, including discounted products, surplus inventory, excess stock, end-of-season products, or other products offered through the application, thereby helping establishments reach customers and reduce unsold products or inventory. The Second Party wishes to offer selected products through Zyada in accordance with these Terms.</p>

<div class="section-title">2. SCOPE OF PARTNERSHIP</div>
<p>The Second Party shall provide the agreed products, quantities, prices, pickup information from the Vendor’s location, availability periods, and any other information required by the First Party for listing on Zyada. The First Party shall provide the technology platform, product-listing functionality, the customer-facing marketplace, and reasonable promotional support for transactions conducted through Zyada. The commercial relationship is non-exclusive unless the Parties expressly agree otherwise in a separate written agreement.</p>

<div class="section-title">3. COMMISSION AND TAXES</div>
<p>Zyada shall be entitled to a commission of <strong>25%</strong> of the value of products actually sold in completed orders after Vendor discounts, excluding VAT on the products, cancelled orders, and refunded amounts. No commission shall be due on products provided free of charge. The commission shall be recalculated in the event of a partial or full refund.</p>
<p>The 25% commission is exclusive of VAT applicable to Zyada’s service. VAT at the legally applicable rate shall be added only to the commission amount, where due and where Zyada is registered or required to register under applicable law, and shall be shown in the tax invoice and settlement statement. It shall not be added to the entire order value as tax on Zyada’s commission. Each Party shall issue such invoices as required by law. No additional payment or transfer fees shall be deducted from the Vendor unless they have been disclosed and electronically accepted before being applied. The commission rate may only be changed with the documented agreement of both Parties.</p>

<div class="section-title">4. WEEKLY SETTLEMENT THROUGH TELR</div>
<p>Zyada shall settle the Vendor’s net amounts due on a weekly basis for completed and collected orders and shall arrange the transfer through Telr to the Vendor’s approved bank account recorded in the registration file. The weekly settlement day shall be displayed to the Vendor in the commercial summary before acceptance and retained in the acceptance record. If the settlement day falls on a banking holiday, settlement shall be made on the next business day. The actual credit date may vary according to bank processing times; Zyada shall follow up on any delay and inform the Vendor of the known reason.</p>
<p>The Vendor shall have access to a weekly statement showing the orders, their values, commission, applicable tax, refunds, any agreed deductions, the net amount transferred, and any remaining balance. No fees or minimum payout thresholds shall be imposed unless agreed in writing.</p>
<p>Transfers shall be made in UAE Dirhams (AED) to the Vendor’s verified account. Bank account details and the bank letter shall be kept securely in the registration file and shall not be included in the body of these Terms. Any request to change the beneficiary account must be verified before any transfer is made to the new account.</p>
<p>An amount specifically related to a dispute, refund, chargeback, or legal order may be temporarily withheld to the extent necessary, with the Vendor informed of the reason where permitted by law, while undisputed amounts shall be paid. Such withholding does not cancel the Vendor’s entitlement to amounts proven to be due.</p>

<div class="section-title">5. PRODUCT SAFETY AND LICENCES</div>
<p>The Vendor warrants that all food, beverages, and products offered through Zyada comply with all applicable UAE health, safety, food-handling, packaging, and storage standards and municipal requirements, and are fit for human consumption and prepared and kept in licensed, compliant premises. The Vendor shall ensure appropriate temperatures and allergen disclosures. The Vendor bears full responsibility for defects, contamination, food poisoning, or non-compliance and shall hold Zyada harmless from any related liability.</p>

<div class="section-title">6. ORDER FULFILMENT AND PROOF OF COLLECTION</div>
<p>The Vendor shall prepare orders promptly and accurately in accordance with the details shown in the application, have them ready for customer pickup during the agreed time window, obtain proof of collection that the customer collected the order from the Vendor’s location, such as a signature or a documented electronic confirmation approved within the application, link it to the order number, and provide it when required for handling a complaint or dispute. Zyada shall issue payment confirmation in accordance with the application’s published and active process. The Vendor shall not be required to collect or store customers’ card data.</p>

<div class="section-title">7. CANCELLATION, REFUNDS AND DISPUTES</div>
<p>The cancellation and refund policy published in the application shall apply as appropriate to the nature of the products, without limiting statutory consumer rights in cases of non-fulfilment, defect, or non-conformity. This Agreement does not require the return of unsafe food as a condition for establishing entitlement to a refund, nor does it impose a short period that extinguishes a statutory right.</p>
<p>Zyada shall process refunds through the payment provider to the original payment method in accordance with the provider’s and bank’s rules. The Vendor shall cooperate with verification and provide fulfilment evidence. Each Party shall bear the documented amounts and losses resulting from its own error or breach, and the commission shall be adjusted when the sale value is refunded. The Vendor shall not automatically bear every chargeback regardless of cause, and the same amount shall not be deducted twice for the same event.</p>

<div class="section-title">8. TERM AND RENEWAL</div>
<p>This Agreement shall remain in force for an initial term of one (1) year from the date of electronic acceptance and shall renew automatically for successive periods of one (1) year unless either Party gives written notice of non-renewal at least thirty (30) days prior to the expiry of the then-current term.</p>

<div class="section-title">9. TERMINATION AND SUSPENSION</div>
<p>Either Party may terminate this Agreement without cause upon thirty (30) days’ written notice. Either Party may terminate immediately upon written notice if the other Party commits a material breach that remains uncured for fourteen (14) days after written notice, or upon insolvency, bankruptcy, cessation of business, or revocation of licences. Zyada may suspend the Vendor’s account where there is a direct risk to food safety, substantiated fraud, or licence invalidity, with immediate notice and an opportunity to remedy, without prejudice to existing amounts due.</p>

<div class="section-title">10. REPUTATION AND PLATFORM STANDARDS</div>
<p>Each Party shall protect the goodwill and reputation of the other and refrain from misleading, disparaging, or unfair statements. The Vendor shall maintain consistent quality, adhere to confirmed prices, and accurately reflect inventory availability to avoid repeated order cancellations.</p>

<div class="section-title">11. INTELLECTUAL PROPERTY AND MARKETING RIGHTS</div>
<p>Each Party retains ownership of its trademarks and intellectual property. The Vendor grants Zyada a non-exclusive, royalty-free licence to use the Vendor’s trade name, logo, menu, product descriptions, and images on the platform and in related marketing solely to promote the Vendor’s products on Zyada during the term of this Agreement.</p>

<div class="section-title">12. CONFIDENTIALITY AND DATA PROTECTION</div>
<p>Each Party shall protect confidential business, operational, and financial information disclosed by the other. Both Parties shall comply with UAE Federal Decree-Law No. 45 of 2021 on Personal Data Protection. The Vendor shall not use Zyada customer data for independent marketing without a lawful basis and required consent. Vendor verification documents shall be stored securely and processed in accordance with applicable laws.</p>

<div class="section-title">13. NON-CIRCUMVENTION OF PLATFORM TRANSACTIONS</div>
<p>The Second Party shall not intentionally direct, encourage, or facilitate a customer introduced through Zyada to complete the same transaction outside Zyada for the purpose of avoiding the applicable commission or platform fees. This restriction does not prevent the Second Party from conducting its ordinary business with customers acquired independently outside Zyada, provided that it does not misuse customer information obtained through Zyada or intentionally circumvent the platform in relation to transactions generated through Zyada.</p>

<div class="section-title">14. LIABILITY</div>
<p>Zyada is an intermediary platform for product listings, order coordination, and collection through the payment provider. To the maximum extent permitted by law, Zyada shall not be liable for indirect, incidental, or consequential damages. Zyada’s total aggregate liability arising out of this Agreement shall be limited to the total commission received by Zyada from the Vendor in the three (3) months preceding the claim.</p>

<div class="section-title">15. THIRD-PARTY CLAIMS</div>
<p>The Vendor shall indemnify and hold harmless Zyada, its directors, officers, and employees against any third-party claims, liabilities, fines, and legal costs arising out of the Vendor’s products, food safety breaches, regulatory non-compliance, intellectual property infringement, or material breach of this Agreement.</p>

<div class="section-title">16. GOVERNING LAW AND JURISDICTION</div>
<p>This Agreement shall be governed by and construed in accordance with the federal laws of the United Arab Emirates and the local laws of the Emirate of Dubai. Any dispute arising out of or in connection with this Agreement shall be subject to the exclusive jurisdiction of the competent courts of Dubai, United Arab Emirates, unless the Parties agree otherwise in writing.</p>

<div class="section-title">17. GENERAL PROVISIONS</div>
<p>This Agreement constitutes the entire agreement between the Parties concerning its subject matter and supersedes any prior discussions or understandings. Any amendment must be in writing and accepted by both Parties. If any provision is found invalid, the remaining provisions shall continue in full force. Electronic signatures and electronically exchanged records of this Agreement have full legal validity and enforceability under UAE law.</p>

<div class="section-title">18. MANDATORY REGISTRATION AND VERIFICATION DOCUMENTS</div>
<p>As a contractual condition for registration and activation on the Zyada application, the Vendor shall upload clear and valid copies of the following documents through the secure process designated within the application:</p>
<ul>
    <li><strong>Trade licence:</strong> including all pages, together with any required activity and branch permits.</li>
    <li><strong>Emirates ID and passport:</strong> of the owner or authorised representative, and of shareholders/beneficial owners as required for verification, with signing authority evidence where necessary.</li>
    <li><strong>Stamped bank letter (IBAN letter):</strong> evidencing the beneficiary account and stating bank name, account holder name, account number, IBAN, and branch. These details remain in the secure verification file outside the body of this Agreement.</li>
    <li><strong>VAT Registration Certificate:</strong> if the establishment is VAT registered, and signing-authority information where required.</li>
</ul>

<div class="section-title">19. NOTICES AND INDEPENDENCE OF THE PARTIES</div>
<p>Notices shall be sent to the email address registered in the Vendor account and through Zyada’s official channels within the application. This Agreement does not create a partnership, joint venture, employment relationship, or registered commercial agency between the Parties. Each Party shall bear its own expenses.</p>

<div class="section-title">20. ELECTRONIC ACCEPTANCE AND RECORD OF TERMS</div>
<p>These Terms and the commercial summary shall be displayed to the Vendor before activation. Acceptance shall be given through a clear affirmative action within the account by pressing the "Agree and Continue" button. Merely viewing or closing the Terms page shall not constitute acceptance. These Terms become effective between the Parties when electronic acceptance is recorded.</p>
<p>The acceptance record links the Vendor account, establishment name, timestamp, and version number. Zyada allows the Vendor to access and save the accepted version. New electronic acceptance shall be obtained for material amendments before they are applied.</p>

<div style="margin-top: 10px; font-weight: bold; color: #0b1f3a;">ACCEPTANCE TEXT DISPLAYED TO THE VENDOR</div>
<div style="font-style: italic; background-color: #f1f5f9; padding: 6px 10px; border-radius: 4px; margin-top: 4px; color: #1e293b;">
"I confirm that I am authorised to represent the establishment registered under this account, that I have reviewed and agree to the Vendor Joining Terms and Conditions and the commercial summary, and that the establishment information and documents provided are accurate."
</div>

<pagebreak />

<div class="acceptance-box" style="page-break-inside: avoid;">
    <div class="acceptance-title">&#10004; ELECTRONIC AGREEMENT & ACCEPTANCE RECORD</div>
    <div style="font-size: 8pt; color: #475569; margin-bottom: 8px;">
        This agreement has been electronically accepted and executed through the ZYADA application in accordance with the laws of the United Arab Emirates.
    </div>
    <table class="acceptance-table">
        <tr>
            <td class="label">Vendor Legal Name:</td>
            <td><strong>{{ $vendor_legal_name ?? '......................................................................' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Trade License Number:</td>
            <td><strong>{{ $trade_license_number ?? '..................................................' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Acceptance Date & Time (UAE):</td>
            <td><strong>{{ $accepted_at_uae ?? 'Generated automatically upon electronic acceptance' }}</strong></td>
        </tr>
        <tr>
            <td class="label">First Party (Platform):</td>
            <td>Zyada Digital Solutions - F.Z.C. (Licence No. 57982 - Ajman Free Zone)</td>
        </tr>
        <tr>
            <td class="label">Legal Validity:</td>
            <td><span style="color: #16a34a; font-weight: bold;">Certified and Legally Binding via Electronic Acceptance</span></td>
        </tr>
    </table>
</div>

</body>
</html>
