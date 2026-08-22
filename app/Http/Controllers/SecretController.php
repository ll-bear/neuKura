<?php

namespace App\Http\Controllers;

use App\Models\Secret;
use Illuminate\Http\Request;

class SecretController extends Controller
{
    /**
     * 一覧表示。値そのもの(fields)は含めず、タイトル/カテゴリ/メモのみ返す。
     * 実際の値は編集時に個別取得する(一覧でまとめて平文を渡さないため)。
     */
    public function index(Request $request)
    {
        $secrets = $request->user()->secrets()
            ->orderByDesc('updated_at')
            ->get(['id', 'category', 'title', 'memo', 'updated_at']);

        return view('secrets.index', [
            'secrets' => $secrets,
        ]);
    }

    /**
     * 編集用に、対象1件の復号済みfieldsを含めて返す(AJAX/JSON)。
     */
    public function edit(Request $request, Secret $secret)
    {
        abort_unless($secret->user_id === $request->user()->id, 403);

        return response()->json([
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
}
