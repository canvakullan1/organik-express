<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        return view('storefront.account.addresses', [
            'addresses' => auth()->user()->addresses()->get(),
        ]);
    }

    public function create()
    {
        return view('storefront.account.address-form', ['address' => new Address()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['user_id'] = auth()->id();

        $address = Address::create($data);
        $this->ensureSingleDefault($address);

        return redirect($this->redirectTarget($request))->with('success', 'Adres eklendi.');
    }

    public function edit(Address $address)
    {
        $this->authorizeAddress($address);

        return view('storefront.account.address-form', compact('address'));
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeAddress($address);
        $address->update($this->validateData($request));
        $this->ensureSingleDefault($address);

        return redirect()->route('account.addresses')->with('success', 'Adres güncellendi.');
    }

    public function destroy(Address $address)
    {
        $this->authorizeAddress($address);
        $address->delete();

        return back()->with('success', 'Adres silindi.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'is_corporate' => ['boolean'],
            'first_name' => ['nullable', 'required_if:is_corporate,0', 'string', 'max:100'],
            'last_name' => ['nullable', 'required_if:is_corporate,0', 'string', 'max:100'],
            'company_name' => ['nullable', 'required_if:is_corporate,1', 'string', 'max:150'],
            'tax_office' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['required', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:60'],
            'district' => ['required', 'string', 'max:60'],
            'neighborhood' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'is_default' => ['boolean'],
        ], [
            'title.required' => 'Adres başlığı zorunludur.',
            'phone.required' => 'Telefon zorunludur.',
            'city.required' => 'İl zorunludur.',
            'district.required' => 'İlçe zorunludur.',
            'address.required' => 'Açık adres zorunludur.',
            'first_name.required_if' => 'Ad zorunludur.',
            'last_name.required_if' => 'Soyad zorunludur.',
            'company_name.required_if' => 'Firma ünvanı zorunludur.',
        ]);

        $data['is_corporate'] = $request->boolean('is_corporate');
        $data['is_default'] = $request->boolean('is_default');
        $data['type'] = 'both';

        return $data;
    }

    private function ensureSingleDefault(Address $address): void
    {
        if ($address->is_default) {
            Address::where('user_id', $address->user_id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }
    }

    private function authorizeAddress(Address $address): void
    {
        abort_unless($address->user_id === auth()->id(), 403);
    }

    private function redirectTarget(Request $request): string
    {
        return $request->input('return') === 'checkout'
            ? route('checkout.index')
            : route('account.addresses');
    }
}
