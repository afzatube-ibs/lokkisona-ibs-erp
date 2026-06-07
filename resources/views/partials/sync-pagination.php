<?php
$pageParam = $pageParam ?? 'page';
$page = max(1, (int) ($page ?? 1));
$hasPrevious = !empty($pagination['has_previous']);
$hasNext = !empty($pagination['has_next']);
$baseUrl = $baseUrl ?? url('/sync-preview');
$otherPageQuery = $otherPageQuery ?? [];
$queryBase = array_merge($otherPageQuery, []);
?>
<div class="sync-pagination-bar">
    <?php if ($hasPrevious): ?>
    <a class="btn btn-secondary btn-sm" href="<?= e($baseUrl . '?' . http_build_query(array_merge($queryBase, [$pageParam => $page - 1]))) ?>">Previous</a>
    <?php else: ?>
    <span class="btn btn-secondary btn-sm" aria-disabled="true">Previous</span>
    <?php endif; ?>
    <span class="page-description sync-pagination-label">Page <?= e((string) $page) ?> · max <?= e((string) ($pagination['per_page'] ?? 20)) ?> rows</span>
    <?php if ($hasNext): ?>
    <a class="btn btn-secondary btn-sm" href="<?= e($baseUrl . '?' . http_build_query(array_merge($queryBase, [$pageParam => $page + 1]))) ?>">Next</a>
    <?php else: ?>
    <span class="btn btn-secondary btn-sm" aria-disabled="true">Next</span>
    <?php endif; ?>
</div>
