<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(): Response
    {
        $payments = Payment::query()
            ->with(['user', 'plan'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Payments/Index', [
            'payments' => $payments,
        ]);
    }
}
