@extends('layouts.app')
@section('content')

<div class="min-h-screen p-4 md:p-6" x-data="bookmarkApp()" x-init="init()">

    {{-- ===== ヘッダー ===== --}}
    <header class="max-w-5xl mx-auto mb-6">
        <div class="bg-white/60 backdrop-blur-xl rounded-2xl shadow-sm border border-white/60 px-5 py-3
                    flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset('favicon.ico') }}" class="w-9 h-9" alt="webPlot">
                <span class="text-xl font-bold bg-gradient-to-r from-violet-600 to-blue-500
                             bg-clip-text text-transparent tracking-tight">
                    neuKura
                </span>
            </div>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-400 to-blue-400
                                flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <span class="text-sm text-slate-500 hidden sm:block">{{ auth()->user()->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex items-center gap-1.5 text-sm text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="hidden sm:block"></span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- ===== メインコンテナ ===== --}}
    <main class="max-w-5xl mx-auto">

        {{-- タブ --}}
        <div class="flex gap-2 mb-4 px-1">
            <template x-for="tab in tabs" :key="tab.id">
                <button
                    @click="activeTab = tab.id"
                    :class="activeTab === tab.id
                        ? 'bg-white shadow-md text-slate-700 border-white'
                        : 'text-slate-400 hover:text-slate-600 hover:bg-white/50 border-transparent'"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium
                           border transition-all duration-200">
                    <span x-html="tab.icon" class="w-4 h-4"></span>
                    <span x-text="tab.label"></span>
                </button>
            </template>
        </div>

        {{-- コンテンツカード --}}
        <div class="bg-white/70 backdrop-blur-xl rounded-3xl shadow-lg border border-white/60 overflow-hidden">

            {{-- ===== SEARCH ===== --}}
            <div x-show="activeTab === 'search'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

                {{-- 検索バー --}}
                <div class="p-5 border-b border-slate-100">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" x-model="search"
                               @input.debounce.500ms="handleSearch()"
                               placeholder="自然言語で検索... 例：クリーム不要のパスタ"
                               class="w-full pl-12 pr-12 py-3.5 bg-slate-50 border border-slate-100 rounded-2xl
                                      text-slate-700 placeholder-slate-300 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-200 focus:border-transparent
                                      transition-all">
                        <div class="absolute inset-y-0 right-4 flex items-center">
                            <span x-show="search"
                                  class="text-xs text-violet-400 font-medium"
                                  x-text="searching ? '検索中...' : `${searchResults.length}件`"></span>
                        </div>
                    </div>

                    {{-- カテゴリフィルター --}}
                    <div class="flex gap-2 mt-3 flex-wrap">
                        <template x-for="cat in ['すべて', ...categories.map(c => c.name)]" :key="cat">
                            <button @click="selectedCategory = cat"
                                    :class="selectedCategory === cat
                                        ? 'bg-gradient-to-r from-violet-500 to-blue-500 text-white shadow-sm'
                                        : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                    class="px-3 py-1 rounded-full text-xs font-medium transition-all"
                                    x-text="cat">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- 検索結果 / 全件一覧 --}}
                <div class="divide-y divide-slate-50 max-h-[520px] overflow-y-auto">
                    <template x-if="(search ? searchResults : filtered).length === 0">
                        <div class="flex flex-col items-center justify-center py-16 text-slate-300">
                            <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">見つかりませんでした</p>
                        </div>
                    </template>

                    <template x-for="(bm, i) in (search ? searchResults : filtered)" :key="bm.id">
                        <div class="flex items-start gap-4 px-5 py-4 hover:bg-violet-50/40 transition-colors group">
                            {{-- ブックマークアイコン --}}
                            <div class="shrink-0 w-9 h-9 rounded-xl
                                        bg-gradient-to-br from-orange-400 to-pink-500
                                        flex items-center justify-center shadow-sm mt-0.5">
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <a :href="bm.url" target="_blank"
                                       class="font-semibold text-slate-700 text-sm leading-snug
                                              hover:text-violet-600 transition-colors line-clamp-1"
                                       x-text="bm.title || bm.url"></a>
                                    <span x-show="bm.category"
                                          class="shrink-0 px-2 py-0.5 bg-violet-100 text-violet-600
                                                 rounded-full text-xs font-medium"
                                          x-text="bm.category?.name"></span>
                                </div>
                                <div class="flex items-center gap-1 mb-1.5">
                                    <svg class="w-3 h-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/>
                                    </svg>
                                    <span class="text-xs text-slate-400 truncate" x-text="bm.url"></span>
                                </div>
                                <p class="text-xs text-slate-500 line-clamp-2" x-text="bm.summary"></p>
                                {{-- RAG類似度バー（検索時のみ） --}}
                                <div x-show="search && bm.similarity" class="mt-2 flex items-center gap-2">
                                    <div class="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-violet-400 to-blue-400 rounded-full"
                                             :style="`width: ${Math.round(bm.similarity * 100)}%`"></div>
                                    </div>
                                    <span class="text-xs text-slate-400"
                                          x-text="`${Math.round(bm.similarity * 100)}%`"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- フッター --}}
                <div class="px-5 py-3 border-t border-slate-50 bg-slate-50/50">
                    <p class="text-xs text-slate-400"
                       x-text="`${bookmarks.length} 件のブックマーク`"></p>
                </div>
            </div>

            {{-- ===== REGISTER ===== --}}
            <div x-show="activeTab === 'register'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

                {{-- 登録フォーム --}}
                <div class="p-5 border-b border-slate-100">
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">URL</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m2.829-2.829a4 4 0 000-5.656l-4-4a4 4 0 00-5.656 5.656l1.102 1.102"/>
                                </svg>
                            </div>
                            <input type="url" x-model="urlInput" placeholder="https://..."
                                   :disabled="saving"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl
                                          text-slate-700 placeholder-slate-300 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-violet-200 focus:border-transparent
                                          disabled:opacity-50 transition-all">
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">メモ（任意）</label>
                        <input type="text" x-model="memoInput" placeholder="メモを入力..."
                               :disabled="saving"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-2xl
                                      text-slate-700 placeholder-slate-300 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-200 focus:border-transparent
                                      disabled:opacity-50 transition-all">
                    </div>

                    {{-- 処理ステップ --}}
                    <div x-show="saving || saveMsg" x-transition class="mb-5">
                        <div class="bg-violet-50 rounded-2xl p-4 space-y-2">
                            <template x-for="(step, i) in processingSteps" :key="i">
                                <div class="flex items-center gap-3">
                                    <div :class="{
                                            'bg-gradient-to-r from-violet-500 to-blue-500 text-white': step.done,
                                            'bg-violet-100 text-violet-400 animate-pulse': step.active && !step.done,
                                            'bg-slate-100 text-slate-300': !step.active && !step.done
                                         }"
                                         class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 transition-all">
                                        <svg x-show="step.done" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <div x-show="!step.done" class="w-1.5 h-1.5 rounded-full bg-current"></div>
                                    </div>
                                    <span :class="step.active ? 'text-violet-600 font-medium' : step.done ? 'text-slate-400' : 'text-slate-300'"
                                          class="text-xs transition-all" x-text="step.label"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="saveMsg === 'done'" x-transition
                         class="flex items-center gap-2 bg-emerald-50 text-emerald-600 rounded-2xl px-4 py-3 mb-4 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        保存しました
                    </div>

                    <div x-show="saveMsg === 'error'" x-transition
                        class="flex items-center gap-2 bg-red-50 text-red-500 rounded-2xl px-4 py-3 mb-4 text-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <span x-text="saveError"></span>
                    </div>

                    <button @click="handleSave()" :disabled="saving || !urlInput.trim()"
                            class="w-full py-3.5 bg-gradient-to-r from-violet-500 to-blue-500
                                   hover:from-violet-600 hover:to-blue-600
                                   text-white font-semibold rounded-2xl shadow-md shadow-violet-200
                                   transition-all duration-200 hover:-translate-y-0.5
                                   disabled:opacity-50 disabled:cursor-not-allowed disabled:translate-y-0">
                        <span x-text="saving ? '処理中...' : 'ブックマークを追加'"></span>
                    </button>
                </div>

                {{-- ブックマーク一覧 --}}
                <div class="p-5">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3"
                        x-text="`登録済み (${bookmarks.length})`"></h3>
                    <div class="space-y-2 max-h-72 overflow-y-auto">
                        <template x-if="bookmarks.length === 0">
                            <p class="text-sm text-slate-300 py-4 text-center">まだ登録されていません</p>
                        </template>
                        <template x-for="bm in bookmarks" :key="bm.id">
                            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-2xl group hover:bg-violet-50/50 transition-colors">
                                <div class="shrink-0 w-8 h-8 rounded-lg
                                            bg-gradient-to-br from-orange-400 to-pink-500
                                            flex items-center justify-center shadow-sm">
                                    <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-700 truncate" x-text="bm.title || bm.url"></p>
                                    <p class="text-xs text-slate-400 truncate" x-text="bm.url"></p>
                                </div>
                                <span class="shrink-0 px-2 py-0.5 bg-violet-100 text-violet-500 rounded-full text-xs"
                                      x-show="bm.category" x-text="bm.category?.name"></span>
                                <button @click="deleteBookmark(bm.id)"
                                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                                               text-slate-200 hover:text-red-400 hover:bg-red-50
                                               opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ===== CONFIG ===== --}}
            <div x-show="activeTab === 'config'" x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

                {{-- 追加フォーム --}}
                <div class="p-5 border-b border-slate-100">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                        カテゴリを追加
                    </h3>
                    <div class="flex gap-3">
                        <input type="text" x-model="newCategoryName"
                               placeholder="カテゴリ名..."
                               @keydown.enter="addCategory()"
                               class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl
                                      text-slate-700 placeholder-slate-300 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-200 focus:border-transparent
                                      transition-all">
                        <input type="text" x-model="newCategoryRemarks"
                               placeholder="備考（任意）"
                               @keydown.enter="addCategory()"
                               class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl
                                      text-slate-700 placeholder-slate-300 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-200 focus:border-transparent
                                      transition-all">
                        <button @click="addCategory()" :disabled="!newCategoryName.trim()"
                                class="px-5 py-2.5 bg-gradient-to-r from-violet-500 to-blue-500
                                       hover:from-violet-600 hover:to-blue-600
                                       text-white text-sm font-semibold rounded-xl shadow-sm shadow-violet-200
                                       disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                            追加
                        </button>
                    </div>
                    <div x-show="categoryMsg" x-transition
                         class="flex items-center gap-2 mt-3 text-emerald-600 text-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="categoryMsg"></span>
                    </div>
                </div>

                {{-- カテゴリ一覧 --}}
                <div class="p-5">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3"
                        x-text="`登録済みカテゴリ (${categories.length})`"></h3>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <template x-if="categories.length === 0">
                            <p class="text-sm text-slate-300 py-4 text-center">カテゴリがありません</p>
                        </template>
                        <template x-for="cat in categories" :key="cat.id">
                            <div class="rounded-2xl border border-slate-100 overflow-hidden">

                                {{-- 表示モード --}}
                                <div x-show="editingCategoryId !== cat.id"
                                     class="flex items-center gap-4 px-4 py-3 bg-slate-50 group hover:bg-violet-50/40 transition-colors">
                                    <div class="w-2.5 h-2.5 rounded-full bg-gradient-to-br from-violet-400 to-blue-400 shrink-0"></div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-medium text-slate-700" x-text="cat.name"></span>
                                        <span x-show="cat.remarks" class="text-xs text-slate-400 ml-2" x-text="cat.remarks"></span>
                                    </div>
                                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="startEdit(cat)"
                                                class="w-7 h-7 rounded-lg flex items-center justify-center
                                                       text-slate-300 hover:text-violet-500 hover:bg-violet-50 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteCategory(cat.id)"
                                                class="w-7 h-7 rounded-lg flex items-center justify-center
                                                       text-slate-300 hover:text-red-400 hover:bg-red-50 transition-all">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- 編集モード --}}
                                <div x-show="editingCategoryId === cat.id"
                                     class="flex items-center gap-3 px-4 py-3 bg-violet-50">
                                    <input type="text" x-model="editingName"
                                           @keydown.enter="updateCategory(cat.id)"
                                           @keydown.escape="editingCategoryId = null"
                                           x-ref="editInput"
                                           class="flex-1 px-3 py-1.5 bg-white border border-violet-200 rounded-xl
                                                  text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-violet-300">
                                    <input type="text" x-model="editingRemarks"
                                           @keydown.enter="updateCategory(cat.id)"
                                           @keydown.escape="editingCategoryId = null"
                                           class="flex-1 px-3 py-1.5 bg-white border border-violet-200 rounded-xl
                                                  text-sm text-slate-500 focus:outline-none focus:ring-2 focus:ring-violet-300">
                                    <div class="flex gap-2 shrink-0">
                                        <button @click="updateCategory(cat.id)"
                                                class="px-3 py-1.5 bg-gradient-to-r from-violet-500 to-blue-500
                                                       text-white text-xs font-semibold rounded-lg">保存</button>
                                        <button @click="editingCategoryId = null"
                                                class="px-3 py-1.5 bg-slate-100 text-slate-500 text-xs font-semibold rounded-lg">
                                            キャンセル
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ===== トークン管理 ===== --}}
                <div class="p-5 border-t border-slate-100">

                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                        APIトークン管理
                    </h3>

                    {{-- 発行フォーム --}}
                    <div class="flex gap-3 mb-4">
                        <input type="text" x-model="newTokenName"
                            placeholder="トークン名（例: ios-shortcut）"
                            @keydown.enter="addToken()"
                            class="flex-1 px-4 py-2.5 bg-slate-50 border border-slate-100 rounded-xl
                                    text-slate-700 placeholder-slate-300 text-sm
                                    focus:outline-none focus:ring-2 focus:ring-violet-200 focus:border-transparent
                                    transition-all">
                        <button @click="addToken()" :disabled="!newTokenName.trim()"
                                class="px-5 py-2.5 bg-gradient-to-r from-violet-500 to-blue-500
                                    hover:from-violet-600 hover:to-blue-600
                                    text-white text-sm font-semibold rounded-xl shadow-sm shadow-violet-200
                                    disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                            発行
                        </button>
                    </div>

                    {{-- 新規発行トークンの表示（一度限り） --}}
                    <div x-show="newlyCreatedToken" x-transition class="mb-4">
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <span class="text-xs font-semibold text-amber-700">
                                    このトークンは一度しか表示されません。必ずコピーしてください。
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 text-xs font-mono bg-white border border-amber-200 rounded-lg
                                            px-3 py-2 text-amber-800 break-all select-all"
                                    x-text="newlyCreatedToken"></code>
                                <button @click="copyToken()"
                                        class="shrink-0 flex items-center gap-1.5 px-3 py-2 bg-amber-500
                                            hover:bg-amber-600 text-white text-xs font-semibold
                                            rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <span x-text="tokenMsg || 'コピー'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- トークン一覧 --}}
                    <div class="space-y-2">
                        <template x-if="tokens.length === 0">
                            <p class="text-sm text-slate-300 py-4 text-center">トークンがありません</p>
                        </template>
                        <template x-for="token in tokens" :key="token.id">
                            <div class="flex items-center gap-4 px-4 py-3 bg-slate-50 rounded-2xl
                                        group hover:bg-violet-50/40 transition-colors">
                                <div class="w-2.5 h-2.5 rounded-full bg-gradient-to-br from-amber-400 to-orange-400 shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-700" x-text="token.name"></p>
                                    <p class="text-xs text-slate-400">
                                        作成：<span x-text="new Date(token.created_at).toLocaleDateString('ja-JP')"></span>
                                        <span x-show="token.last_used_at" class="ml-2">
                                            最終使用：<span x-text="token.last_used_at ? new Date(token.last_used_at).toLocaleDateString('ja-JP') : '-'"></span>
                                        </span>
                                        <span x-show="!token.last_used_at" class="ml-2 text-slate-300">未使用</span>
                                    </p>
                                </div>
                                <button @click="deleteToken(token.id)"
                                        class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center
                                            text-slate-200 hover:text-red-400 hover:bg-red-50
                                            opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
function bookmarkApp() {
    return {
        activeTab: 'search',
        tabs: [
            {
                id: 'search',
                label: '検索',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                       </svg>`,
            },
            {
                id: 'register',
                label: '登録',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M12 4v16m8-8H4"/>
                       </svg>`,
            },
            {
                id: 'config',
                label: '設定',
                icon: `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                       </svg>`,
            },
        ],

        // データ
        bookmarks: @json($bookmarks->load('category')),
        categories: @json($categories),

        // SEARCH
        search: '',
        searchResults: [],
        searching: false,
        selectedCategory: 'すべて',

        // REGISTER
        urlInput: '',
        memoInput: '',
        saving: false,
        saveMsg: '',
        processingSteps: [
            { label: 'Webページを取得中...', active: false, done: false },
            { label: 'コンテンツを解析中...', active: false, done: false },
            { label: 'Gemmaで要約・カテゴリ判定中...', active: false, done: false },
            { label: 'ベクトル化してインデックスに登録中...', active: false, done: false },
        ],

        // CONFIG
        newCategoryName: '',
        newCategoryRemarks: '',
        editingCategoryId: null,
        editingName: '',
        editingRemarks: '',
        categoryMsg: '',

        init() {},

        get filtered() {
            return this.bookmarks.filter(b => {
                const matchCat = this.selectedCategory === 'すべて'
                    || b.category?.name === this.selectedCategory;
                return matchCat;
            });
        },

        // ===== SEARCH =====
        async handleSearch() {
            if (!this.search.trim()) { this.searchResults = []; return; }
            this.searching = true;
            try {
                const res = await fetch(`/bookmarks/search?q=${encodeURIComponent(this.search)}`, {
                    headers: { 'Accept': 'application/json' },
                });

                if (!res.ok) {
                    console.error('Search failed:', res.status);
                    this.searchResults = [];
                    return;
                }

                const data = await res.json();
                // 配列かどうか確認してから代入
                this.searchResults = Array.isArray(data) ? data : [];
            } catch (e) {
                console.error(e);
                this.searchResults = [];
            } finally {
                this.searching = false;
            }
        },

        // ===== REGISTER =====
        async handleSave() {
            if (!this.urlInput.trim()) return;
            this.saving = true;
            this.saveMsg = '';
            this.processingSteps = this.processingSteps.map(s => ({ ...s, active: false, done: false }));

            for (let i = 0; i < this.processingSteps.length; i++) {
                this.processingSteps[i].active = true;
                await new Promise(r => setTimeout(r, 900));
                this.processingSteps[i].done = true;
            }

            try {
                const res = await fetch('/bookmarks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ url: this.urlInput, memo: this.memoInput }),
                });

                if (!res.ok) {
                    const err = await res.json();
                    console.error('Save failed:', err);
                    this.saveMsg = 'error';
                    this.saveError = err.message ?? '保存に失敗しました';
                    return;
                }

                const bookmark = await res.json();
                this.bookmarks = [bookmark, ...this.bookmarks];
                this.saveMsg = 'done';
                this.urlInput = '';
                this.memoInput = '';
            } catch (e) { console.error(e); }
            finally {
                this.saving = false;
                setTimeout(() => {
                    this.saveMsg = '';
                    this.processingSteps = this.processingSteps.map(s => ({ ...s, active: false, done: false }));
                }, 2000);
            }
        },

        async deleteBookmark(id) {
            if (!confirm('このブックマークを削除しますか？')) return;
            try {
                await fetch(`/bookmarks/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                this.bookmarks = this.bookmarks.filter(b => b.id !== id);
                this.searchResults = this.searchResults.filter(b => b.id !== id);
            } catch (e) { console.error(e); }
        },

        // ===== CONFIG =====
        async addCategory() {
            if (!this.newCategoryName.trim()) return;
            try {
                const res = await fetch('/category', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ name: this.newCategoryName, remarks: this.newCategoryRemarks }),
                });
                const category = await res.json();
                this.categories.push(category);
                this.newCategoryName = '';
                this.newCategoryRemarks = '';
                this.categoryMsg = 'カテゴリを追加しました';
                setTimeout(() => this.categoryMsg = '', 2000);
            } catch (e) { console.error(e); }
        },

        startEdit(cat) {
            this.editingCategoryId = cat.id;
            this.editingName = cat.name;
            this.editingRemarks = cat.remarks ?? '';
            this.$nextTick(() => this.$refs.editInput?.focus());
        },

        async updateCategory(id) {
            if (!this.editingName.trim()) return;
            try {
                const res = await fetch(`/category/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ name: this.editingName, remarks: this.editingRemarks }),
                });
                const updated = await res.json();
                const idx = this.categories.findIndex(c => c.id === id);
                if (idx !== -1) this.categories[idx] = updated;
                this.editingCategoryId = null;
            } catch (e) { console.error(e); }
        },

        async deleteCategory(id) {
            if (!confirm('このカテゴリを削除しますか？')) return;
            try {
                await fetch(`/category/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                this.categories = this.categories.filter(c => c.id !== id);
            } catch (e) { console.error(e); }
        },

        // ===== トークン =====
        tokens: @json(auth()->user()->tokens()->latest()->get()),
        newTokenName: '',
        newlyCreatedToken: null,
        tokenMsg: '',

        async addToken() {
            if (!this.newTokenName.trim()) return;
            try {
                const res = await fetch('/tokens', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ name: this.newTokenName }),
                });

                if (!res.ok) {
                    console.error('Token creation failed:', await res.text());
                    return;
                }

                const data = await res.json();

                if (data.token) {
                    this.tokens = [data.token, ...this.tokens];
                    this.newlyCreatedToken = data.plain_text_token;
                    this.newTokenName = '';
                }
            } catch (e) {
                console.error(e);
            }
        },

        async deleteToken(id) {
            if (!confirm('このトークンを削除しますか？\n削除すると該当のデバイスからアクセスできなくなります。')) return;
            try {
                await fetch(`/tokens/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                this.tokens = this.tokens.filter(t => t.id !== id);
                // 削除したトークンが表示中のものなら非表示に
                this.newlyCreatedToken = null;
            } catch (e) {
                console.error(e);
            }
        },

        async copyToken() {
            try {
                await navigator.clipboard.writeText(this.newlyCreatedToken);
                this.tokenMsg = 'コピー済み ✓';
                setTimeout(() => this.tokenMsg = '', 2000);
            } catch (e) {
                // clipboard APIが使えない場合
                this.tokenMsg = '手動でコピーしてください';
            }
        },
    }
}
</script>
@endsection