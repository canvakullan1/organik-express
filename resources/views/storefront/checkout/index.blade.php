@extends('layouts.storefront')

@section('title', 'Ödeme — ' . app(\App\Settings\GeneralSettings::class)->site_name)

@php($try = fn ($v) => '₺' . number_format((float) $v, 2, ',', '.'))

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8"
     x-data="{
        payment: '{{ $methods->first()?->key() }}',
        billingSame: true,
        usePoints: false,
        sub: {{ $pricing['subtotal'] }},
        couponDiscount: {{ $pricing['coupon_discount'] }},
        redeemable: {{ $pricing['redeemable'] }},
        shipping: {{ $pricing['shipping'] }},
        zoneCities: @js($deliveryZoneCities),
        zones: @js($deliveryZones),
        dateCandidates: @js($dateCandidates),
        earlyPct: {{ $earlyPct }},
        zoneName: '',
        shipCity: @js($guest ? old('city', '') : (optional($addresses->firstWhere('id', $defaultAddressId))->city ?? optional($addresses->first())->city ?? '')),
        deliveryDate: '',
        norm(s) { const m={'İ':'i','I':'i','ı':'i','Ş':'s','ş':'s','Ğ':'g','ğ':'g','Ü':'u','ü':'u','Ö':'o','ö':'o','Ç':'c','ç':'c'}; return (s||'').replace(/[İIıŞşĞğÜüÖöÇç]/g, c => m[c]).trim().toLowerCase(); },
        get inZone() { return this.zoneCities.map(c => this.norm(c)).includes(this.norm(this.shipCity)); },
        get isCargo() { return this.zoneName === '__diger__'; },
        get selectedZone() { return this.zones.find(z => z.name === this.zoneName) || null; },
        get availableDates() { const z = this.selectedZone; if (!z) return []; return this.dateCandidates.filter(d => (z.days || []).map(Number).includes(d.dow)).slice(0, 6); },
        get earlyDate() { return this.availableDates.length ? this.availableDates[0].date : ''; },
        syncDate() { if (!this.selectedZone) { this.deliveryDate = ''; return; } const ds = this.availableDates.map(d => d.date); if (!ds.includes(this.deliveryDate)) { this.deliveryDate = ds.length ? ds[0] : ''; } },
        init() { this.$watch('zoneName', () => this.syncDate()); this.syncDate(); },
        get earlyEligible() { return this.earlyPct > 0 && !!this.selectedZone && this.deliveryDate !== '' && this.deliveryDate === this.earlyDate; },
        get earlyDiscount() { return this.earlyEligible ? Math.round(this.sub * this.earlyPct) / 100 : 0 },
        get loyalty() { return this.usePoints ? this.redeemable : 0 },
        get total() { return Math.max(0, this.sub - this.couponDiscount - this.loyalty - this.earlyDiscount) + this.shipping },
        fmt(v) { return '₺' + Number(v).toLocaleString('tr-TR', {minimumFractionDigits:2, maximumFractionDigits:2}) }
     }">
    <h1 class="font-display text-2xl sm:text-3xl font-700 text-bark mb-6">Ödeme</h1>

    @if($errors->any())
        <div class="mb-5 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" class="grid lg:grid-cols-3 gap-6">
        @csrf
        <div class="lg:col-span-2 space-y-5">

            {{-- Teslimat adresi --}}
            <section class="rounded-2xl border border-paper bg-white p-5">
                @if($guest)
                    {{-- ÜYELİKSİZ (MİSAFİR) ALIŞVERİŞ --}}
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-700 text-bark">1 · İletişim &amp; Teslimat Bilgileri</h2>
                        <span class="text-sm text-bark/55">Üye misin? <a href="{{ route('login', ['return' => 'checkout']) }}" class="font-600 text-leaf-700 hover:underline">Giriş yap</a></span>
                    </div>

                    @if($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                            Lütfen işaretli alanları kontrol edin.
                        </div>
                    @endif

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-600 mb-1.5">E-posta <span class="text-red-500">*</span></label>
                            <input type="email" name="guest_email" value="{{ old('guest_email') }}" required placeholder="ornek@eposta.com"
                                   class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                            <p class="mt-1 text-xs text-bark/45">Sipariş bilgileri bu adrese gönderilir.</p>
                            @error('guest_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-600 mb-1.5">Ad <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                            @error('first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-600 mb-1.5">Soyad <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                            @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-600 mb-1.5">Telefon <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="05XX XXX XX XX" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                            @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-600 mb-1.5">Posta Kodu</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                        </div>
                        <div>
                            <label class="block text-sm font-600 mb-1.5">İl <span class="text-red-500">*</span></label>
                            <input type="text" name="city" value="{{ old('city') }}" required x-model="shipCity" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                            @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-600 mb-1.5">İlçe <span class="text-red-500">*</span></label>
                            <input type="text" name="district" value="{{ old('district') }}" required class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                            @error('district')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-600 mb-1.5">Mahalle</label>
                            <input type="text" name="neighborhood" value="{{ old('neighborhood') }}" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-600 mb-1.5">Açık Adres <span class="text-red-500">*</span></label>
                            <textarea name="address" rows="2" required placeholder="Cadde, sokak, bina/daire no…" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm focus:ring-2 focus:ring-leaf-300">{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @else
                    {{-- ÜYE: adres defteri --}}
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-700 text-bark">1 · Teslimat Adresi</h2>
                        <a href="{{ route('account.address.create', ['return' => 'checkout']) }}" class="text-sm font-600 text-leaf-700 hover:underline">+ Yeni adres</a>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        @foreach($addresses as $a)
                            <label class="relative flex cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-leaf-500 has-[:checked]:bg-leaf-50/50 border-paper">
                                <input type="radio" name="shipping_address_id" value="{{ $a->id }}" class="sr-only peer" {{ $a->id == $defaultAddressId ? 'checked' : '' }} @change="shipCity = @js($a->city)">
                                <div class="text-sm">
                                    <span class="font-700 text-bark">{{ $a->title }}</span>
                                    <p class="text-bark/70 mt-0.5">{{ $a->full_name }} · {{ $a->phone }}</p>
                                    <p class="text-bark/60 mt-1 leading-snug">{{ \Illuminate\Support\Str::limit($a->address, 60) }}, {{ $a->district }}/{{ $a->city }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <label class="flex items-center gap-2 text-sm mt-4">
                        <input type="checkbox" name="billing_same" value="1" x-model="billingSame" class="rounded border-paper text-leaf-600">
                        Fatura adresim teslimat adresimle aynı
                    </label>
                    <div x-show="!billingSame" x-cloak class="mt-3">
                        <label class="block text-sm font-600 mb-1.5">Fatura Adresi</label>
                        <select name="billing_address_id" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm">
                            <option value="">Seçin…</option>
                            @foreach($addresses as $a)
                                <option value="{{ $a->id }}">{{ $a->title }} — {{ $a->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </section>

            {{-- Teslimat zamanı --}}
            <section class="rounded-2xl border border-paper bg-white p-5">
                <h2 class="font-700 text-bark mb-4">2 · Teslimat Zamanı</h2>

                {{-- Teslimat bölgesi seçimi --}}
                <div class="mb-4">
                    <label class="block text-sm font-600 mb-1.5">Teslimat Bölgeniz</label>
                    <select x-model="zoneName" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm">
                        <option value="">Bölgenizi seçin…</option>
                        @foreach($deliveryZones as $z)
                            <option value="{{ $z['name'] }}">{{ $z['name'] }}</option>
                        @endforeach
                        <option value="__diger__">Diğer İller (Kargo ile gönderim)</option>
                    </select>
                    <input type="hidden" name="delivery_zone" :value="isCargo ? '' : zoneName">
                </div>

                {{-- Bölge seçilmedi --}}
                <div x-show="!selectedZone && !isCargo" x-cloak class="rounded-xl bg-cream border border-paper px-4 py-3 text-sm text-bark/60">
                    Teslimat gününüzü görmek için lütfen bölgenizi seçin.
                </div>

                {{-- Diğer iller: kargo bilgilendirmesi (gün seçimi yok) --}}
                <div x-show="isCargo" x-cloak class="rounded-xl bg-leaf-50 border border-leaf-200 px-4 py-3 text-sm text-bark/80 leading-relaxed whitespace-pre-line">{{ $deliveryInfoNote ?: 'Bulunduğunuz il elden teslim bölgemiz dışında olduğundan, siparişiniz anlaşmalı kargo ile 1-3 iş günü içinde adresinize gönderilecektir.' }}</div>

                {{-- Elden teslim bölgesi: gün + zaman aralığı --}}
                <div x-show="selectedZone" x-cloak class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-600 mb-1.5">Teslimat Günü</label>
                        <select name="delivery_date" x-model="deliveryDate" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm">
                            <template x-for="d in availableDates" :key="d.date">
                                <option :value="d.date" x-text="d.label"></option>
                            </template>
                        </select>
                        <p class="mt-1.5 text-xs text-bark/45"><span x-text="selectedZone?.name"></span> bölgesine özel teslim günleri gösteriliyor.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-600 mb-1.5">Zaman Aralığı</label>
                        <select name="delivery_slot" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm">
                            @foreach($deliverySlots as $slot)
                                <option value="{{ $slot }}">{{ $slot }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($earlyPct > 0)
                    {{-- Erken sipariş indirimi ipucu --}}
                    <div x-show="earlyEligible" x-cloak class="mt-3 flex items-center gap-2 rounded-lg bg-clay-50 border border-clay-200 px-3 py-2.5 text-sm text-clay-800">
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        <span><strong>%<span x-text="earlyPct"></span> erken sipariş indirimi</strong> uygulandı — sepet özetinde görebilirsiniz.</span>
                    </div>
                    <div x-show="selectedZone && !earlyEligible" x-cloak class="mt-3 flex items-center gap-2 rounded-lg bg-leaf-50 border border-leaf-200 px-3 py-2.5 text-sm text-leaf-800">
                        <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                        <span>En erken teslim gününü seçerseniz <strong>%<span x-text="earlyPct"></span> indirim</strong> kazanırsınız!</span>
                    </div>
                @endif
                <div class="mt-4">
                    <label class="block text-sm font-600 mb-1.5">Sipariş Notu <span class="font-400 text-bark/40">(opsiyonel)</span></label>
                    <textarea name="note" rows="2" placeholder="Kapı kodu, teslimat tercihi…" class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm">{{ old('note') }}</textarea>
                </div>
            </section>

            {{-- Ödeme yöntemi --}}
            <section class="rounded-2xl border border-paper bg-white p-5">
                <h2 class="font-700 text-bark mb-4">3 · Ödeme Yöntemi</h2>
                @if($methods->isEmpty())
                    <p class="text-sm text-red-600">Aktif ödeme yöntemi yok. Lütfen yönetici ile iletişime geçin.</p>
                @endif
                <div class="space-y-3">
                    @foreach($methods as $m)
                        <label class="block cursor-pointer rounded-xl border-2 p-4 transition has-[:checked]:border-leaf-500 has-[:checked]:bg-leaf-50/50 border-paper">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="payment_method" value="{{ $m->key() }}" x-model="payment" class="text-leaf-600">
                                <div>
                                    <span class="font-700 text-bark">{{ $m->label() }}</span>
                                    <p class="text-xs text-bark/60">{{ $m->description() }}</p>
                                </div>
                            </div>

                            {{-- Test kartı alanı --}}
                            @if($m->key() === 'test')
                                <div x-show="payment === 'test'" x-cloak class="mt-3 pl-7">
                                    <input name="card_number" value="4111 1111 1111 1111"
                                           class="w-full rounded-lg border border-paper bg-cream/50 px-4 py-2.5 text-sm tnum focus:border-leaf-400 focus:outline-none">
                                    <p class="mt-1 text-xs text-bark/50">Demo: başarılı için 4111 1111 1111 1111</p>
                                </div>
                            @endif
                            {{-- Havale bilgisi --}}
                            @if($m->key() === 'bank_transfer')
                                @php($c = app(\App\Settings\PaymentSettings::class))
                                <div x-show="payment === 'bank_transfer'" x-cloak class="mt-3 pl-7 text-sm text-bark/70">
                                    <p>{{ $c->bank_name }} · {{ $c->bank_account_holder }}</p>
                                    <p class="font-600">IBAN: {{ $c->bank_iban }}</p>
                                    <p class="text-xs text-bark/50 mt-1">Sipariş sonrası bu bilgiler e-postanıza da gönderilir.</p>
                                </div>
                            @endif
                            {{-- Kart (iyzico/paytr) yönlendirme notu --}}
                            @if(in_array($m->key(), ['iyzico', 'paytr']))
                                <div x-show="payment === '{{ $m->key() }}'" x-cloak class="mt-3 pl-7 text-xs text-bark/50">
                                    Güvenli 3D Secure ödeme sayfasına yönlendirileceksiniz.
                                </div>
                            @endif
                        </label>
                    @endforeach
                </div>
            </section>

            {{-- Sözleşmeler --}}
            <section class="rounded-2xl border border-paper bg-white p-5">
                <label class="flex items-start gap-2 text-sm text-bark/80">
                    <input type="checkbox" name="agree" value="1" class="mt-0.5 rounded border-paper text-leaf-600">
                    <span><a href="{{ url('/sayfa/mesafeli-satis-sozlesmesi') }}" target="_blank" class="text-leaf-700 hover:underline">Mesafeli Satış Sözleşmesi</a> ve <a href="{{ url('/sayfa/on-bilgilendirme-formu') }}" target="_blank" class="text-leaf-700 hover:underline">Ön Bilgilendirme Formu</a>'nu okudum, onaylıyorum.</span>
                </label>
                @error('agree')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </section>
        </div>

        {{-- Özet --}}
        <aside class="lg:col-span-1">
            <div class="rounded-2xl border border-paper bg-white p-5 lg:sticky lg:top-44">
                <h2 class="font-700 text-bark mb-4">Sipariş Özeti</h2>
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($items as $row)
                        <div class="flex gap-3 text-sm">
                            <div class="size-12 rounded-lg bg-paper bg-cover bg-center shrink-0" @if($row['cover']) style="background-image:url({{ $row['cover'] }})" @endif></div>
                            <div class="flex-1 min-w-0">
                                <p class="font-600 text-bark truncate">{{ $row['name'] }}</p>
                                <p class="text-xs text-bark/50">{{ rtrim(rtrim(number_format($row['qty'],3,',','.'),'0'),',') }} × {{ $try($row['unit_price']) }}</p>
                            </div>
                            <span class="font-600 text-bark tnum">{{ $try($row['line_total']) }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Para puan kullanımı --}}
                @if($pricing['redeemable'] > 0)
                    <label class="mt-4 flex items-start gap-2 rounded-xl bg-leaf-50 border border-leaf-200 p-3 cursor-pointer">
                        <input type="checkbox" x-model="usePoints" class="mt-0.5 rounded border-leaf-300 text-leaf-600">
                        <span class="text-sm">
                            <span class="font-600 text-leaf-800">Para puanımı kullan</span>
                            <span class="block text-xs text-leaf-700/80">{{ number_format($pricing['redeemable'], 0, ',', '.') }} puan ({{ $try($pricing['redeemable']) }}) · bakiye: {{ number_format($loyaltyBalance, 0, ',', '.') }}</span>
                        </span>
                    </label>
                @endif
                <input type="hidden" name="loyalty_points" :value="loyalty">

                <div class="mt-4 pt-4 border-t border-paper space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-bark/60">Ara Toplam</span><span class="tnum">{{ $try($pricing['subtotal']) }}</span></div>
                    @if($pricing['coupon'])
                        <div class="flex justify-between text-leaf-700"><span>Kupon ({{ $pricing['coupon']->code }})</span><span class="tnum">-{{ $try($pricing['coupon_discount']) }}</span></div>
                    @endif
                    <div class="flex justify-between text-leaf-700" x-show="usePoints" x-cloak><span>Para Puan</span><span class="tnum" x-text="'-' + fmt(loyalty)"></span></div>
                    <div class="flex justify-between text-clay-600 font-600" x-show="earlyDiscount > 0" x-cloak><span>Erken Sipariş İndirimi (%<span x-text="earlyPct"></span>)</span><span class="tnum" x-text="'-' + fmt(earlyDiscount)"></span></div>
                    <div class="flex justify-between"><span class="text-bark/60">Kargo</span>
                        <span class="tnum">{{ $pricing['shipping'] > 0 ? $try($pricing['shipping']) : 'Ücretsiz' }}</span>
                    </div>
                    @if($pricing['shipping'] > 0)
                        <p class="text-xs text-leaf-700">{{ $try($threshold - $pricing['subtotal']) }} daha ekle, kargo bedava!</p>
                    @endif
                    <div class="flex justify-between pt-2 border-t border-paper">
                        <span class="font-700 text-bark">Toplam</span>
                        <span class="font-700 text-lg text-leaf-700 tnum" x-text="fmt(total)">{{ $try($pricing['grand_total']) }}</span>
                    </div>
                </div>

                <button type="submit" class="btn-leaf w-full !rounded-full mt-5">Siparişi Tamamla</button>
                <p class="mt-3 text-center text-xs text-bark/40">256-bit SSL ile güvenli ödeme · KDV dahil fiyatlar</p>
            </div>
        </aside>
    </form>
</div>
@endsection
