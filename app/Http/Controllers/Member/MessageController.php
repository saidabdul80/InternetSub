<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function index(): Response
    {
        /** @var Customer $customer */
        $customer = request()->attributes->get('customer');

        $messages = $customer->messages()
            ->latest()
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'subject' => $message->subject,
                'body' => $message->body,
                'from_type' => $message->from_type,
                'read_at' => $message->read_at,
                'created_at' => $message->created_at,
            ]);

        return Inertia::render('Member/Messages', [
            'customer' => [
                'id' => $customer->id,
                'username' => $customer->username,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'balance' => (string) $customer->balance,
                'status' => $customer->status,
            ],
            'stats' => [
                'total' => $customer->messages()->count(),
                'unread' => $customer->messages()->whereNull('read_at')->count(),
                'from_system' => $customer->messages()->where('from_type', 'System')->count(),
                'from_admin' => $customer->messages()->where('from_type', 'Admin')->count(),
            ],
            'messages' => $messages,
        ]);
    }
}
