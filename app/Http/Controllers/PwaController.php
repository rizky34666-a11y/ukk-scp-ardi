<?php

namespace App\Http\Controllers;

class PwaController extends Controller
{
    public function manifest()
    {
        $data = [
            'name' => 'UKK SCP - SMK Negeri 1 Bantul',
            'short_name' => 'UKK SCP',
            'description' => 'Sistem Manajemen KBM & Perizinan SMK Negeri 1 Bantul',
            'id' => '/',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'lang' => 'id',
            'background_color' => '#ffffff',
            'theme_color' => '#0d6efd',
            'icons' => [
                ['src' => asset('image/pwa/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('image/pwa/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('image/pwa/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ];

        return response()->json($data, 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Service worker disajikan dari root (/sw.js) agar cakupannya seluruh situs.
     * Sengaja TIDAK menyimpan halaman HTML (berisi data pribadi/sesi); hanya
     * menampilkan halaman offline saat navigasi gagal karena tidak ada jaringan.
     */
    public function serviceWorker()
    {
        $offline = route('pwa.offline', [], false);
        $icon = asset('image/pwa/icon-192.png');
        $js = <<<JS
const CACHE = 'ukk-scp-offline-v2';
const OFFLINE_URL = '{$offline}';
const PRECACHE = [OFFLINE_URL, '{$icon}'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.registration.navigationPreload && self.registration.navigationPreload.enable())
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET' || req.mode !== 'navigate') return; // selain navigasi: biarkan browser
  e.respondWith(
    (e.preloadResponse ? e.preloadResponse.then((r) => r || fetch(req)) : fetch(req))
      .catch(() => caches.match(OFFLINE_URL))
  );
});
JS;

        return response($js, 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, max-age=0',
        ]);
    }

    public function offline()
    {
        return response()->view('pwa.offline');
    }
}
