<?php

namespace App\Http\Controllers;

use App\Jobs\FetchFaviconJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SecretPickerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $domain = $request->query('domain');

        $credentials = $user->credentials()
            ->select('id', 'title', 'url', 'username', 'favicon_path')
            ->get()
            ->map(fn ($c) => [
                'kind' => 'credential',
                'id' => $c->id,
                'title' => $c->title,
                'sub' => $c->url,
                'username' => $c->username,
                'favicon_url' => $c->favicon_path ? Storage::url($c->favicon_path) : null,
                'match' => $domain && str_contains($c->url, $domain),
            ]);

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

            return response()->json(['value' => $item->password]);
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

        $credential = $request->user()->credentials()->create([
            'title' => $title,
            'url' => $validated['url'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'comment' => $validated['comment'],
        ]);

        FetchFaviconJob::dispatch($credential->id);

        return response()->json([
            'kind' => 'credential',
            'id' => $credential->id,
            'value' => $credential->password,
        ]);
    }
}
