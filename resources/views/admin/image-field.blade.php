<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $label }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; background: #f3f4f6; color: #111827; }
        .wrap { max-width: 720px; margin: 0 auto; padding: 24px 16px 64px; }
        .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        h1 { font-size: 20px; margin: 0; }
        a.btn, button.btn { display: inline-flex; align-items: center; gap: 6px; border: 0; cursor: pointer; border-radius: 8px; padding: 10px 16px; font-size: 14px; font-weight: 600; text-decoration: none; }
        .btn-back { background: #e5e7eb; color: #374151; }
        .btn-green { background: #16a34a; color: #fff; }
        .btn-red { background: #dc2626; color: #fff; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .card h2 { font-size: 15px; margin: 0 0 14px; color: #374151; }
        .flash { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-weight: 600; }
        .err { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }
        .err ul { margin: 6px 0 0; padding-left: 18px; }
        .current { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .current img { width: 200px; height: 150px; object-fit: contain; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 10px; }
        .empty { color: #9ca3af; font-size: 14px; }
        .drop { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 24px; text-align: center; background: #f8fafc; cursor: pointer; }
        .drop.hl { border-color: #16a34a; background: #f0fdf4; }
        .drop p { margin: 6px 0; color: #64748b; font-size: 14px; }
        .row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 16px; }
        #name { font-size: 13px; color: #16a34a; }
        input[type=file] { display: none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>{{ $label }}</h1>
        <a class="btn btn-back" href="{{ route($editRoute, ['record' => $id]) }}">← Geri dön</a>
    </div>

    @if (session('ok'))
        <div class="flash">✓ {{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="err"><strong>Hata:</strong>
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card">
        <h2>Mevcut Görsel</h2>
        @if ($record->{$field})
            <div class="current">
                <img src="{{ asset('storage/' . $record->{$field}) }}" alt="">
                <form method="POST" action="{{ route('admin.image-field.destroy', ['key' => $key, 'id' => $id]) }}"
                      onsubmit="return confirm('Görsel kaldırılsın mı?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-red">🗑 Kaldır</button>
                </form>
            </div>
        @else
            <div class="empty">Henüz görsel yok.</div>
        @endif
    </div>

    <div class="card">
        <h2>{{ $record->{$field} ? 'Görseli Değiştir' : 'Görsel Yükle' }}</h2>
        <form method="POST" action="{{ route('admin.image-field.store', ['key' => $key, 'id' => $id]) }}" enctype="multipart/form-data">
            @csrf
            <label class="drop" id="drop">
                <p><strong>Görsel seçmek için tıkla</strong> veya buraya sürükle</p>
                <p>JPG · PNG · WEBP · GIF (maks. 30 MB)</p>
                <input type="file" name="image" id="file" accept="image/*" required>
                <p id="name"></p>
            </label>
            <div class="row">
                <button type="submit" class="btn btn-green">⬆ Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
    const file = document.getElementById('file'), name = document.getElementById('name'), drop = document.getElementById('drop');
    file.addEventListener('change', () => { name.textContent = file.files[0]?.name || ''; });
    ['dragover', 'dragenter'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.add('hl'); }));
    ['dragleave', 'drop'].forEach(e => drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.remove('hl'); }));
    drop.addEventListener('drop', ev => { ev.preventDefault(); file.files = ev.dataTransfer.files; name.textContent = file.files[0]?.name || ''; });
</script>
</body>
</html>
