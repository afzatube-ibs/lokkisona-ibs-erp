<?php
$payable_center = $payable_center ?? [];
$ledgerLines = $payable_center['ledger_lines'] ?? [];
$statusCards = $payable_center['status_cards'] ?? [];
?>
<section class="card si-payable">
    <div class="card-header card-header-flex">
        <h2 class="card-title">Payable Command Center</h2>
        <a href="<?= e(url('/supplier-payables')) ?>" class="btn btn-sm btn-ghost">Open Payables</a>
    </div>
    <div class="card-body si-payable-split">
        <div class="si-payable-ledger">
            <h3 class="si-section-label">Ledger (BDT)</h3>
            <ul class="si-ledger-list">
                <?php foreach ($ledgerLines as $line): ?>
                <li class="si-ledger-row si-ledger-<?= e((string) ($line['key'] ?? '')) ?>">
                    <span><?= e((string) ($line['label'] ?? '')) ?></span>
                    <strong><?= e(number_format((float) ($line['amount'] ?? 0), 2)) ?></strong>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="si-payable-status">
            <h3 class="si-section-label">Status</h3>
            <div class="si-status-cards">
                <?php foreach ($statusCards as $card): ?>
                <div class="si-status-card si-status-<?= e((string) ($card['tone'] ?? 'muted')) ?>">
                    <span class="si-status-count"><?= e((string) ($card['count'] ?? 0)) ?></span>
                    <span class="si-status-label"><?= e((string) ($card['label'] ?? '')) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
