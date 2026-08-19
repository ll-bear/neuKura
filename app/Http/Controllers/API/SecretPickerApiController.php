<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\FetchFaviconJob;
use App\Models\Bookmark;
use App\Models\Secret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Chrome拡張機能のpopupから叩くJSON API版。
 * 画面(Blade)版の SecretPickerController と処理内容は共通だが、
 * 認証がセッションCookieではなくSanctumトークンである点が異なる。
 *
 * NOTE: ability名('credentials:read' 等)は既存のスコープ命名規則に
 * 合わせて調整してください。ここでは仮の名称を使っています。
 */
class SecretPickerApiController
{
    public function index(Request $request)
    {
        $user = $request->user();
        $domain = $request->query('domain');

        $credentials = $user->credentials()
            ->with('bookmark:id,title,url,favicon_path')
            ->get()
            ->map(function ($c) use ($domain) {
                $bookmark = $c->bookmark;

                return [
                    'kind' => 'credential',
                    'id' => $c->id,
                    'title' => $c->label ?? $bookmark?->title ?? $bookmark?->url ?? '(無題)',
                    'sub' => $bookmark?->url,
                    'username' => $c->username,
                    'favicon_url' => $bookmark?->favicon_path ? Storage::url($bookmark->favicon_path) : null,
                    'match' => $domain && $bookmark && str_contains($bookmark->url, $domain),
                ];
            });

        $secrets = $user->secrets()
            ->select('id', 'title', 'category')
            ->get()
            ->map(fn ($s) => [
                'kind' => 'secret',
                'id' => $s->id,
                'title' => $s->title,
                'sub' => $s->category,
                'username' => null,
                'favicon_url' => null,
                'match' => false,
            ]);

        $items = $credentials->concat($secrets)->sortByDesc('match')->values();

        return response()->json(['items' => $items]);
    }

    public function reveal(Request $request)
    {
        $validated = $request->validate([
            'kind' => 'required|in:credential,secret',
            'id' => 'required|integer',
        ]);

        $user = $request->user();

        if ($validated['kind'] === 'credential') {
            $item = $user->credentials()->findOrFail($validated['id']);

            return response()->json([
                'username' => $item->username,
                'password' => $item->password_encrypted,
            ]);
        }

        $item = $user->secrets()->findOrFail($validated['id']);
        $value = $item->fields['password'] ?? $item->fields['key'] ?? reset($item->fields);

        return response()->json(['password' => $value]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'required|string|max:255',
            'comment' => 'nullable|string|max:1000',
        ]);

        $title = parse_url($validated['url'], PHP_URL_HOST) ?? $validated['url'];
        $user = $request->user();

        $bookmark = $user->bookmarks()->where('url', $validated['url'])->first();

        if (! $bookmark) {
            $bookmark = Bookmark::create([
                'user_id' => $user->id,
                'category_id' => null,
                'title' => $title,
                'url' => $validated['url'],
            ]);

            FetchFaviconJob::dispatch($bookmark->id);
        }

        $credential = $user->credentials()->create([
            'bookmark_id' => $bookmark->id,
            'username' => $validated['username'],
            'password_encrypted' => $validated['password'],
            'notes' => $validated['comment'],
        ]);

        return response()->json([
            'kind' => 'credential',
            'id' => $credential->id,
            'username' => $credential->username,
            'password' => $credential->password_encrypted,
        ]);
    }

    public function storeSecret(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:'.implode(',', Secret::CATEGORIES),
            'title' => 'required|string|max:255',
            'memo' => 'nullable|string|max:1000',
            'fields' => 'required|array|min:1',
            'fields.*' => 'nullable|string|max:1000',
        ]);

        $secret = $request->user()->secrets()->create($validated);

        $value = $validated['fields']['password']
            ?? $validated['fields']['key']
            ?? reset($validated['fields']);

        return response()->json(['kind' => 'secret', 'id' => $secret->id, 'password' => $value]);
    }
}
