# Inventory Module Planning (Future)

Internal reference only. Not exposed in the Product Control launch UI.

## Product Control (master data — current)

Product Control edits ERP-local fields:

- IBS Category
- IBS Model (main + option)
- Rate
- IBS Stock
- Low Warning

## Future Inventory module (read/report)

Planned reports and views:

1. Stock movement report
2. Stock deduction report
3. Low stock warning
4. Origin stock warning (Live Stock)
5. IBS stock warning
6. Stock adjustment history (from Product Control cost/stock history)
7. Order-based stock deduction ledger
8. Return stock restoration log

Inventory reads the same ERP-local fields; it does not replace Product Control editing.

## Stock deduction rules (future automation)

| Order state | IBS Stock effect |
|-------------|------------------|
| New Order | No deduction |
| Order Received | Deduct IBS Stock once |
| Packaging | No additional deduction |
| Shipped | No additional deduction |
| Cancelled | Restore if already deducted |
| Returned | Restore later based on return reason |

## Build gate

Do not build the Inventory module until Product Control modal work is complete and signed off.
