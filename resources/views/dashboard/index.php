<?php view('dashboard.overview', [
    'dashboardAnalytics' => $dashboardAnalytics ?? [],
    'showRetailAmounts' => $showRetailAmounts ?? false,
    'isSupplierView' => $isSupplierView ?? false,
    'welcomeDate' => $welcomeDate ?? date('l, d F Y'),
    'recentNotes' => $recentNotes ?? [],
]); ?>
