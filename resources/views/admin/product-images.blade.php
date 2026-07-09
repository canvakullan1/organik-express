<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Görseller — {{ $product->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
               background: #f3f4f6; color: #111827; }
        .wrap { max-width: 1000px; margin: 0 auto; padding: 24px 16px 64px; }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        h1 { font-size: 20px; margin: 0; }
        h1 small { display: block; font-weight: 400; color: #6b7280; font-size: 13px; margin-top: 4px; }
        a.btn, button.btn { display: inline-flex; align-items: center; gap: 6px; border: 0; cursor: pointer;
               border-radius: 8px; padding: 10px 16px; font-size: 14px; font-weight: 600; text-decoration: none; }
        .btn-back { background: #e5e7eb; color: #374151; }
        .btn-green { background: #16a34a; color: #fff; }
        .btn-red { background: #dc2626; color: #fff; padding: 7px 12px; font-size: 13px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .card h2 { font-size: 15px; margin: 0 0 14px; color: #374151; }
        .flash { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 16px;
                 border-radius: 8px; margin-bottom: 18px; font-weight: 600; }
        .err { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px;
               border-radius: 8px; margin-bottom: 18px; }
        .err ul { margin: 6px 0 0; padding-left: 18px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
        .thumb { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; background: #fafafa;
                 display: flex; flex-direction: column; }
        .thumb img { width: 100%; height: 150px; object-fit: cover; display: block; background: #fff; }
        .thumb .foot { padding: 8px; display: flex; justify-content: center; }
        .empty { color: #9ca3af; font-size: 14px; padding: 20px 0; text-align: center; }
        .drop { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 26px; text-align: center;
                background: #f8fafc; transition: .15s; cursor: pointer; }
        .drop.hl { border-color: #16a34a; background: #f0fdf4; }
        .drop p { margin: 6px 0; color: #64748b; font-size: 14px; }
        .row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 16px; }
        #names { font-size: 13px; color: #16a34a; }
        input[type=file] { display: none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>Ürün Görselleri
            <small>{{ $product->name }}</small>
        </h1>
        <a class="btn btn-back" href="/admin/products/{{ $product->id }}/edit">← Ürüne dön</a>
    </div>

    @if (session('ok'))
        <div class="flash">✓ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="err">
            <strong>Hata:</strong>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- YÜKLEME --}}
    <div class="card">
        <h2>Yeni Görsel Yükle</h2>
        <form method="POST" action="{{ route('admin.product-images.store', $product) }}" enctype="multipart/form-data">
            @csrf
            <label class="drop" id="drop">
                <p><strong>Görsel seçmek için tıkla</strong> veya buraya sürükle</p>
                <p>JPG · PNG · WEBP · GIF — birden fazla seçebilirsin (maks. 30 MB)</p>
                <input type="file" name="images[]" id="file" accept="image/*" multiple required>
                <p id="names"></p>
            </label>
            <div class="row">
                <button type="submit" class="btn btn-green">⬆ Yükle</button>
            </div>
        </form>
    </div>

    {{-- MEVCUT GÖRSELLER --}}
    <div class="card">
        <h2>Mevcut Görseller ({{ $product->images->count() }})</h2>
        @if ($product->images->isEmpty())
            <div class="empty">Henüz görsel yok.</div>
        @else
            <div class="grid">
                @foreach ($product->images as $img)
                    <div class="thumb">
                        <img src="{{ asset('storage/' . $img->path) }}" alt="{{ $img->alt }}" loading="lazy">
                        <div class="foot">
                            <form method="POST" action="{{ route('admin.product-images.destroy', [$product, $img]) }}"
                                  onsubmit="return confirm('Bu görsel silinsin mi?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-red">🗑 Sil</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
    // Seçilen dosya adlarını göster + sürükle-bırak vurgusu
    const file = document.getElementById('file');
    const names = document.getElementById('names');
    const drop = document.getElementById('drop');
    file.addEventListener('change', () => {
        names.textContent = file.files.length ? [...file.files].map(f => f.name).join(', ') : '';
    });
    ['dragover', 'dragenter'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.add('hl'); }));
    ['dragleave', 'drop'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.remove('hl'); }));
    drop.addEventListener('drop', ev => {
        file.files = ev.dataTransfer.files;
        names.textContent = [...file.files].map(f => f.name).join(', ');
    });
</script>
</body>
</html>
