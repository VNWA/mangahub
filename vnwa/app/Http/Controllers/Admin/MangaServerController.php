<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MangaServer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MangaServerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MangaServer::withCount('chapterContents')->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $servers = $query->paginate(15)->withQueryString();

        return Inertia::render('admin/server/Index', [
            'servers' => $servers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/server/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:manga_servers,name'],
            'description' => ['nullable', 'string'],
        ]);

        MangaServer::create($validated);

        return redirect()->route('servers.index')
            ->with('success', 'Server đã được tạo thành công.');
    }

    public function show(MangaServer $server): Response
    {
        $server->loadCount('chapterContents');

        return Inertia::render('admin/server/Show', [
            'server' => $server,
        ]);
    }

    public function edit(MangaServer $server): Response
    {
        return Inertia::render('admin/server/Edit', [
            'server' => $server,
        ]);
    }

    public function update(Request $request, MangaServer $server): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:manga_servers,name,'.$server->id],
            'description' => ['nullable', 'string'],
        ]);

        $server->update($validated);

        return redirect()->route('servers.index')
            ->with('success', 'Server đã được cập nhật thành công.');
    }

    public function destroy(MangaServer $server): RedirectResponse
    {
        $server->delete();

        return redirect()->route('servers.index')
            ->with('success', 'Server đã được xóa thành công.');
    }
}
