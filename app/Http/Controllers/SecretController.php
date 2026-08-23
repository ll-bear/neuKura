<?php

namespace App\Http\Controllers;

use App\Models\Credential;
use App\Models\Secret;
use Illuminate\Http\Request;

class SecretController extends Controller
{
    /**
     * 一覧表示(ログイン情報 + シークレットを統合)。
     * 値そのもの(password/fields)は含めず、タイトル/カテゴリ/メモのみ返す。
     * 実際の値は編集時に個別取得する。
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $credentials = $user->credentials()
            ->with('bookmark:id,title,url')
            ->get()
            ->map(fn ($c) => [
                'type' => 'credential',
                'id' => $c->id,
                'category' => 'login',
                'title' => $c->label ?? $c->bookmark?->title ?? $c->bookmark?->url ?? '(無題)',
                'memo' => $c->notes,
                'updated_at' => $c->updated_at,
            ]);

        $secrets = $user->secrets()
            ->get(['id', 'category', 'title', 'memo', 'updated_at'])
            ->map(fn ($s) => [
                'type' => 'secret',
                'id' => $s->id,
                'category' => $s->category,
                'title' => $s->title,
                'memo' => $s->memo,
                'updated_at' => $s->updated_at,
            ]);

        $items = $credentials->concat($secrets)->sortByDesc('updated_at')->values();

        return view('secrets.index', [
            'items' => $items,
        ]);
    }

    /**
     * secret編集用に、対象1件の復号済みfieldsを含めて返す(AJAX/JSON)。
     */
    public function edit(Request $request, Secret $secret)
    {
        abort_unless($secret->user_id === $request->user()->id, 403);

        return response()->json([
            'type' => 'secret',
            'id' => $secret->id,
            'category' => $secret->category,
            'title' => $secret->title,
            'memo' => $secret->memo,
            'fields' => $secret->fields,
        ]);
    }

    public function update(Request $request, Secret $secret)
    {
        abort_unless($secret->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'memo' => 'nullable|string|max:1000',
            'fields' => 'required|array|min:1',
            'fields.*' => 'nullable|string|max:1000',
        ]);

        // categoryは変更不可(fields構成がカテゴリごとに固定のため、
        // 変更したい場合は削除して作り直す運用とする)
        $secret->update([
            'title' => $validated['title'],
            'memo' => $validated['memo'] ?? null,
            'fields' => $validated['fields'],
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Secret $secret)
    {
        abort_unless($secret->user_id === $request->user()->id, 403);

        $secret->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * credential編集用に、対象1件の復号済み値(パスワード含む)とURLを返す(AJAX/JSON)。
     */
    public function editCredential(Request $request, Credential $credential)
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        return response()->json([
            'type' => 'credential',
            'id' => $credential->id,
            'label' => $credential->label,
            'url' => $credential->bookmark?->url,
            'username' => $credential->username,
            'password' => $credential->password_encrypted,
            'notes' => $credential->notes,
        ]);
    }

    public function updateCredential(Request $request, Credential $credential)
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'url' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $credential->update([
            'label' => $validated['label'] ?: null,
            'username' => $validated['username'],
            'password_encrypted' => $validated['password'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // 同一bookmarkを他のcredentialが共有している可能性があるため、
        // URL変更はbookmark自体を更新する(=そのURLを使う全credentialsに影響する点に注意)
        if ($credential->bookmark && $credential->bookmark->url !== $validated['url']) {
            $credential->bookmark->update(['url' => $validated['url']]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyCredential(Request $request, Credential $credential)
    {
        abort_unless($credential->user_id === $request->user()->id, 403);

        $credential->delete();

        return response()->json(['ok' => true]);
    }
}
