@extends('layouts.app')

@section('content')
<div x-data="secretManager({
        secrets: @js($secrets),
        editUrlTemplate: @js(route('secrets.edit', ':id')),
        updateUrlTemplate: @js(route('secrets.update', ':id')),
        destroyUrlTemplate: @js(route('secrets.destroy', ':id')),
        csrfToken: @js(csrf_token()),
    })"
    class="max-w-2xl mx-auto px-4 py-6"
>
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-lg font-semibold text-slate-800">シークレット管理</h1>
        <a href="{{ route('secrets.picker') }}" class="text-xs text-violet-600 hover:underline">
            + 新規保存はpickerから
        </a>
    </div>

    {{-- カテゴリフィルタ --}}
    <div class="flex gap-2 mb-4 flex-wrap">
        <template x-for="cat in categories" :key="cat.id">
            <button
                @click="activeCat = cat.id"
                class="text-xs px-3 py-1.5 rounded-full border transition-colors backdrop-blur-sm"
                :class="activeCat === cat.id ? 'chip-active' : 'chip-inactive'"
                x-text="cat.label"
            ></button>
        </template>
    </div>

    {{-- 一覧 --}}
    <div class="space-y-2" x-show="filteredSecrets.length > 0">
        <template x-for="secret in filteredSecrets" :key="secret.id">
            <div class="glass-card !cursor-default flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-medium text-slate-800 truncate" x-text="secret.title"></p>
                    <p class="text-[11px] text-slate-400" x-text="categoryLabel(secret.category)"></p>
                    <p x-show="secret.memo" class="text-[11px] text-slate-400 truncate mt-0.5" x-text="secret.memo"></p>
                </div>
                <div class="flex gap-1.5 shrink-0">
                    <button @click="openEdit(secret)" type="button" class="glass-btn !py-1.5 !px-2.5 text-[11px]">編集</button>
                    <button @click="confirmDelete(secret)" type="button" class="glass-btn !py-1.5 !px-2.5 text-[11px] !text-red-600 !border-red-200 hover:!bg-red-50">削除</button>
                </div>
            </div>
        </template>
    </div>

    <p x-show="filteredSecrets.length === 0" class="text-sm text-slate-400 text-center py-10">
        該当するシークレットはありません
    </p>

    {{-- 編集パネル --}}
    <div x-show="editing" x-cloak x-transition class="glass-card mt-5 !cursor-default">
        <p class="text-sm font-medium text-slate-800 mb-3">
            <span x-text="categoryLabel(editing?.category)"></span> を編集
        </p>

        <div class="space-y-3" x-show="editing">
            <div>
                <label class="glass-label">タイトル</label>
                <input type="text" x-model="editForm.title" class="glass-input">
            </div>

            <template x-for="f in editFieldConfig" :key="f.key">
                <div>
                    <label class="glass-label" x-text="f.label"></label>
                    <div class="flex gap-2">
                        <input type="text" x-model="editForm.fields[f.key]" class="glass-input flex-1">
                        <button
                            x-show="f.generate"
                            @click="editForm.fields[f.key] = generatePassword()"
                            type="button"
                            class="glass-btn whitespace-nowrap"
                        >自動生成</button>
                    </div>
                </div>
            </template>

            <div>
                <label class="glass-label">メモ</label>
                <input type="text" x-model="editForm.memo" class="glass-input">
            </div>
        </div>

        <div class="flex gap-2 pt-3">
            <button @click="closeEdit()" type="button" class="glass-btn flex-1">キャンセル</button>
            <button @click="saveEdit()" type="button" class="glass-btn-primary flex-1">保存</button>
        </div>
    </div>

    {{-- トースト --}}
    <div x-show="toast" x-cloak x-transition
        class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-sm px-4 py-2.5 rounded-full shadow-lg"
        x-text="toastMessage"
    ></div>
</div>
@endsection

@push('scripts')
<script>
function secretManager({ secrets, editUrlTemplate, updateUrlTemplate, destroyUrlTemplate, csrfToken }) {
    return {
        secrets,
        activeCat: 'all',
        editing: null,
        editForm: { title: '', memo: '', fields: {} },
        toast: false,
        toastMessage: '',

        categories: [
            { id: 'all', label: 'すべて' },
            { id: 'wifi', label: 'Wi-Fi' },
            { id: 'license', label: 'ライセンスキー' },
            { id: 'pin', label: 'PIN' },
            { id: 'uncategorized', label: '未分類' },
            { id: 'other', label: 'その他' },
        ],

        // picker画面と同じフィールド構成(カテゴリごとの入力項目定義)
        secretFieldConfig: {
            wifi: [
                { key: 'ssid', label: 'SSID' },
                { key: 'password', label: 'パスワード', generate: true },
            ],
            license: [
                { key: 'product', label: '製品名' },
                { key: 'key', label: 'ライセンスキー' },
            ],
            pin: [
                { key: 'pin', label: 'PINコード' },
            ],
            other: [
                { key: 'value', label: '値' },
            ],
            uncategorized: [
                { key: 'value', label: '値' },
            ],
        },

        categoryLabel(id) {
            const c = this.categories.find(c => c.id === id);
            return c ? c.label : id;
        },

        get filteredSecrets() {
            return this.activeCat === 'all'
                ? this.secrets
                : this.secrets.filter(s => s.category === this.activeCat);
        },

        get editFieldConfig() {
            if (!this.editing) return [];
            return this.secretFieldConfig[this.editing.category] || this.secretFieldConfig.uncategorized;
        },

        async openEdit(secret) {
            const res = await fetch(editUrlTemplate.replace(':id', secret.id), {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json();

            this.editing = secret;
            this.editForm = {
                title: data.title,
                memo: data.memo || '',
                fields: data.fields || {},
            };
        },

        closeEdit() {
            this.editing = null;
            this.editForm = { title: '', memo: '', fields: {} };
        },

        async saveEdit() {
            if (!this.editing || !this.editForm.title) return;

            const res = await fetch(updateUrlTemplate.replace(':id', this.editing.id), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(this.editForm),
            });

            if (!res.ok) {
                this.showToast('保存に失敗しました');
                return;
            }

            // ローカルの一覧表示も更新
            const target = this.secrets.find(s => s.id === this.editing.id);
            if (target) {
                target.title = this.editForm.title;
                target.memo = this.editForm.memo;
            }

            this.showToast('保存しました');
            this.closeEdit();
        },

        async confirmDelete(secret) {
            if (!confirm(`「${secret.title}」を削除します。よろしいですか？`)) return;

            const res = await fetch(destroyUrlTemplate.replace(':id', secret.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });

            if (!res.ok) {
                this.showToast('削除に失敗しました');
                return;
            }

            this.secrets = this.secrets.filter(s => s.id !== secret.id);
            this.showToast('削除しました');
        },

        generatePassword(length = 8, includeSymbols = true) {
            const lower = 'abcdefghijkmnopqrstuvwxyz';
            const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
            const digits = '23456789';
            const symbols = '!@#$%^&*-_';
            const chars = lower + upper + digits + (includeSymbols ? symbols : '');
            const values = new Uint32Array(length);
            crypto.getRandomValues(values);
            return Array.from(values, v => chars[v % chars.length]).join('');
        },

        showToast(msg) {
            this.toastMessage = msg;
            this.toast = true;
            setTimeout(() => { this.toast = false; }, 2000);
        },
    };
}
</script>
@endpush

@push('styles')
<style>
.glass-card {
    background: rgba(255, 255, 255, 0.55);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    border-radius: 14px;
    padding: 12px;
}
.glass-label {
    display: block;
    font-size: 12px;
    color: rgb(100, 116, 139);
    margin-bottom: 4px;
}
.glass-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(100, 116, 139, 0.25);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    color: rgb(30, 41, 59);
}
.glass-input:focus {
    outline: none;
    border-color: rgba(139, 92, 246, 0.5);
    background: rgba(255, 255, 255, 0.95);
}
.glass-btn {
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(100, 116, 139, 0.25);
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    color: rgb(51, 65, 85);
    cursor: pointer;
}
.glass-btn:hover {
    background: rgba(255, 255, 255, 0.9);
}
.glass-btn-primary {
    background: rgba(16, 185, 129, 0.15);
    border: 1px solid rgba(16, 185, 129, 0.35);
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    font-weight: 500;
    color: rgb(4, 120, 87);
    cursor: pointer;
}
.glass-btn-primary:hover {
    background: rgba(16, 185, 129, 0.25);
}
.chip-active {
    background: rgba(139, 92, 246, 0.15);
    border-color: rgba(139, 92, 246, 0.35);
    color: rgb(91, 33, 182);
}
.chip-inactive {
    background: rgba(255, 255, 255, 0.4);
    border-color: rgba(100, 116, 139, 0.2);
    color: rgb(100, 116, 139);
}
</style>
@endpush
