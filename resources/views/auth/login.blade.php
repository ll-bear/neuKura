@extends('layouts.app')

@section('content')
<div
    x-data="loginApp()"
    x-init="init()"
    class="w-full max-w-lg relative screen-flicker"
>
    <div class="scan-sweep"></div>

    {{-- ベゼル --}}
    <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0d0d0d] rounded-xl p-0
                border border-[#1a1a1a]
                shadow-[0_0_60px_rgba(0,255,65,0.08),inset_0_0_30px_rgba(0,0,0,0.8),0_20px_60px_rgba(0,0,0,0.9)]">

        {{-- スクリーン --}}
        <div class="bg-[#020a02] rounded-md p-2 min-h-[420px]
                    border border-[#0a1a0a]
                    shadow-[inset_0_0_40px_rgba(0,0,0,0.6)]
                    relative overflow-hidden font-terminal">

            {{-- ブート画面 --}}
            <div x-show="!booted" class="text-[#00cc33] text-lg leading-loose">
                <template x-for="(line, i) in bootLines" :key="i">
                    <div x-text="line" class="animate-fadeIn"></div>
                </template>
                <span :class="blink ? 'opacity-100' : 'opacity-0'" class="transition-opacity">█</span>
            </div>

            {{-- ログインフォーム --}}
            <div x-show="booted" x-transition:enter="animate-fadeIn">

                {{-- ヘッダー --}}
                <div class="border-b border-[#0d2a0d] pb-4 mb-6">
                    <div class="text-[#00ff41] text-3xl tracking-[4px] text-glow leading-none">
                        ■ WEBPLOTTEROS
                    </div>
                    <div class="text-[#1a5a1a] text-sm tracking-[3px] mt-1">
                        USER AUTHENTICATION REQUIRED
                    </div>
                </div>

                {{-- エラー表示 --}}
                @if ($errors->any())
                <div class="bg-[#1a0000] border border-[#4a0000] px-4 py-2.5 mb-5 text-[#ff3333] text-lg tracking-widest">
                    <span>▶ AUTH FAILED: {{ $errors->first() }}</span>
                </div>
                @endif

                {{-- フォーム --}}
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-5">
                        <div class="text-[#1a4a1a] text-sm tracking-widest mb-1.5">USER ID (EMAIL):</div>
                        <div class="border border-[#1a4a1a] px-4 py-2.5 bg-[#010701] flex items-center"
                             :class="focusField === 'email' ? 'border-[#00ff41]' : ''">
                            <span class="text-[#00aa22] mr-3 text-xl">ID&gt;</span>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="term-input text-lg"
                                placeholder="user@example.com"
                                @focus="focusField = 'email'"
                                @blur="focusField = ''"
                                autocomplete="email"
                                required
                            >
                            <span x-show="focusField === 'email'"
                                  :class="blink ? 'opacity-100' : 'opacity-0'"
                                  class="text-[#00ff41] text-xl transition-opacity">█</span>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="mb-8">
                        <div class="text-[#1a4a1a] text-sm tracking-widest mb-1.5">PASSWORD:</div>
                        <div class="border border-[#1a4a1a] px-4 py-2.5 bg-[#010701] flex items-center"
                             :class="focusField === 'password' ? 'border-[#00ff41]' : ''">
                            <span class="text-[#00aa22] mr-3 text-xl">PW&gt;</span>
                            <input
                                type="password"
                                name="password"
                                class="term-input text-lg"
                                placeholder="••••••••"
                                @focus="focusField = 'password'"
                                @blur="focusField = ''"
                                autocomplete="current-password"
                                required
                            >
                            <span x-show="focusField === 'password'"
                                  :class="blink ? 'opacity-100' : 'opacity-0'"
                                  class="text-[#00ff41] text-xl transition-opacity">█</span>
                        </div>
                    </div>

                    {{-- ログインボタン --}}
                    <button type="submit" class="action-btn w-full text-center tracking-[4px]">
                        [ AUTHENTICATE ]
                    </button>

                    <div class="text-[#0d2a0d] text-xs mt-5 leading-loose">
                        SYSTEM: BOOKMARKOS v1.0 — UNAUTHORIZED ACCESS PROHIBITED
                    </div>
                </form>

            </div>
        </div>

        {{-- モニター底面 --}}
        <div class="flex justify-center items-center gap-2 mt-3">
            <div class="w-1.5 h-1.5 rounded-full bg-[#00ff41] shadow-[0_0_6px_#00ff41]"></div>
            <div class="text-[#0d2a0d] text-xs tracking-[4px]">PHOSPHOR GREEN MK-III</div>
        </div>

    </div>
</div>

<script>
function loginApp() {
    return {
        booted: false,
        bootLines: [],
        blink: true,
        focusField: '',

        allBootLines: [
            'BIOS v2.41 Copyright (C) 1987',
            'Memory Test: 640K OK',
            'Loading BOOKMARK.SYS........',
            'Auth module: [OK]',
            '─────────────────────────────────',
            'LOGIN REQUIRED.',
        ],

        init() {
            let i = 0;
            const timer = setInterval(() => {
                if (i < this.allBootLines.length) {
                    this.bootLines.push(this.allBootLines[i++]);
                } else {
                    clearInterval(timer);
                    setTimeout(() => this.booted = true, 500);
                }
            }, 200);

            setInterval(() => this.blink = !this.blink, 530);
        },
    }
}
</script>
@endsection
