<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bakımdayız — {{ app(\App\Settings\GeneralSettings::class)->site_name }}</title>
    @include('partials.theme-styles')
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen grid place-items-center bg-cream px-4 text-center">
    <div class="max-w-md">
        <span class="grid size-16 place-items-center rounded-full bg-leaf-600 text-white mx-auto mb-6">
            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085"/></svg>
        </span>
        <h1 class="font-display text-3xl font-600 text-bark">Kısa bir bakımdayız</h1>
        <p class="mt-3 text-bark/60">Sizlere daha iyi hizmet verebilmek için çalışıyoruz. Çok kısa sürede tekrar buradayız.</p>
    </div>
</body>
</html>
