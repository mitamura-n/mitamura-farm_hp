<a @class(['instagram-link', 'instagram-menu-link' => $mobileMenu ?? false]) href="https://www.instagram.com/mitamura_farm/">
    <span class="instagram-link-icon" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" focusable="false">
            <rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="2"/>
            <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
            <circle cx="17.5" cy="6.5" r="1.15" fill="currentColor"/>
        </svg>
    </span>
    @if ($mobileMenu ?? false)
        <span>農園のInstagram</span>
    @else
        <span class="instagram-link-text">
            <strong>Instagram</strong>
            <span>@mitamura_farm</span>
        </span>
    @endif
</a>
