@php
    $navItems = [
        ['label' => 'Explore', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Curators', 'href' => route('static.curators'), 'active' => request()->routeIs('static.curators')],
        ['label' => 'Trends', 'href' => route('static.trends'), 'active' => request()->routeIs('static.trends')],
        ['label' => 'Blog', 'href' => route('blog.index'), 'active' => request()->routeIs('blog.*')],
        [
            'label' => 'Tools',
            'href' => route('tools'),
            'active' => request()->routeIs('tools*')
                || request()->routeIs('snippets.*')
                || request()->routeIs('patterns.*')
                || request()->routeIs('type-tools.*')
                || request()->routeIs('color-refs.*')
                || request()->routeIs('font-pairings.*')
                || request()->routeIs('color-palettes.*')
                || request()->routeIs('design-prompts.*')
                || request()->routeIs('website-sections.*')
                || request()->routeIs('briefs.*'),
        ],
        ['label' => 'Archive', 'href' => route('static.archive'), 'active' => request()->routeIs('static.archive')],
    ];
@endphp

<header class="sticky top-0 z-50" style="background:rgba(255,255,255,.9);border-bottom:.5px solid #E9ECEF;backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);">
    <div class="vk-container">
        <div style="height:64px;display:flex;align-items:center;justify-content:space-between;gap:24px;">
            <a href="{{ route('home') }}" style="display:flex;align-items:baseline;gap:6px;color:#131B2E;text-decoration:none;white-space:nowrap;">
                <span style="font-family:Manrope,Inter,sans-serif;font-size:18px;font-weight:700;letter-spacing:0;">Visual Design Journey</span>
                <span aria-hidden="true" style="width:5px;height:5px;border-radius:999px;background:#7F77DD;display:inline-block;"></span>
            </a>

            <nav class="hidden md:flex" style="align-items:center;gap:24px;">
                @foreach($navItems as $item)
                    <a href="{{ $item['href'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="hidden md:flex" style="align-items:center;gap:12px;">
                @auth
                    <a href="{{ route('saved') }}" class="btn-secondary btn-primary-sm">Saved</a>
                    <a href="{{ route('boards.create') }}" class="btn-primary btn-primary-sm">Create Board</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary btn-primary-sm">Sign In</a>
                    <a href="{{ route('boards.create') }}" class="btn-primary btn-primary-sm">Create Board</a>
                @endauth
            </div>

            <label for="mobile-menu-toggle" class="inline-flex md:hidden" style="align-items:center;justify-content:center;width:40px;height:40px;border:.5px solid #E9ECEF;border-radius:999px;color:#131B2E;cursor:pointer;">
                <span aria-hidden="true" style="display:grid;gap:4px;">
                    <span style="display:block;width:16px;height:1px;background:#131B2E;"></span>
                    <span style="display:block;width:16px;height:1px;background:#131B2E;"></span>
                    <span style="display:block;width:16px;height:1px;background:#131B2E;"></span>
                </span>
            </label>
        </div>
    </div>

    <input type="checkbox" id="mobile-menu-toggle" class="hidden peer">
    <div class="md:hidden hidden peer-checked:block" style="background:#FFFFFF;border-top:.5px solid #E9ECEF;">
        <nav class="vk-container" style="display:flex;flex-direction:column;padding-top:14px;padding-bottom:18px;gap:2px;">
            @foreach($navItems as $item)
                <a href="{{ $item['href'] }}" style="padding:12px 0;color:{{ $item['active'] ? '#7F77DD' : '#454F5E' }};text-decoration:none;font-size:12px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;">{{ $item['label'] }}</a>
            @endforeach
            <div style="display:flex;gap:10px;flex-wrap:wrap;padding-top:12px;">
                @auth
                    <a href="{{ route('saved') }}" class="btn-secondary btn-primary-sm">Saved</a>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary btn-primary-sm">Sign In</a>
                @endauth
                <a href="{{ route('boards.create') }}" class="btn-primary btn-primary-sm">Create Board</a>
            </div>
        </nav>
    </div>
</header>
