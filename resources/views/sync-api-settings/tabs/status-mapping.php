<?php
use App\Domain\EntryMappingOptions;

$entryMapping = $entryMapping ?? [];
$finalResultMapping = $finalResultMapping ?? [];
$queueStatuses = is_array($entryMapping['queue_statuses'] ?? null) ? $entryMapping['queue_statuses'] : [];
$allStatuses = is_array($entryMapping['all_statuses'] ?? null) ? $entryMapping['all_statuses'] : [];
$savedByStatus = is_array($entryMapping['saved_by_status'] ?? null) ? $entryMapping['saved_by_status'] : [];
$entryOptions = is_array($entryMapping['entry_options'] ?? null) ? $entryMapping['entry_options'] : EntryMappingOptions::dropdownOptions();
$entryLoadedAt = (int) ($entryMapping['connector_loaded_at'] ?? 0);
$sourceId = (int) ($entryMapping['business_source_id'] ?? config('opencart.business_source_id', 1));
$statusRows = $allStatuses !== [] ? $allStatuses : $queueStatuses;
$actionLabels = [
    EntryMappingOptions::IMPORT_NEW => 'Import as New',
    EntryMappingOptions::IGNORE => 'Ignore',
];
?>
<div class="sync-hub-mapping-stack">
    <?php if (!empty($canSyncHub)): ?>

    <div class="card sync-hub-card-wide sync-hub-mapping-card">
        <div class="card-header">
            <h2 class="card-title">New Order Import Mapping</h2>
            <p class="sync-hub-card-lead mb-0">Selected OpenCart statuses will enter IBS as New orders.</p>
        </div>
        <div class="card-body">
            <form method="post" action="<?= e(url('/sync-api-settings/load-queue-statuses')) ?>" class="sync-hub-mapping-toolbar">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="tab" value="mapping">
                <button type="submit" class="btn btn-secondary">Load Queue Statuses</button>
                <?php if ($entryLoadedAt > 0): ?>
                <span class="form-help"><?= count($queueStatuses) ?> queue status<?= count($queueStatuses) === 1 ? '' : 'es' ?> loaded</span>
                <?php endif; ?>
            </form>

            <?php if ($statusRows !== []): ?>
            <form method="post" action="<?= e(url('/sync-api-settings/save-entry-mappings')) ?>" class="sync-hub-mapping-form">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="tab" value="mapping">
                <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">

                <div class="sync-hub-mapping-toolbar">
                    <input type="search" class="form-input" data-sync-hub-status-search placeholder="Search statuses…" aria-label="Search statuses">
                    <details class="sync-hub-dev-panel" data-sync-hub-dev-panel>
                        <summary>Developer: show all OpenCart statuses</summary>
                    </details>
                </div>

                <div class="sync-hub-table-wrap">
                    <table class="data-table sync-hub-mapping-table" data-sync-hub-status-table>
                        <thead>
                            <tr>
                                <th>OC ID</th>
                                <th>OpenCart Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusRows as $status):
                                if (!is_array($status)) continue;
                                $statusId = trim((string) ($status['status_id'] ?? ''));
                                if ($statusId === '') continue;
                                $name = trim((string) ($status['name'] ?? ''));
                                $selected = !empty($status['selected']);
                                $saved = $savedByStatus[$statusId] ?? null;
                                $currentAction = $saved !== null ? EntryMappingOptions::IMPORT_NEW : EntryMappingOptions::IGNORE;
                                $searchText = $statusId . ' ' . $name;
                            ?>
                            <tr data-queue-row="<?= $selected ? '1' : '0' ?>" data-search="<?= e(strtolower($searchText)) ?>" class="<?= $selected ? '' : 'sync-hub-dev-row' ?>">
                                <td><code><?= e($statusId) ?></code></td>
                                <td><?= e($name) ?></td>
                                <td>
                                    <?php if ($selected): ?>
                                    <select name="entry_mapping[<?= e($statusId) ?>]" class="form-input sync-hub-mapping-select">
                                        <?php foreach ($entryOptions as $option):
                                            $code = (string) ($option['code'] ?? '');
                                        ?>
                                        <option value="<?= e($code) ?>" <?= $currentAction === $code ? 'selected' : '' ?>><?= e($actionLabels[$code] ?? (string) ($option['label'] ?? '')) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php else: ?>
                                    <span class="form-help sync-hub-dev-only">Developer view</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="sync-hub-card-footer">
                    <button type="submit" class="btn btn-primary">Save Import Mapping</button>
                </div>
            </form>
            <?php else: ?>
            <div class="sync-hub-empty">Load queue statuses to choose which orders import as New.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card sync-hub-card-wide sync-hub-mapping-card">
        <div class="card-header">
            <h2 class="card-title">Delivered / Returned Mapping</h2>
            <p class="sync-hub-card-lead mb-0">Only applies after supplier marks the order Dispatched.</p>
        </div>
        <div class="card-body">
            <?php if ($allStatuses !== []): ?>
            <form method="post" action="<?= e(url('/sync-api-settings/save-final-result-mappings')) ?>" class="sync-hub-mapping-form">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="tab" value="mapping">
                <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
                <div class="sync-settings-form-grid sync-hub-final-mapping-grid">
                    <div class="form-group">
                        <label for="final_delivered_status_id">Delivered status</label>
                        <select id="final_delivered_status_id" name="final_delivered_status_id" class="form-input">
                            <option value="">— Select status —</option>
                            <?php foreach ($allStatuses as $status):
                                if (!is_array($status)) continue;
                                $statusId = trim((string) ($status['status_id'] ?? ''));
                                if ($statusId === '') continue;
                                $label = trim((string) ($status['name'] ?? ''));
                            ?>
                            <option value="<?= e($statusId) ?>" <?= ($finalResultMapping['delivered_status_id'] ?? '') === $statusId ? 'selected' : '' ?>><?= e($label !== '' ? $label . ' (#' . $statusId . ')' : '#' . $statusId) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="final_returned_status_id">Returned status</label>
                        <select id="final_returned_status_id" name="final_returned_status_id" class="form-input">
                            <option value="">— Select status —</option>
                            <?php foreach ($allStatuses as $status):
                                if (!is_array($status)) continue;
                                $statusId = trim((string) ($status['status_id'] ?? ''));
                                if ($statusId === '') continue;
                                $label = trim((string) ($status['name'] ?? ''));
                            ?>
                            <option value="<?= e($statusId) ?>" <?= ($finalResultMapping['returned_status_id'] ?? '') === $statusId ? 'selected' : '' ?>><?= e($label !== '' ? $label . ' (#' . $statusId . ')' : '#' . $statusId) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="sync-hub-card-footer">
                    <button type="submit" class="btn btn-primary">Save Delivered / Returned Mapping</button>
                </div>
            </form>
            <?php else: ?>
            <div class="sync-hub-empty">Load queue statuses above first.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: ?>
    <div class="card sync-hub-card-wide sync-hub-mapping-card">
        <div class="card-body">
            <p class="page-description mb-0">View-only · <?= (int) ($entryMapping['mapping_count'] ?? 0) ?> Import-as-New mapping<?= (int) ($entryMapping['mapping_count'] ?? 0) === 1 ? '' : 's' ?> saved.</p>
        </div>
    </div>
    <?php endif; ?>
</div>
