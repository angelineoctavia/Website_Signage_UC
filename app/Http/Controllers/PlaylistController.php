<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Playlist;
use App\Models\PlaylistDetail;
use App\Models\Content;

class PlaylistController extends Controller
{
    public function index()
    {
        $contents = $this->getContentsWithMeta();
        $bookedDates = $this->getBookedDates(null);

        return view('playlist', [
            'contents' => $contents,
            'editMode' => false,
            'bookedDates' => $bookedDates,
        ]);
    }

    public function edit($id)
    {
        $playlist = Playlist::with(['details.content'])->findOrFail($id);
        $contents = $this->getContentsWithMeta();
        $bookedDates = $this->getBookedDates($id);

        $existingItems = $playlist->details->sortBy('playlist_order')->map(function ($d) {
            $content = $d->content;
            $extension = strtolower($content->content_type ?? '');
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $fullUrl = Content::resolveFileUrl($content->content_file_path_url ?? '', $content->content_type ?? null);

            return [
                'id' => $content->contents_id,
                'title' => $content->content_title,
                'url' => $fullUrl,
                'isImage' => $isImage,
                'duration' => $content->content_duration ?? ($isImage ? 5 : 10),
            ];
        })->values();

        return view('playlist', [
            'contents' => $contents,
            'editMode' => true,
            'playlist' => $playlist,
            'existingItems' => $existingItems,
            'bookedDates' => $bookedDates,
        ]);
    }

    private function getBookedDates($excludeId = null)
    {
        $query = Playlist::where('status_del', '0');
        if ($excludeId) {
            $query->where('playlist_id', '!=', $excludeId);
        }
        $bookedRanges = $query->get(['playlist_start_date', 'playlist_end_date']);

        $bookedDates = [];
        foreach ($bookedRanges as $r) {
            $period = \Carbon\CarbonPeriod::create($r->playlist_start_date, $r->playlist_end_date);
            foreach ($period as $date) {
                $bookedDates[] = $date->format('Y-m-d');
            }
        }
        return array_values(array_unique($bookedDates));
    }

    public function store(Request $request)
    {
        $this->validatePlaylistRequest($request);
        $this->checkOverlap($request, null);

        $totalDuration = $this->calculateTotalDuration($request->contents);

        $playlist = Playlist::create([
            'playlist_start_date' => $request->playlist_start_date,
            'playlist_end_date'   => $request->playlist_end_date . ' 23:59:59',
            'playlist_duration' => $totalDuration,
            'status_del' => '0'
        ]);

        foreach ($request->contents as $index => $contentId) {
            PlaylistDetail::create([
                'playlist_id' => $playlist->playlist_id,
                'contents_id' => $contentId,
                'playlist_order' => $index + 1
            ]);
        }

        return redirect()->route('playlist.index')->with('success', 'Playlist berhasil disimpan ke database!');
    }

    public function update(Request $request, $id)
    {
        $this->validatePlaylistRequest($request);
        $this->checkOverlap($request, $id);

        $totalDuration = $this->calculateTotalDuration($request->contents);

        Playlist::where('playlist_id', $id)->update([
            'playlist_start_date' => $request->playlist_start_date,
            'playlist_end_date'   => $request->playlist_end_date . ' 23:59:59',
            'playlist_duration' => $totalDuration,
        ]);

        PlaylistDetail::where('playlist_id', $id)->delete();
        foreach ($request->contents as $index => $contentId) {
            PlaylistDetail::create([
                'playlist_id' => $id,
                'contents_id' => $contentId,
                'playlist_order' => $index + 1
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Playlist berhasil diperbarui!');
    }

    public function destroy($id)
    {
        Playlist::query()->where('playlist_id', $id)->update(['status_del' => '1']);
        return redirect()->route('dashboard')->with('success', 'Playlist berhasil dipindahkan ke sampah.');
    }

    public function restore($id)
    {
        Playlist::query()->where('playlist_id', $id)->update(['status_del' => '0']);
        return redirect()->route('dashboard')->with('success', 'Playlist berhasil dipulihkan!');
    }

    private function getContentsWithMeta()
    {
        $contents = DB::table('contents')->where('status_del', '0')->get();

        foreach ($contents as $content) {
            $path = $content->content_file_path_url ?? '';
            $content->full_url = Content::resolveFileUrl($path, $content->content_type ?? null);

            $extension = strtolower($content->content_type ?? '');
            $content->is_image = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $content->duration_seconds = $content->content_duration ?? ($content->is_image ? 5 : 10);
        }

        return $contents;
    }

    private function validatePlaylistRequest(Request $request)
    {
        $request->validate([
            'playlist_start_date' => ['required', 'date'],
            'playlist_end_date'   => ['required', 'date', 'after_or_equal:playlist_start_date'],
            'contents' => ['required', 'array', 'min:1'],
            'contents.*' => ['exists:contents,contents_id']
        ], [
            'playlist_end_date.after_or_equal' => 'End Date tidak boleh lebih awal dari Start Date.',
        ]);
    }

    private function checkOverlap(Request $request, $excludeId = null)
    {
        $query = Playlist::where('status_del', '0')
            ->where(function ($q) use ($request) {
                $q->where('playlist_start_date', '<=', $request->playlist_end_date)
                    ->where('playlist_end_date', '>=', $request->playlist_start_date);
            });

        if ($excludeId) {
            $query->where('playlist_id', '!=', $excludeId);
        }

        if ($query->exists()) {
            abort(redirect()->back()
                ->withErrors(['playlist_start_date' => 'Rentang tanggal ini sudah dipakai playlist lain. Silakan pilih tanggal yang berbeda.'])
                ->withInput());
        }
    }

    private function calculateTotalDuration(array $contentIds)
    {
        $selectedContents = DB::table('contents')->whereIn('contents_id', $contentIds)->get();

        return $selectedContents->sum(function ($content) {
            $extension = strtolower(pathinfo($content->content_file_path_url, PATHINFO_EXTENSION));
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            return $content->content_duration ?? ($isImage ? 5 : 10);
        });
    }

    public function displaySignage()
    {
        $now = now();

        // Cari playlist aktif hari ini
        $activePlaylist = Playlist::with(['details.content'])
            ->where('status_del', '0')
            ->whereDate('playlist_start_date', '<=', $now)
            ->whereDate('playlist_end_date', '>=', $now)
            ->first();

        return view('signage-display', [
            'activePlaylist' => $activePlaylist
        ]);
    }

    public function getActivePlaylistJson()
    {
        $now = now();
        $activePlaylist = Playlist::with(['details.content'])
            ->where('status_del', '0')
            ->whereDate('playlist_start_date', '<=', $now)
            ->whereDate('playlist_end_date', '>=', $now)
            ->first();

        if (!$activePlaylist) {
            return response()->json(['media' => []]);
        }

        $mediaList = $activePlaylist->details->sortBy('playlist_order')->map(function ($detail) {
            $content = $detail->content;
            $extension = strtolower($content->content_type ?? '');
            $isImg = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

            // Gunakan resolveFileUrl agar konsisten dengan halaman dashboard/preview
            $fullUrl = Content::resolveFileUrl($content->content_file_path_url ?? '', $content->content_type ?? null);

            return [
                'url' => $fullUrl,
                'type' => $isImg ? 'image' : 'video',
                'duration' => $content->content_duration ?? ($isImg ? 5 : 10)
            ];
        })->values();

        return response()->json(['media' => $mediaList]);
    }
}