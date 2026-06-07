<?php
$header = $header ?? [];
$supplierDisplayName = $supplierDisplayName ?? 'Iqbal & Brothers (IBS)';
$businessSourceLabel = $businessSourceLabel ?? 'Lokkisona.com';
$recentNotes = $recentNotes ?? [];
$noteCount = count($recentNotes);
?>
<header class="si-header card">
    <div class="si-header-main">
        <div class="si-header-titles">
            <h1 class="si-title">IQBAL &amp; BROTHERS (IBS)</h1>
            <p class="si-subtitle">Supplier Intelligence &amp; Fulfillment Command Center</p>
            <p class="si-meta">
                <span>Supplier: <?= e($supplierDisplayName) ?></span>
                <span class="si-meta-sep">·</span>
                <span>Business Source: <?= e($businessSourceLabel) ?></span>
            </p>
        </div>
        <span class="si-verified-badge">Verified Supplier</span>
    </div>
    <div class="si-header-aside">
        <div class="si-header-sync">
            <span class="si-sync-dot" aria-hidden="true"></span>
            <span class="si-sync-label"><?= e((string) ($header['sync_label'] ?? 'Synced recently')) ?></span>
        </div>
        <time class="si-datetime"><?= e((string) ($header['datetime'] ?? date('l, d M Y · H:i'))) ?></time>
        <button type="button" class="si-notify-btn" title="Recent activity" aria-label="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <?php if ($noteCount > 0 || (int) ($header['notification_count'] ?? 0) > 0): ?>
            <span class="si-notify-count"><?= e((string) min(9, max($noteCount, (int) ($header['notification_count'] ?? 0)))) ?></span>
            <?php endif; ?>
        </button>
        <div class="si-avatar-badge" title="IBS">IB</div>
    </div>
</header>
