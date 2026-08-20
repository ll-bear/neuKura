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

        $items = $credentials->concat($secrets)
            ->sortByDesc('match')
            ->values();

        return view('secrets.picker', [
            'items' => $items,
            'domain' => $domain,
            'source' => $request->query('source', 'web'),
            // 新規登録の呼び水: URLありでpickerに来た場合、フォームにプリフィルし
            // autoNew=1 なら新規登録パネルを開いた状態で表示する
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

            // encryptedキャストにより取得時点で復号済み
            return response()->json(['value' => $item->password_encrypted]);
        }

        $item = $user->secrets()->findOrFail($validated['id']);

        // secretsは複数フィールド持ちうるので、代表フィールド(password/key)を優先返却
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

        // 同一URLの既存bookmarkがあれば再利用(同一サイトへの複数アカウント登録に対応)
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

    /**
     * URLに紐づかない汎用シークレット(Wi-Fi/ライセンスキー/PINなど)の新規保存。
     * カテゴリごとにfieldsのキー構成が変わるため、バリデーションはcategoryで分岐する。
     */
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

        // 代表フィールド(picker上でそのままコピーする値)を決定
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
