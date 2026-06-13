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
?>
<div class="card sync-hub-card-wide">
    <div class="card-header">
        <h2 class="card-title">Entry Status Mapping</h2>
        <p class="page-description mb-0">OpenCart queue status → <strong>Import as NEW</strong> or <strong>Ignore</strong>.</p>
    </div>
    <div class="card-body">
        <?php if (!empty($canSyncHub)): ?>
        <form method="post" action="<?= e(url('/sync-api-settings/load-queue-statuses')) ?>" class="sync-hub-mapping-toolbar">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="tab" value="mapping">
            <button type="submit" class="btn btn-secondary">Load Queue Statuses</button>
            <?php if ($entryLoadedAt > 0): ?>
            <span class="form-help"><?= count($queueStatuses) ?> in queue · <?= count($allStatuses) ?> total loaded</span>
            <?php endif; ?>
        </form>

        <?php if ($statusRows !== []): ?>
        <div class="sync-hub-mapping-toolbar">
            <input type="search" class="form-input" data-sync-hub-status-search placeholder="Search by ID or name…" aria-label="Search statuses">
            <label class="sync-hub-show-all">
                <input type="checkbox" data-sync-hub-show-all value="1">
                Show all statuses (debug)
            </label>
        </div>
        <form method="post" action="<?= e(url('/sync-api-settings/save-entry-mappings')) ?>">
            <?= $csrfField ?? '' ?>
            <input type="hidden" name="tab" value="mapping">
            <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
            <div class="sync-hub-table-wrap">
                <table class="data-table" data-sync-hub-status-table>
                    <thead>
                        <tr>
                            <th>OC ID</th>
                            <th>OC Status</th>
                            <th>In Queue</th>
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
                        <tr data-queue-row="<?= $selected ? '1' : '0' ?>" data-search="<?= e(strtolower($searchText)) ?>" class="<?= $selected ? '' : 'text-muted' ?>">
                            <td><code><?= e($statusId) ?></code></td>
                            <td><?= e($name) ?></td>
                            <td><span class="badge <?= $selected ? 'badge-ok' : 'badge-muted' ?>"><?= $selected ? 'Yes' : 'No' ?></span></td>
                            <td>
                                <?php if ($selected): ?>
                                <select name="entry_mapping[<?= e($statusId) ?>]" class="form-input">
                                    <?php foreach ($entryOptions as $option): ?>
                                    <option value="<?= e((string) ($option['code'] ?? '')) ?>" <?= $currentAction === ($option['code'] ?? '') ? 'selected' : '' ?>><?= e((string) ($option['label'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="form-help">Debug only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="sync-settings-actions mt-15">
                <button type="submit" class="btn btn-primary">Save Entry Mapping</button>
            </div>
        </form>
        <?php else: ?>
        <div class="sync-hub-empty">Click <strong>Load Queue Statuses</strong> to configure entry mapping.</div>
        <?php endif; ?>

        <div class="sync-hub-section-block">
            <h3 class="card-title">Final Result Mapping</h3>
            <p class="page-description">Applies only after order is <strong>Dispatched</strong> in IBS.</p>
            <?php if ($allStatuses !== []): ?>
            <form method="post" action="<?= e(url('/sync-api-settings/save-final-result-mappings')) ?>">
                <?= $csrfField ?? '' ?>
                <input type="hidden" name="tab" value="mapping">
                <input type="hidden" name="business_source_id" value="<?= e((string) $sourceId) ?>">
                <div class="sync-settings-form-grid">
                    <div class="form-group">
                        <label for="final_delivered_status_id">OC status → Delivered</label>
                        <select id="final_delivered_status_id" name="final_delivered_status_id" class="form-input">
                            <option value="">— Not mapped —</option>
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
                        <label for="final_returned_status_id">OC status → Returned</label>
                        <select id="final_returned_status_id" name="final_returned_status_id" class="form-input">
                            <option value="">— Not mapped —</option>
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
                <div class="sync-settings-actions mt-15">
                    <button type="submit" class="btn btn-primary">Save Final Result Mapping</button>
                </div>
            </form>
            <?php else: ?>
            <div class="sync-hub-empty">Load queue statuses first.</div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="page-description">View-only · <?= (int) ($entryMapping['mapping_count'] ?? 0) ?> Import-as-NEW mapping(s) saved.</p>
        <?php endif; ?>
    </div>
</div>
