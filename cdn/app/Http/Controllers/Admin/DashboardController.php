<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\MangaAuthor;
use App\Models\MangaBadge;
use App\Models\MangaCategory;
use App\Models\MangaChapter;
use App\Models\MangaServer;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_mangas' => Manga::count(),
            'total_chapters' => MangaChapter::count(),
            'total_categories' => MangaCategory::count(),
            'total_authors' => MangaAuthor::count(),
            'total_badges' => MangaBadge::count(),
            'total_servers' => MangaServer::count(),
            'total_users' => User::count(),
            'total_views' => Manga::sum('total_views'),
            'total_follows' => Manga::sum('total_follow'),
        ];

        $recentMangas = Manga::with(['author:id,name', 'badge:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        $topMangas = Manga::with(['author:id,name'])
            ->orderBy('total_views', 'desc')
            ->limit(5)
            ->get();

        $mangasByStatus = Manga::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentMangas' => $recentMangas,
            'topMangas' => $topMangas,
            'mangasByStatus' => $mangasByStatus,
        ]);
    }
}
