{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>{{ config('seo.url') }}</loc></url>@foreach(config('seo.pages') as $path => $page)<url><loc>{{ rtrim(config('seo.url'), '/').'/'.$path }}</loc></url>@endforeach</urlset>
