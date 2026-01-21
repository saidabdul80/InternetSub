<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadVouchersRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->orderBy('plan_type')
            ->get(['plan_type', 'name', 'amount', 'currency']);

        $payments = Payment::query()
            ->with(['plan', 'voucher'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'reference' => $payment->reference,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'plan' => [
                    'plan_type' => $payment->plan?->plan_type,
                    'name' => $payment->plan?->name,
                ],
                'voucher' => [
                    'code' => $payment->voucher?->code,
                    'status' => $payment->voucher?->status,
                ],
                'paid_at' => $payment->paid_at,
                'created_at' => $payment->created_at,
            ]);

        $vouchers = Voucher::query()
            ->with(['plan', 'payment'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Voucher $voucher) => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'status' => $voucher->status,
                'plan' => [
                    'plan_type' => $voucher->plan?->plan_type,
                    'name' => $voucher->plan?->name,
                ],
                'payment' => [
                    'reference' => $voucher->payment?->reference,
                    'status' => $voucher->payment?->status,
                ],
                'reserved_at' => $voucher->reserved_at,
                'used_at' => $voucher->used_at,
                'created_at' => $voucher->created_at,
            ]);

        return Inertia::render('Admin/Vouchers/Index', [
            'plans' => $plans,
            'payments' => $payments,
            'vouchers' => $vouchers,
        ]);
    }

    public function store(UploadVouchersRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile) {
            return back()->withErrors(['file' => 'Invalid file upload.']);
        }

        $rows = $this->parseFile($file);
        $planTypes = Plan::query()->pluck('plan_type')->all();
        $validPlanTypes = array_flip($planTypes);
        $now = now();
        $validRows = [];
        $invalidCount = 0;

        foreach ($rows as $row) {
            $code = trim((string) data_get($row, 'code', ''));
            $planType = (int) data_get($row, 'plan_type', 0);

            if ($code === '' || ! isset($validPlanTypes[$planType])) {
                $invalidCount++;

                continue;
            }

            $validRows[$code] = [
                'code' => $code,
                'plan_type' => $planType,
                'status' => 'available',
                'payment_id' => null,
                'reserved_at' => null,
                'used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($validRows === []) {
            return back()->withErrors(['file' => 'No valid vouchers were found in the file.']);
        }

        $codes = array_keys($validRows);
        $existingCodes = Voucher::query()
            ->whereIn('code', $codes)
            ->pluck('code')
            ->all();

        foreach ($existingCodes as $existingCode) {
            unset($validRows[$existingCode]);
        }

        $inserted = 0;
        foreach (array_chunk(array_values($validRows), 1000) as $chunk) {
            Voucher::insert($chunk);
            $inserted += count($chunk);
        }

        $skipped = count($existingCodes);
        $message = "Uploaded {$inserted} vouchers.";

        if ($skipped > 0 || $invalidCount > 0) {
            $message .= " Skipped {$skipped} duplicates and {$invalidCount} invalid rows.";
        }

        return back()->with('success', $message);
    }

    protected function parseFile(UploadedFile $file): array
    {
        $contents = (string) $file->get();
        $extension = Str::lower($file->getClientOriginalExtension());
        $mimeType = Str::lower((string) $file->getClientMimeType());

        return match (true) {
            $extension === 'json' || $mimeType === 'application/json' => $this->parseJson($contents),
            $extension === 'csv' => $this->parseCsv($contents),
            $extension === 'txt' => $this->parseText($contents),
            default => [],
        };
    }

    protected function parseJson(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $data = json_decode((string) $contents, true);

        if (! is_array($data)) {
            return [];
        }

        if (array_key_exists('vouchers', $data) && is_array($data['vouchers'])) {
            return $data['vouchers'];
        }

        if (array_key_exists('code', $data) && array_key_exists('plan_type', $data)) {
            return [$data];
        }

        return $data;
    }

    protected function parseCsv(string $contents): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', $contents);
        $header = null;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = array_map('trim', str_getcsv($line));

            if ($header === null && $this->looksLikeHeader($columns)) {
                $header = $this->normalizeHeader($columns);

                continue;
            }

            if ($header) {
                $columns = array_pad($columns, count($header), '');
                $rows[] = array_combine($header, $columns);

                continue;
            }

            $rows[] = [
                'code' => $columns[0] ?? '',
                'plan_type' => $columns[1] ?? '',
            ];
        }

        return $rows;
    }

    protected function parseText(string $contents): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', $contents);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split('/[,\s]+/', $line);
            $parts = Arr::where($parts, fn ($value) => $value !== '');
            $parts = array_values($parts);

            $rows[] = [
                'code' => $parts[0] ?? '',
                'plan_type' => $parts[1] ?? '',
            ];
        }

        return $rows;
    }

    protected function looksLikeHeader(array $columns): bool
    {
        $first = Str::lower(trim((string) ($columns[0] ?? '')));
        $second = Str::lower(trim((string) ($columns[1] ?? '')));

        return $first === 'code' && in_array($second, ['plan', 'plan_type'], true);
    }

    protected function normalizeHeader(array $columns): array
    {
        return array_map(function (string $column): string {
            $column = Str::lower(trim($column));

            return $column === 'plan' ? 'plan_type' : $column;
        }, $columns);
    }
}
