<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromoCodeController extends Controller
{
    public function index()
    {
        $codes = PromoCode::latest()->paginate(30);

        return view('super-admin.promo-codes.index', compact('codes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'           => ['required', 'string', 'max:32', 'alpha_dash', Rule::unique('promo_codes', 'code')],
            'discount_type'  => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:100000'],
            'max_uses'       => ['nullable', 'integer', 'min:1'],
            'expires_at'     => ['nullable', 'date', 'after:now'],
            'is_active'      => ['boolean'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ]);

        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        PromoCode::create($data);

        return redirect()->route('super-admin.promo-codes.index')
            ->with('success', "Promo code '{$data['code']}' created successfully.");
    }

    public function toggle(int $id)
    {
        $code = PromoCode::findOrFail($id);
        $code->update(['is_active' => ! $code->is_active]);

        $status = $code->is_active ? 'activated' : 'deactivated';

        return redirect()->route('super-admin.promo-codes.index')
            ->with('success', "Promo code '{$code->code}' {$status}.");
    }

    public function destroy(int $id)
    {
        $code = PromoCode::findOrFail($id);
        $label = $code->code;
        $code->delete();

        return redirect()->route('super-admin.promo-codes.index')
            ->with('success', "Promo code '{$label}' deleted.");
    }
}
