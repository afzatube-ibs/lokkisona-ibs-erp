<?php view('dashboard.intelligence', [
    'supplierIntelligence' => $supplierIntelligence ?? [],
    'showRetailAmounts' => $showRetailAmounts ?? false,
    'isSupplierView' => $isSupplierView ?? false,
    'supplierDisplayName' => $supplierDisplayName ?? 'Iqbal & Brothers (IBS)',
    'businessSourceLabel' => $businessSourceLabel ?? 'Lokkisona.com',
    'recentNotes' => $recentNotes ?? [],
]); ?>
