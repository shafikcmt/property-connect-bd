{{-- Illustrative architectural artwork (inline SVG, no external assets). Windows twinkle; clouds drift; cars pass. --}}
<svg viewBox="0 0 640 540" class="h-full w-full rounded-[1.5rem]" role="img" aria-label="{{ __('Illustration of modern residential towers at dusk') }}" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0f2a4d"/><stop offset=".45" stop-color="#17537a"/><stop offset=".78" stop-color="#2a8a8a"/><stop offset="1" stop-color="#f0cf90"/></linearGradient>
        <radialGradient id="sun" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#fff0c4" stop-opacity="1"/><stop offset=".35" stop-color="#ffd98a" stop-opacity=".55"/><stop offset="1" stop-color="#ffd98a" stop-opacity="0"/></radialGradient>
        <pattern id="win" width="18" height="24" patternUnits="userSpaceOnUse"><rect x="4" y="5" width="9" height="13" rx="1" fill="#ffd98a"/></pattern>
        <pattern id="win2" width="18" height="24" patternUnits="userSpaceOnUse"><rect x="4" y="5" width="9" height="13" rx="1" fill="#bfe9e2"/></pattern>
        <linearGradient id="glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2d5a8f"/><stop offset="1" stop-color="#0d2848"/></linearGradient>
        <linearGradient id="shine" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#fff" stop-opacity=".16"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
        <linearGradient id="road" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0c2440"/><stop offset="1" stop-color="#061526"/></linearGradient>
        <clipPath id="rc"><rect width="640" height="540" rx="24"/></clipPath>
    </defs>
    <g clip-path="url(#rc)">
        <rect width="640" height="540" fill="url(#sky)"/>
        {{-- stars --}}
        <g fill="#fff" class="win-twinkle" style="animation-delay:-1.5s"><circle cx="70" cy="48" r="1.6"/><circle cx="150" cy="82" r="1.2"/><circle cx="262" cy="36" r="1.8"/><circle cx="352" cy="64" r="1.2"/><circle cx="540" cy="52" r="1.6"/><circle cx="596" cy="104" r="1.2"/><circle cx="30" cy="130" r="1.2"/></g>
        <circle cx="430" cy="350" r="210" fill="url(#sun)"/>
        <g class="drift-slow" fill="#fff" opacity=".16"><ellipse cx="150" cy="120" rx="74" ry="15"/><ellipse cx="198" cy="108" rx="46" ry="13"/><ellipse cx="500" cy="168" rx="84" ry="14"/></g>
        {{-- far skyline --}}
        <g fill="#1b3f6b" opacity=".7"><rect x="18" y="304" width="58" height="190"/><rect x="88" y="262" width="46" height="232"/><rect x="458" y="286" width="52" height="208"/><rect x="522" y="322" width="70" height="172"/><rect x="590" y="272" width="42" height="222"/></g>
        <g fill="url(#win)" opacity=".35" class="win-twinkle" style="animation-delay:-2s"><rect x="88" y="262" width="46" height="232"/><rect x="458" y="286" width="52" height="208"/></g>
        {{-- main towers --}}
        <rect x="150" y="150" width="130" height="340" fill="url(#glass)"/><rect x="150" y="150" width="130" height="340" fill="url(#win)" class="win-twinkle" style="animation-delay:-1s"/><rect x="280" y="160" width="26" height="330" fill="#0b1f3a"/>
        <rect x="150" y="150" width="130" height="340" fill="url(#shine)"/>
        <rect x="316" y="90" width="150" height="400" fill="url(#glass)"/><rect x="316" y="90" width="150" height="400" fill="url(#win2)" class="win-twinkle" style="animation-delay:-2.4s"/><rect x="466" y="104" width="28" height="386" fill="#0b1f3a"/>
        <rect x="316" y="90" width="150" height="400" fill="url(#shine)"/>
        <g stroke="#d7dde6" stroke-opacity=".45"><path d="M316 130h150M316 170h150M316 210h150M316 250h150M316 290h150M316 330h150M316 370h150M316 410h150M316 450h150"/></g>
        <rect x="316" y="78" width="150" height="12" fill="#c9a24b"/>
        <rect x="389" y="52" width="4" height="26" fill="#c9a24b"/><circle cx="391" cy="50" r="4" fill="#ff6b5b" class="beacon"/>
        <rect x="86" y="332" width="64" height="158" fill="#274a78"/><rect x="86" y="332" width="64" height="158" fill="url(#win)" opacity=".7" class="win-twinkle" style="animation-delay:-3s"/>
        {{-- ground, trees, road --}}
        <rect x="0" y="488" width="640" height="52" fill="url(#road)"/>
        <rect x="0" y="508" width="640" height="2" fill="#ffd98a" opacity=".25"/>
        <g class="car-light"><circle cx="0" cy="500" r="3" fill="#ffe3a8"/><circle cx="12" cy="500" r="3" fill="#ffe3a8"/></g>
        <g class="car-light" style="animation-delay:-3.4s"><circle cx="0" cy="518" r="3" fill="#ff7a6b"/><circle cx="12" cy="518" r="3" fill="#ff7a6b"/></g>
        <g fill="#0f5c4a"><circle cx="60" cy="478" r="22"/><circle cx="560" cy="476" r="26"/><circle cx="520" cy="484" r="18"/><circle cx="118" cy="484" r="14"/></g>
        <g fill="#0b4636" opacity=".8"><circle cx="64" cy="470" r="12"/><circle cx="566" cy="466" r="14"/></g>
    </g>
</svg>
