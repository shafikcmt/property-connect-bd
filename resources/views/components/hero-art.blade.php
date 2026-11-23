{{-- Illustrative architectural artwork (inline SVG, no external assets). Windows twinkle; clouds drift. --}}
<svg viewBox="0 0 640 540" class="h-full w-full" role="img" aria-label="{{ __('Illustration of modern residential towers at dusk') }}" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#16345c"/><stop offset=".65" stop-color="#14707a"/><stop offset="1" stop-color="#e9c98a"/></linearGradient>
        <radialGradient id="sun" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#ffe3a8" stop-opacity=".95"/><stop offset="1" stop-color="#ffe3a8" stop-opacity="0"/></radialGradient>
        <pattern id="win" width="18" height="24" patternUnits="userSpaceOnUse"><rect x="4" y="5" width="9" height="13" rx="1" fill="#ffd98a"/></pattern>
        <pattern id="win2" width="18" height="24" patternUnits="userSpaceOnUse"><rect x="4" y="5" width="9" height="13" rx="1" fill="#bfe9e2"/></pattern>
        <linearGradient id="glass" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#27507f"/><stop offset="1" stop-color="#0d2848"/></linearGradient>
    </defs>
    <clipPath id="rc"><rect width="640" height="540" rx="28"/></clipPath><g clip-path="url(#rc)"><rect width="640" height="540" fill="url(#sky)"/>
    <circle cx="430" cy="330" r="190" fill="url(#sun)"/>
    <g class="drift-slow" fill="#fff" opacity=".18"><ellipse cx="160" cy="110" rx="70" ry="16"/><ellipse cx="205" cy="98" rx="46" ry="14"/><ellipse cx="480" cy="170" rx="80" ry="15"/></g>
    {{-- far skyline --}}
    <g fill="#1b3b66" opacity=".75"><rect x="20" y="300" width="60" height="190"/><rect x="92" y="260" width="48" height="230"/><rect x="460" y="280" width="52" height="210"/><rect x="524" y="320" width="70" height="170"/><rect x="590" y="270" width="40" height="220"/></g>
    {{-- main towers --}}
    <rect x="150" y="150" width="130" height="340" fill="url(#glass)"/><rect x="150" y="150" width="130" height="340" fill="url(#win)" class="win-twinkle" style="animation-delay:-1s"/><rect x="280" y="160" width="26" height="330" fill="#0b1f3a"/>
    <rect x="316" y="90" width="150" height="400" fill="url(#glass)"/><rect x="316" y="90" width="150" height="400" fill="url(#win2)" class="win-twinkle" style="animation-delay:-2.4s"/><rect x="466" y="104" width="28" height="386" fill="#0b1f3a"/>
    <g stroke="#d7dde6" stroke-opacity=".5"><path d="M316 130h150M316 170h150M316 210h150M316 250h150M316 290h150M316 330h150M316 370h150M316 410h150M316 450h150"/></g>
    <rect x="316" y="78" width="150" height="12" fill="#c9a24b"/>
    <rect x="86" y="330" width="64" height="160" fill="#274a78"/><rect x="86" y="330" width="64" height="160" fill="url(#win)" opacity=".7" class="win-twinkle" style="animation-delay:-3s"/>
    {{-- ground + trees --}}
    <rect x="0" y="488" width="640" height="52" fill="#0a2038"/>
    <g fill="#0f5c4a"><circle cx="60" cy="478" r="22"/><circle cx="560" cy="476" r="26"/><circle cx="520" cy="484" r="18"/></g>
</g></svg>
