<?php

namespace App\Http\Middleware;

use App\Services\Analytics\AnalyticsRecorder;
use App\Support\Attribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * İlk-dokunuş atıfını oturuma yazar ve oturum başına bir 'page_view' (landing) kaydeder.
 * Admin paneli, varlıklar ve AJAX/JSON istekleri hariç tutulur.
 */
class TrackVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldTrack($request)) {
            // İlk-dokunuş: atıf yalnızca oturumda yoksa set edilir.
            if (! session()->has('attribution')) {
                session(['attribution' => Attribution::fromRequest($request)]);
                app(AnalyticsRecorder::class)->record('page_view');
            }
        }

        return $next($request);
    }

    private function shouldTrack(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! $request->is('admin', 'admin/*')
            && ! $request->is('livewire/*')
            && ! $request->ajax()
            && ! $request->expectsJson()
            && ! $request->is('arama/oneri');
    }
}
