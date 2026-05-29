@extends('layouts.app')
@section('content')

<div
    x-data="bookmarkApp()"
    x-init="init()"
    class="w-full max-w-5xl relative screen-flicker"
>
    <div class="scan-sweep"></div>

    {{-- ベゼル --}}
    <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0d0d0d] rounded-xl p-3
                border border-[#1a1a1a]
                shadow-[0_0_60px_rgba(0,255,65,0.08),inset_0_0_30px_rgba(0,0,0,0.8),0_20px_60px_rgba(0,0,0,0.9)]">

        {{-- スクリーン --}}
        <div class="bg-[#020a02] rounded-md p-4 min-h-[600px]
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

            {{-- メインUI --}}
            <div x-show="booted" x-transition:enter="animate-fadeIn">

                {{-- ヘッダー --}}
                <div class="border-b border-[#0d2a0d] pb-3 mb-4 flex justify-between items-start">
                    <div>
                        <div class="text-[#00ff41] text-3xl tracking-[4px] leading-none text-glow">■ BOOKMARKOS</div>
                        <div class="text-[#1a5a1a] text-sm tracking-[3px] mt-1">NEURAL RETRIEVAL SYSTEM v1.0</div>
                    </div>
                    <div class="text-right text-[#1a4a1a] text-sm">
                        <div>{{ now()->format('Y/m/d') }}</div>
                        <div class="text-[#0d2a0d]">{{ auth()->user()->name }}</div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-[#1a3a1a] text-xs tracking-widest hover:text-[#00ff41] transition-colors">
                                [LOGOUT]
                            </button>
                        </form>
                    </div>
                </div>

                {{-- タブ --}}
                <div class="flex gap-1 mb-5">
                    <template x-for="tab in ['SEARCH','REGISTER','CONFIG']" :key="tab">
                        <button
                            @click="activeTab = tab"
                            :class="activeTab === tab ? 'tab-active' : 'tab-default'"
                            class="tab-btn"
                            x-text="`[${tab}]`"
                        ></button>
                    </template>
                </div>

                {{-- ========== SEARCH ========== --}}
                <div x-show="activeTab === 'SEARCH'" x-transition:enter="animate-fadeIn">

                    <div class="text-[#1a5a1a] text-sm tracking-widest mb-3">
                        // SEMANTIC SEARCH — NATURAL LANGUAGE QUERY
                    </div>

                    {{-- 検索入力 --}}
                    <div class="border border-[#1a4a1a] px-4 py-2.5 mb-2 flex items-center bg-[#010701]">
                        <span class="text-[#00aa22] mr-3 text-xl">QUERY&gt;</span>
                        <input
                            type="text"
                            x-model="search"
                            @input.debounce.500ms="handleSearch()"
                            placeholder="クリーム使わないパスタ..."
                            class="term-input text-xl flex-1"
                        >
                        <span :class="blink ? 'opacity-100' : 'opacity-0'" class="text-[#00ff41] text-xl transition-opacity">█</span>
                    </div>

                    <div class="text-[#0d2a0d] text-xs mb-4 tracking-widest"
                         x-text="searching ? 'SEARCHING...' : (search ? `${searchResults.length} RESULTS FOUND` : 'AWAITING INPUT...')">
                    </div>

                    {{-- 検索結果 --}}
                    <div class="max-h-[440px] overflow-y-auto pr-2 space-y-2 scrollbar-terminal">
                        <template x-if="!search">
                            <div>
                                <template x-for="bm in bookmarks" :key="bm.id">
                                    <div class="bm-row">
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="text-[#00ff41] text-lg text-glow-dim flex-1" x-text="bm.title"></div>
                                            <span class="text-[#0a3a0a] text-xs border border-[#0a2a0a] px-2 py-0.5 shrink-0"
                                                  x-text="bm.category?.name ?? ''"></span>
                                        </div>
                                        <a :href="bm.url" target="_blank"
                                           class="text-[#1a4a1a] text-sm mt-1 block hover:text-[#00ff41] transition-colors"
                                           x-text="`> ${bm.url}`"></a>
                                        <div class="text-[#2a6a2a] text-base mt-1" x-text="bm.summary"></div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="search">
                            <div>
                                <template x-if="searchResults.length === 0 && !searching">
                                    <div class="text-[#1a4a1a] text-lg py-5">&gt; NO RECORDS FOUND_</div>
                                </template>
                                <template x-for="(bm, i) in searchResults" :key="bm.id">
                                    <div class="bm-row">
                                        <div class="flex gap-3 items-start">
                                            <span class="text-[#0d2a0d] text-lg min-w-[30px]"
                                                  x-text="String(i + 1).padStart(2, '0') + '.'"></span>
                                            <div class="flex-1">
                                                <div class="flex justify-between items-start gap-2">
                                                    <div class="text-[#00ff41] text-lg text-glow-dim" x-text="bm.title"></div>
                                                    <span class="text-[#0a3a0a] text-xs border border-[#0a2a0a] px-2 py-0.5 shrink-0"
                                                          x-text="bm.category?.name ?? ''"></span>
                                                </div>
                                                <a :href="bm.url" target="_blank"
                                                   class="text-[#1a4a1a] text-sm hover:text-[#00ff41] transition-colors"
                                                   x-text="`> ${bm.url}`"></a>
                                                <div class="text-[#2a6a2a] text-base mt-1" x-text="bm.summary"></div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="text-[#0d2a0d] text-xs mt-3 pt-2 border-t border-[#0a1a0a]"
                         x-text="`TOTAL INDEXED: ${bookmarks.length} RECORDS`"></div>
                </div>

                {{-- ========== REGISTER ========== --}}
                <div x-show="activeTab === 'REGISTER'" x-transition:enter="animate-fadeIn">

                    <div class="text-[#1a5a1a] text-sm tracking-widest mb-4">
                        // URL REGISTRATION — SCRAPE & INDEX
                    </div>

                    {{-- URL入力 --}}
                    <div class="mb-3">
                        <div class="text-[#1a4a1a] text-sm tracking-widest mb-1.5">TARGET URL:</div>
                        <div class="border border-[#1a4a1a] px-4 py-2.5 bg-[#010701] flex items-center">
                            <span class="text-[#00aa22] mr-3 text-xl">URL&gt;</span>
                            <input type="url" x-model="urlInput" placeholder="https://..."
                                   :disabled="saving" class="term-input text-lg flex-1">
                            <span :class="blink ? 'opacity-100' : 'opacity-0'" class="text-[#00ff41] text-xl transition-opacity">█</span>
                        </div>
                    </div>

                    {{-- メモ入力 --}}
                    <div class="mb-4">
                        <div class="text-[#1a4a1a] text-sm tracking-widest mb-1.5">MEMO (OPTIONAL):</div>
                        <div class="border border-[#0d2a0d] px-4 py-2.5 bg-[#010701] flex items-center">
                            <span class="text-[#1a5a1a] mr-3 text-xl">MEM&gt;</span>
                            <input type="text" x-model="memoInput" placeholder="メモを入力..."
                                   :disabled="saving" class="term-input text-[#2a8a2a] text-lg flex-1">
                        </div>
                    </div>

                    {{-- 処理ログ --}}
                    <div x-show="saveMsg !== ''" x-transition
                         class="bg-[#010f01] border border-[#0a2a0a] px-4 py-2.5 mb-4 text-[#00cc33] text-lg tracking-widest">
                        <span :class="blink ? 'opacity-100' : 'opacity-0'" class="transition-opacity">▶ </span>
                        <span x-text="saveMsg"></span>
                    </div>

                    <div class="flex items-center gap-4 mb-6">
                        <button @click="handleSave()" :disabled="saving || !urlInput.trim()" class="action-btn"
                                x-text="saving ? 'PROCESSING...' : '[ EXECUTE ]'"></button>
                        <div class="text-[#0d2a0d] text-xs tracking-widest">
                            PIPELINE: SCRAPE → GEMMA → VECTORIZE → INDEX
                        </div>
                    </div>

                    {{-- ブックマーク一覧 --}}
                    <div class="border-t border-[#0a1a0a] pt-4">
                        <div class="text-[#1a4a1a] text-sm tracking-widest mb-3"
                             x-text="`INDEXED RECORDS [${bookmarks.length}]`"></div>

                        <div class="max-h-72 overflow-y-auto pr-2 space-y-2 scrollbar-terminal">
                            <template x-if="bookmarks.length === 0">
                                <div class="text-[#1a4a1a] text-lg py-3">&gt; NO RECORDS_</div>
                            </template>
                            <template x-for="bm in bookmarks" :key="bm.id">
                                <div class="bm-row flex items-start gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="text-[#00ff41] text-lg text-glow-dim truncate" x-text="bm.title"></div>
                                            <span class="text-[#0a3a0a] text-xs border border-[#0a2a0a] px-2 py-0.5 shrink-0"
                                                  x-text="bm.category?.name ?? ''"></span>
                                        </div>
                                        <div class="text-[#1a4a1a] text-sm truncate" x-text="`> ${bm.url}`"></div>
                                        <div class="text-[#2a6a2a] text-sm mt-1 line-clamp-1" x-text="bm.summary"></div>
                                    </div>
                                    <button @click="deleteBookmark(bm.id)"
                                            class="shrink-0 text-[#3a0a0a] border border-[#2a0a0a] px-2 py-0.5 text-sm
                                                   hover:text-[#ff3333] hover:border-[#ff3333] transition-colors">
                                        [DEL]
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ========== CONFIG ========== --}}
                <div x-show="activeTab === 'CONFIG'" x-transition:enter="animate-fadeIn">

                    <div class="text-[#1a5a1a] text-sm tracking-widest mb-4">
                        // CATEGORY CONFIGURATION
                    </div>

                    {{-- カテゴリ追加フォーム --}}
                    <div class="border border-[#0d2a0d] p-4 bg-[#010701] mb-5">
                        <div class="text-[#1a4a1a] text-sm tracking-widest mb-3">NEW CATEGORY:</div>
                        <div class="flex gap-3 items-end">
                            <div class="flex-1">
                                <div class="text-[#0d2a0d] text-xs mb-1">NAME:</div>
                                <div class="border border-[#1a4a1a] px-3 py-2 flex items-center">
                                    <span class="text-[#00aa22] mr-2 text-lg">CAT&gt;</span>
                                    <input type="text" x-model="newCategoryName"
                                           placeholder="カテゴリ名..."
                                           @keydown.enter="addCategory()"
                                           class="term-input text-base">
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="text-[#0d2a0d] text-xs mb-1">REMARKS:</div>
                                <div class="border border-[#0d2a0d] px-3 py-2 flex items-center">
                                    <span class="text-[#1a5a1a] mr-2 text-lg">REM&gt;</span>
                                    <input type="text" x-model="newCategoryRemarks"
                                           placeholder="備考（任意）..."
                                           @keydown.enter="addCategory()"
                                           class="term-input text-[#2a8a2a] text-base">
                                </div>
                            </div>
                            <button @click="addCategory()"
                                    :disabled="!newCategoryName.trim()"
                                    class="action-btn text-base px-4 shrink-0">
                                [ADD]
                            </button>
                        </div>
                        <div x-show="categoryMsg !== ''" x-transition
                             class="mt-3 text-[#00cc33] text-base tracking-widest">
                            <span :class="blink ? 'opacity-100' : 'opacity-0'">▶ </span>
                            <span x-text="categoryMsg"></span>
                        </div>
                    </div>

                    {{-- カテゴリ一覧 --}}
                    <div class="text-[#1a4a1a] text-sm tracking-widest mb-3"
                         x-text="`REGISTERED CATEGORIES [${categories.length}]`"></div>

                    <div class="max-h-80 overflow-y-auto pr-2 space-y-2 scrollbar-terminal">
                        <template x-if="categories.length === 0">
                            <div class="text-[#1a4a1a] text-lg py-3">&gt; NO CATEGORIES_</div>
                        </template>
                        <template x-for="cat in categories" :key="cat.id">
                            <div class="bm-row">

                                {{-- 表示モード --}}
                                <div x-show="editingCategoryId !== cat.id"
                                     class="flex justify-between items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <span class="text-[#00ff41] text-lg text-glow-dim" x-text="cat.name"></span>
                                        <span x-show="cat.remarks"
                                              class="text-[#2a6a2a] text-sm ml-3"
                                              x-text="cat.remarks"></span>
                                    </div>
                                    <div class="flex gap-2 shrink-0">
                                        <button @click="startEdit(cat)"
                                                class="text-[#1a4a1a] border border-[#0d2a0d] px-2 py-0.5 text-sm
                                                       hover:text-[#00ff41] hover:border-[#00ff41] transition-colors">
                                            [EDIT]
                                        </button>
                                        <button @click="deleteCategory(cat.id)"
                                                class="text-[#3a0a0a] border border-[#2a0a0a] px-2 py-0.5 text-sm
                                                       hover:text-[#ff3333] hover:border-[#ff3333] transition-colors">
                                            [DEL]
                                        </button>
                                    </div>
                                </div>

                                {{-- 編集モード --}}
                                <div x-show="editingCategoryId === cat.id" class="space-y-2">
                                    <div class="flex gap-2 items-center">
                                        <div class="border border-[#00ff41] px-3 py-1.5 flex items-center flex-1">
                                            <span class="text-[#00aa22] mr-2 text-lg">CAT&gt;</span>
                                            <input type="text" x-model="editingName"
                                                   @keydown.enter="updateCategory(cat.id)"
                                                   @keydown.escape="editingCategoryId = null"
                                                   class="term-input text-base"
                                                   x-ref="editInput">
                                        </div>
                                        <div class="border border-[#0d2a0d] px-3 py-1.5 flex items-center flex-1">
                                            <span class="text-[#1a5a1a] mr-2 text-lg">REM&gt;</span>
                                            <input type="text" x-model="editingRemarks"
                                                   @keydown.enter="updateCategory(cat.id)"
                                                   @keydown.escape="editingCategoryId = null"
                                                   class="term-input text-[#2a8a2a] text-base">
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="updateCategory(cat.id)"
                                                class="text-[#00ff41] border border-[#00ff41] px-3 py-0.5 text-sm
                                                       hover:bg-[#0a2a0a] transition-colors">
                                            [SAVE]
                                        </button>
                                        <button @click="editingCategoryId = null"
                                                class="text-[#1a4a1a] border border-[#0d2a0d] px-3 py-0.5 text-sm
                                                       hover:text-[#00ff41] transition-colors">
                                            [CANCEL]
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                </div>

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
function bookmarkApp() {
    return {
        // 基本状態
        booted: false,
        bootLines: [],
        blink: true,
        activeTab: 'SEARCH',

        // SEARCH
        search: '',
        searchResults: [],
        searching: false,

        // REGISTER
        urlInput: '',
        memoInput: '',
        saving: false,
        saveMsg: '',

        // CONFIG
        newCategoryName: '',
        newCategoryRemarks: '',
        editingCategoryId: null,
        editingName: '',
        editingRemarks: '',
        categoryMsg: '',

        // Laravelから初期データを受け取る
        bookmarks: @json($bookmarks->load('category')),
        categories: @json($categories),

        allBootLines: [
            'BIOS v2.41 Copyright (C) 1987',
            'Memory Test: 640K OK',
            'Detecting drives... HDD0 [OK]',
            'Loading BOOKMARK.SYS........',
            'Initializing neural index... DONE',
            'WELCOME TO BOOKMARKOS v1.0',
            '─────────────────────────────────',
        ],

        init() {
            let i = 0;
            const timer = setInterval(() => {
                if (i < this.allBootLines.length) {
                    this.bootLines.push(this.allBootLines[i++]);
                } else {
                    clearInterval(timer);
                    setTimeout(() => this.booted = true, 600);
                }
            }, 200);
            setInterval(() => this.blink = !this.blink, 530);
        },

        // ===== SEARCH =====
        async handleSearch() {
            if (!this.search.trim()) {
                this.searchResults = [];
                return;
            }
            this.searching = true;
            try {
                const res = await fetch(`/api/bookmarks/search?q=${encodeURIComponent(this.search)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                this.searchResults = await res.json();
            } catch (e) {
                console.error(e);
            } finally {
                this.searching = false;
            }
        },

        // ===== REGISTER =====
        async handleSave() {
            if (!this.urlInput.trim()) return;
            this.saving = true;

            const logs = [
                'CONNECTING TO HOST...',
                'SCRAPING PAGE DATA...',
                'INVOKING GEMMA ENGINE...',
                'VECTORIZING SUMMARY...',
            ];
            for (const msg of logs) {
                this.saveMsg = msg;
                await new Promise(r => setTimeout(r, 800));
            }

            try {
                const res = await fetch('/api/bookmarks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ url: this.urlInput, memo: this.memoInput }),
                });
                const bookmark = await res.json();
                this.bookmarks.unshift(bookmark);
                this.saveMsg = 'SAVED SUCCESSFULLY.';
                this.urlInput = '';
                this.memoInput = '';
            } catch (e) {
                this.saveMsg = 'ERROR: CONNECTION FAILED.';
            } finally {
                this.saving = false;
                setTimeout(() => this.saveMsg = '', 1500);
            }
        },

        async deleteBookmark(id) {
            if (!confirm('DELETE THIS RECORD?')) return;
            try {
                await fetch(`/api/bookmarks/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                this.bookmarks = this.bookmarks.filter(b => b.id !== id);
                this.searchResults = this.searchResults.filter(b => b.id !== id);
            } catch (e) {
                console.error(e);
            }
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
                    body: JSON.stringify({
                        name: this.newCategoryName,
                        remarks: this.newCategoryRemarks,
                    }),
                });
                const category = await res.json();
                this.categories.push(category);
                this.newCategoryName = '';
                this.newCategoryRemarks = '';
                this.categoryMsg = 'CATEGORY ADDED.';
                setTimeout(() => this.categoryMsg = '', 1500);
            } catch (e) {
                console.error(e);
            }
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
                    body: JSON.stringify({
                        name: this.editingName,
                        remarks: this.editingRemarks,
                    }),
                });
                const updated = await res.json();
                const idx = this.categories.findIndex(c => c.id === id);
                if (idx !== -1) this.categories[idx] = updated;
                this.editingCategoryId = null;
            } catch (e) {
                console.error(e);
            }
        },

        async deleteCategory(id) {
            if (!confirm('DELETE THIS CATEGORY?')) return;
            try {
                await fetch(`/category/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                this.categories = this.categories.filter(c => c.id !== id);
            } catch (e) {
                console.error(e);
            }
        },
    }
}
</script>

@endsection