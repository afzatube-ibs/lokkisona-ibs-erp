<?php

namespace App\Controllers;

use App\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        Auth::requireAuth();

        $this->render('dashboard.index', [
            'pageTitle' => 'Dashboard',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'active' => true],
            ],
        ]);
    }
}
