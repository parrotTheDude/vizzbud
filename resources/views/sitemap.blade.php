<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Homepage --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    {{-- Dive Site Index Page --}}
    <url>
        <loc>{{ url('/dive-sites') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    {{-- Individual Dive Sites --}}
    @foreach($diveSites as $site)
        <url>
            <loc>{{ url('/dive-sites/' . $site->slug) }}</loc>
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach
</urlset>