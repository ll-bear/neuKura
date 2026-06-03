@extends('layouts.app')

@section('content')

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        {{-- ロゴ --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-2">
                <img src="{{ asset('webplot-icon.svg') }}" class="w-14 h-14 drop-shadow-lg" alt="webPlot">
                <span class="text-4xl font-bold bg-gradient-to-r from-violet-600 to-blue-500 bg-clip-text text-transparent tracking-tight">
                    neuKura
                </span>
            </div>
            <p class="text-slate-400 text-sm tracking-wide">AI-Powered Bookmark Manager</p>
        </div>

        {{-- カード --}}
        <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-xl border border-white/60 p-8">

            {{-- エラー --}}
            @if ($errors->any())
            <div class="flex items-center gap-3 bg-red-50 border border-red-100 rounded-2xl px-4 py-3 mb-6">
                <svg class="w-5 h-5 text-red-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-red-500 text-sm">{{ $errors->first() }}</p>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-600 mb-2">メールアドレス</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <input type="email" name="email" value="{{ old('email') }}"
                               placeholder="user@example.com"
                               class="w-full pl-12 pr-4 py-3 bg-slate-50/80 border border-slate-200 rounded-2xl
                                      text-slate-700 placeholder-slate-300 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-transparent
                                      transition-all"
                               required>
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-8">
                    <label class="block text-sm font-medium text-slate-600 mb-2">パスワード</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input type="password" name="password"
                               placeholder="••••••••"
                               class="w-full pl-12 pr-4 py-3 bg-slate-50/80 border border-slate-200 rounded-2xl
                                      text-slate-700 placeholder-slate-300 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-300 focus:border-transparent
                                      transition-all"
                               required>
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3.5 bg-gradient-to-r from-violet-500 to-blue-500
                               hover:from-violet-600 hover:to-blue-600
                               text-white font-semibold rounded-2xl shadow-lg shadow-violet-200
                               transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0">
                    ログイン
                </button>
            </form>
        </div>

        <p class="text-center text-slate-400 text-xs mt-6">
            NeuNova — AI-Powered Information Retrieval
        </p>
    </div>
</div>

@endsection
