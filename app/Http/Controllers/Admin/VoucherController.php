<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(): Response
    {
        $vouchers = Voucher::query()
            ->with(['user', 'plan', 'mikrotikRouter'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Vouchers/Index', [
            'vouchers' => $vouchers,
        ]);
    }
}
