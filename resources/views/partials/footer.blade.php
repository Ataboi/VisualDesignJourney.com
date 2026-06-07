<footer style="margin-top:96px;border-top:.5px solid #E9ECEF;background:#FFFFFF;">
    <div class="vk-container" style="padding-top:56px;padding-bottom:32px;">
        <div class="footer-grid" style="margin-bottom:48px;">
            <div>
                <a href="{{ route('home') }}" style="font-family:Manrope,Inter,sans-serif;font-size:18px;font-weight:700;color:#131B2E;text-decoration:none;">Visual Design Journey</a>
                <p style="margin:14px 0 0;max-width:320px;font-size:14px;color:#454F5E;line-height:1.65;">A quiet editorial archive for visual boards, design writing, and practical VibeKit tools.</p>
            </div>
            <div>
                <h4 class="editorial-eyebrow" style="margin:0 0 16px;">Tools</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li><a class="editorial-link" href="{{ route('font-pairings.index') }}">Font Pairings</a></li>
                    <li><a class="editorial-link" href="{{ route('color-palettes.index') }}">Color Palettes</a></li>
                    <li><a class="editorial-link" href="{{ route('briefs.index') }}">Brief Generator</a></li>
                </ul>
            </div>
            <div>
                <h4 class="editorial-eyebrow" style="margin:0 0 16px;">Archive</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li><a class="editorial-link" href="{{ route('blog.index') }}">Blog</a></li>
                    <li><a class="editorial-link" href="{{ route('design-prompts.index') }}">Design Prompts</a></li>
                    <li><a class="editorial-link" href="{{ route('website-sections.index') }}">Section Gallery</a></li>
                    <li><a class="editorial-link" href="{{ route('tools') }}">All Tools</a></li>
                </ul>
            </div>
            <div>
                <h4 class="editorial-eyebrow" style="margin:0 0 16px;">Info</h4>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li><a class="editorial-link" href="{{ route('about') }}">About</a></li>
                    <li><a class="editorial-link" href="{{ route('static.privacy') }}">Privacy</a></li>
                    <li><a class="editorial-link" href="/admin">Admin</a></li>
                </ul>
            </div>
        </div>

        <div style="border:.5px solid #E9ECEF;border-radius:16px;background:#FAF8FF;padding:28px 32px;margin-bottom:32px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:18px;">
            <div>
                <p style="font-family:Manrope,Inter,sans-serif;font-size:22px;font-weight:600;color:#131B2E;margin:0 0 6px;">Build calmer visual systems.</p>
                <p style="font-size:14px;color:#454F5E;margin:0;">Boards, prompts, UI patterns, and generators in one editorial workspace.</p>
            </div>
            <a href="{{ route('tools') }}" class="btn-primary">Explore Tools</a>
        </div>

        <div style="border-top:.5px solid #E9ECEF;padding-top:22px;display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;">
            <p style="font-size:12px;color:#787583;margin:0;">© {{ date('Y') }} Visual Design Journey</p>
            <p style="font-size:12px;color:#787583;margin:0;">visualdesignjourney.com</p>
        </div>
    </div>
</footer>
