# Hide delivery-man tips from rendered views

## Goal

Temporarily hide every rendered delivery-man tip label and amount in Blade views without deleting tip data or changing order, payout, refund, export, or report calculations.

## Chosen approach

Wrap each existing delivery-tip presentation block in a labelled Blade comment. This leaves the original markup in place for easy future re-enablement and preserves every `dm_tips` value and calculation.

## Scope

Comment all Blade-view tip outputs, including standard and parcel order details, admin/vendor/POS invoices, standalone printable invoices, transactional emails, delivery-person transaction and earning views, and any file-export templates that render a dedicated tip amount.

Do not comment `dm_tips` references that exist only to calculate totals, refunds, earnings, balances, or exported non-tip columns. Controllers, APIs, models, migrations, and business settings remain unchanged.

## Alternatives considered

1. Comment existing Blade output blocks. Chosen: display-only, reversible, and no data impact.
2. Disable tips through business settings. Rejected: changes future order behavior and is not reversible presentation-only work.
3. Set `dm_tips` to zero during order creation. Rejected: mutates payments and accounting data.

## Verification

Add a focused source-level regression test that records every affected template and confirms the labelled suppression marker is present. Compile Blade views and run the focused PHPUnit test.
