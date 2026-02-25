<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DashboardAdmin extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public int $totalAbsensi = 0,
        public int $totalPending = 0,
        public int $overdueCount = 0,
        public int $todayCount = 0
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dashboard-admin');
    }
}
