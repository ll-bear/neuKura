<?php

namespace App\Http\Controllers;

use App\Jobs\FetchFaviconJob;
use App\Models\Bookmark;
use App\Models\Secret;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SecretPickerController extends Controller
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
                    'category' => 'login',
                ];
            });

        $secrets = $user->secrets()
            ->get(['id', 'category', 'title'])
            ->map(fn ($s) => [
                'kind' => 'secret',
                'id' => $s->id,
                'title' => $s->title,
                'sub' => $s->category,
                'username' => null,
                'favicon_url' => null,
                'match' => false,
                'category' => $s->category,
            ]);

        $items = $credentials->concat($secrets)
            ->sortByDesc('match')
            ->values();

        return view('secrets.picker', [
            'items' => $items,
            'domain' => $domain,
            'source' => $request->query('source', 'web'),
            'prefillUrl' => $request->query('url'),
            'autoNew' => $request->boolean('autoNew'),
        ]);
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

            return response()->json(['value' => $item->password_encrypted]);
        }

        $item = $user->secrets()->findOrFail($validated['id']);

        $value = $item->fields['password'] ?? $item->fields['key'] ?? reset($item->fields);

        return response()->json(['value' => $value]);
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
            'value' => $credential->password_encrypted,
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

        $secret = $request->user()->secrets()->create([
            'category' => $validated['category'],
            'title' => $validated['title'],
            'fields' => $validated['fields'],
            'memo' => $validated['memo'] ?? null,
        ]);

        $primaryValue = $validated['fields']['password']
            ?? $validated['fields']['key']
            ?? reset($validated['fields']);

        return response()->json([
            'kind' => 'secret',
            'id' => $secret->id,
            'value' => $primaryValue,
        ]);
    }

    /**
     * 「未分類」のsecretを正式なカテゴリ(ログイン/Wi-Fi/ライセンスキー/PIN)へ変換する。
     * - login: bookmark+credentialを新規作成(または既存bookmark再利用)し、元のsecretは削除
     * - それ以外: secretのcategory/title/fields/memoを直接更新(category変更を許可する唯一の経路)
     */
    public function assignSecret(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'secret_id' => 'required|integer',
            'category' => 'required|in:login,wifi,license,pin',
            'title' => 'required|string|max:255',
            'memo' => 'nullable|string|max:1000',
            // login用
            'url' => 'required_if:category,login|nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'password' => 'required_if:category,login|nullable|string|max:255',
            // wifi/license/pin用
            'fields' => 'required_unless:category,login|nullable|array',
            'fields.*' => 'nullable|string|max:1000',
        ]);

        $secret = $user->secrets()->findOrFail($validated['secret_id']);

        if ($validated['category'] === 'login') {
            $bookmark = $user->bookmarks()->where('url', $validated['url'])->first();

            if (! $bookmark) {
                $bookmark = Bookmark::create([
                    'user_id' => $user->id,
                    'category_id' => null,
                    'title' => $validated['title'],
                    'url' => $validated['url'],
                ]);

                FetchFaviconJob::dispatch($bookmark->id);
            }

            $credential = $user->credentials()->create([
                'bookmark_id' => $bookmark->id,
                'label' => $validated['title'],
                'username' => $validated['username'] ?? '',
                'password_encrypted' => $validated['password'],
                'notes' => $validated['memo'] ?? null,
            ]);

            $secret->delete();

            return response()->json([
                'kind' => 'credential',
                'id' => $credential->id,
                'value' => $credential->password_encrypted,
                'removed_secret_id' => $secret->id,
            ]);
        }

        // wifi / license / pin への変換
        $secret->update([
            'category' => $validated['category'],
            'title' => $validated['title'],
            'fields' => $validated['fields'],
            'memo' => $validated['memo'] ?? null,
        ]);

        $primaryValue = $validated['fields']['password']
            ?? $validated['fields']['key']
            ?? reset($validated['fields']);

        return response()->json([
            'kind' => 'secret',
            'id' => $secret->id,
            'value' => $primaryValue,
        ]);
    }
}
