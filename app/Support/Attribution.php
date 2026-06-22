<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Bir isteğin trafik kaynağını (atıf) çözümler.
 * UTM parametreleri > referrer (yönlendiren site) > doğrudan, sırasıyla.
 */
class Attribution
{
    private const SEARCH_ENGINES = ['google', 'bing', 'yahoo', 'yandex', 'duckduckgo', 'ecosia'];

    private const SOCIAL = [
        'instagram', 'facebook', 'fb', 'twitter', 'x', 't.co', 'linkedin',
        'youtube', 'tiktok', 'pinterest', 'whatsapp', 'telegram', 'reddit',
    ];

    /** @return array<string, string|null> */
    public static function fromRequest(Request $request): array
    {
        $utmSource = $request->query('utm_source');
        $utmMedium = $request->query('utm_medium');
        $utmCampaign = $request->query('utm_campaign');
        $utmTerm = $request->query('utm_term');
        $utmContent = $request->query('utm_content');
        // Google Ads tıklama kimliği → ücretli arama işareti
        $gclid = $request->query('gclid');
        $fbclid = $request->query('fbclid');

        $referrer = $request->headers->get('referer');
        $refHost = $referrer ? str_replace('www.', '', parse_url($referrer, PHP_URL_HOST) ?? '') : null;
        $selfHost = str_replace('www.', '', $request->getHost());

        // Kanal sınıflandırması
        $channel = 'direct';
        $source = null;
        $medium = $utmMedium;

        if ($utmSource) {
            $source = strtolower($utmSource);
            $channel = self::channelFromUtm($utmMedium, $source);
        } elseif ($gclid) {
            $channel = 'paid';
            $source = 'google';
            $medium = $medium ?: 'cpc';
        } elseif ($fbclid) {
            $channel = 'social';
            $source = 'facebook';
            $medium = $medium ?: 'social';
        } elseif ($refHost && $refHost !== $selfHost) {
            $source = $refHost;
            [$channel, $medium] = self::channelFromHost($refHost, $medium);
        }

        return [
            'channel' => $channel,
            'source' => $source,
            'medium' => $medium,
            'campaign' => $utmCampaign,
            'term' => $utmTerm,
            'content' => $utmContent,
            'referrer' => $referrer,
            'landing_page' => $request->fullUrl(),
        ];
    }

    private static function channelFromUtm(?string $medium, string $source): string
    {
        $medium = strtolower((string) $medium);

        return match (true) {
            in_array($medium, ['cpc', 'ppc', 'paid', 'paidsearch', 'paid_search', 'display']) => 'paid',
            in_array($medium, ['social', 'social-paid', 'paidsocial']) => 'social',
            in_array($medium, ['email', 'newsletter', 'e-posta']) => 'email',
            in_array($medium, ['organic']) => 'organic',
            in_array($source, self::SOCIAL) => 'social',
            in_array($source, self::SEARCH_ENGINES) => 'organic',
            default => 'referral',
        };
    }

    /** @return array{0:string,1:?string} [channel, medium] */
    private static function channelFromHost(string $host, ?string $medium): array
    {
        foreach (self::SEARCH_ENGINES as $se) {
            if (str_contains($host, $se)) {
                return ['organic', $medium ?: 'organic'];
            }
        }
        foreach (self::SOCIAL as $s) {
            if (str_contains($host, $s)) {
                return ['social', $medium ?: 'social'];
            }
        }

        return ['referral', $medium ?: 'referral'];
    }
}
