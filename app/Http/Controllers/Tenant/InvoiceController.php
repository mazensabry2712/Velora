<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * InvoiceController — full CRUD for tenant invoices.
 *
 * All routes are inside admin middleware so $request->user() is the authenticated staff/admin.
 */
class InvoiceController extends Controller
{
    /**
     * GET /admin/api/invoices
     * List invoices with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer:id,first_name,last_name,email', 'items'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $invoices = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $invoices->getCollection()->map(fn ($inv) => $this->formatInvoice($inv)),
            'meta'    => [
                'total'        => $invoices->total(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
            ],
        ]);
    }

    /**
     * POST /admin/api/invoices
     * Create a new invoice (optionally with line items).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'    => 'required|integer|exists:customers,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'tax_rate'       => 'nullable|numeric|min:0|max:100',
            'notes'          => 'nullable|string|max:1000',
            'due_date'       => 'nullable|date',
            'status'         => 'nullable|in:draft,sent,paid,cancelled',
            'items'          => 'nullable|array',
            'items.*.description' => 'required_with:items|string|max:255',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'  => 'required_with:items|integer|min:0',
            'items.*.discount'    => 'nullable|integer|min:0',
            'items.*.item_type'   => 'nullable|string|in:service,product,other',
        ]);

        $invoice = Invoice::create([
            'number'         => $this->generateNumber(),
            'customer_id'    => $data['customer_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'tax_rate'       => $data['tax_rate'] ?? 0,
            'notes'          => $data['notes'] ?? null,
            'due_date'       => $data['due_date'] ?? null,
            'issued_at'      => now(),
            'status'         => $data['status'] ?? 'draft',
            'amount'         => 0,  // will be recalculated after items
        ]);

        if (! empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $qty       = (float) $item['quantity'];
                $unitPrice = (int) $item['unit_price'];
                $discount  = (int) ($item['discount'] ?? 0);
                $total     = (int) round($qty * $unitPrice) - $discount;

                InvoiceItem::create([
                    'invoice_id'      => $invoice->id,
                    'description'     => ['en' => $item['description']],
                    'quantity'        => $qty,
                    'unit_price'      => $unitPrice,
                    'discount_amount' => $discount,
                    'total'           => max(0, $total),
                    'currency'        => 'USD',
                    'item_type'       => $item['item_type'] ?? 'service',
                ]);
            }
        }

        $invoice = $this->recalculate($invoice);

        return response()->json(['success' => true, 'data' => $this->formatInvoice($invoice->load('items'))], 201);
    }

    /**
     * GET /admin/api/invoices/{id}
     */
    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with(['customer:id,first_name,last_name,email,phone', 'items', 'appointment'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->formatInvoice($invoice)]);
    }

    /**
     * PUT /admin/api/invoices/{id}
     * Update invoice header fields (not items — use storeItem / destroyItem).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $data = $request->validate([
            'tax_rate'  => 'nullable|numeric|min:0|max:100',
            'notes'     => 'nullable|string|max:1000',
            'due_date'  => 'nullable|date',
            'status'    => 'nullable|in:draft,sent,paid,cancelled',
        ]);

        $invoice->update(array_filter($data, fn ($v) => ! is_null($v)));

        return response()->json(['success' => true, 'data' => $this->formatInvoice($invoice->load('items'))]);
    }

    /**
     * PATCH /admin/api/invoices/{id}/status
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $request->validate(['status' => 'required|in:draft,sent,paid,cancelled']);

        $invoice->update(['status' => $request->input('status')]);

        return response()->json(['success' => true, 'message' => __('Invoice status updated.')]);
    }

    /**
     * POST /admin/api/invoices/{id}/items
     * Add a line item to an invoice.
     */
    public function storeItem(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $data = $request->validate([
            'description' => 'required|string|max:255',
            'quantity'    => 'required|numeric|min:0.01',
            'unit_price'  => 'required|integer|min:0',
            'discount'    => 'nullable|integer|min:0',
            'item_type'   => 'nullable|string|in:service,product,other',
        ]);

        $qty       = (float) $data['quantity'];
        $unitPrice = (int) $data['unit_price'];
        $discount  = (int) ($data['discount'] ?? 0);
        $total     = max(0, (int) round($qty * $unitPrice) - $discount);

        $item = InvoiceItem::create([
            'invoice_id'      => $invoice->id,
            'description'     => ['en' => $data['description']],
            'quantity'        => $qty,
            'unit_price'      => $unitPrice,
            'discount_amount' => $discount,
            'total'           => $total,
            'currency'        => 'USD',
            'item_type'       => $data['item_type'] ?? 'service',
        ]);

        $this->recalculate($invoice);

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    /**
     * DELETE /admin/api/invoices/{invoiceId}/items/{itemId}
     * Remove a line item.
     */
    public function destroyItem(string $invoiceId, string $itemId): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $item    = InvoiceItem::where('invoice_id', $invoice->id)->findOrFail($itemId);

        $item->delete();
        $this->recalculate($invoice);

        return response()->json(['success' => true, 'message' => __('Item removed.')]);
    }

    /**
     * DELETE /admin/api/invoices/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $invoice->items()->delete();
        $invoice->delete();

        return response()->json(['success' => true, 'message' => __('Invoice deleted.')]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function recalculate(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('items');
        $subtotalCents = $invoice->items->sum('total');
        $subtotalDecimal = $subtotalCents / 100;

        $invoice->update(['amount' => $subtotalDecimal]);
        $invoice->refresh();

        return $invoice;
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $last = Invoice::whereYear('created_at', $year)->count() + 1;
        return 'INV-' . $year . '-' . str_pad($last, 5, '0', STR_PAD_LEFT);
    }

    private function formatInvoice(Invoice $invoice): array
    {
        $customer = $invoice->customer;

        return [
            'id'             => $invoice->id,
            'number'         => $invoice->number,
            'status'         => $invoice->status,
            'customer'       => $customer ? [
                'id'    => $customer->id,
                'name'  => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'email' => $customer->email,
                'phone' => $customer->phone ?? null,
            ] : null,
            'subtotal'       => $invoice->subtotal,
            'tax_rate'       => (float) $invoice->tax_rate,
            'tax_amount'     => $invoice->tax_amount,
            'discount'       => $invoice->discount,
            'total_amount'   => $invoice->total_amount,
            'notes'          => $invoice->notes,
            'due_date'       => $invoice->due_date?->toDateString(),
            'issued_at'      => $invoice->issued_at?->toDateTimeString(),
            'items'          => $invoice->relationLoaded('items')
                ? $invoice->items->map(fn ($item) => [
                    'id'          => $item->id,
                    'description' => is_array($item->description) ? ($item->description['en'] ?? '') : $item->description,
                    'quantity'    => (float) $item->quantity,
                    'unit_price'  => $item->unit_price_decimal,
                    'discount'    => $item->discount_decimal,
                    'total'       => $item->total_decimal,
                    'item_type'   => $item->item_type,
                ])->values()
                : [],
            'created_at'     => $invoice->created_at->toDateTimeString(),
        ];
    }
}

