<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\Content;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $fullName = $user->users_name ?? 'Admin';
        $firstName = explode(' ', trim($fullName))[0];

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $categoryFilter = $request->query('category', 'all');
        $todayStr = now()->format('Y-m-d');

        $playlists = Playlist::with(['details.content.user'])
            ->where('status_del', '0')
            ->get();

        foreach ($playlists as $playlist) {
            $categories = $playlist->details->pluck('content.content_category')->filter()->unique()->values();
            $playlist->categories = $categories;
        }

        $filteredPlaylists = $categoryFilter === 'all'
            ? $playlists
            : $playlists->filter(fn($p) => $p->categories->contains($categoryFilter))->values();

        $trashedPlaylists = Playlist::with(['details.content'])->where('status_del', '1')->get();

        $totalContent = Content::query()->where('status_del', '0')->count();
        $activePlaylists = $playlists->count();
        $averagePlaytime = round(Content::query()->where('status_del', '0')->average('content_duration') ?? 0, 1);

        // Active Playlist sekarang ditentukan dari TANGGAL, sama persis logic yang dipakai TV
        $activePlaylistToday = Playlist::where('status_del', '0')
            ->whereDate('playlist_start_date', '<=', $todayStr)
            ->whereDate('playlist_end_date', '>=', $todayStr)
            ->first();

        $allContents = DB::table('contents')
            ->join('users', 'contents.users_id', '=', 'users.users_id')
            ->where('contents.status_del', '0')
            ->select('contents.*', 'users.users_name')
            ->get();

        $availableCategories = Content::query()
            ->where('status_del', '0')
            ->distinct()
            ->pluck('content_category')
            ->filter()
            ->values();

        // ================= BANGUN DATA KALENDER (bar horizontal per minggu) =================
        $currentMonthCarbon = Carbon::create($year, $month, 1);
        $startOfMonth = $currentMonthCarbon->copy()->startOfMonth();
        $endOfMonth = $currentMonthCarbon->copy()->endOfMonth();
        $startingDayOfWeek = $startOfMonth->dayOfWeek == 0 ? 6 : $startOfMonth->dayOfWeek - 1;

        $calendarCells = array_fill(0, $startingDayOfWeek, null);
        for ($d = 1; $d <= $endOfMonth->day; $d++) {
            $calendarCells[] = $currentMonthCarbon->copy()->setDay($d)->format('Y-m-d');
        }
        while (count($calendarCells) % 7 !== 0) {
            $calendarCells[] = null;
        }
        $weeksRaw = array_chunk($calendarCells, 7);

        $categoryColors = [
            'event' => ['bg' => 'bg-orange-100', 'border' => 'border-orange-300', 'text' => 'text-uc-orange'],
            'daily' => ['bg' => 'bg-emerald-100', 'border' => 'border-emerald-300', 'text' => 'text-emerald-700'],
        ];

        $calendarWeeks = [];
        foreach ($weeksRaw as $week) {
            $weekDates = array_filter($week);
            $weekStart = !empty($weekDates) ? min($weekDates) : null;
            $weekEnd = !empty($weekDates) ? max($weekDates) : null;

            $bars = [];
            if ($weekStart && $weekEnd) {
                $weekPlaylists = $filteredPlaylists->filter(function ($p) use ($weekStart, $weekEnd) {
                    $pStart = substr($p->playlist_start_date, 0, 10);
                    $pEnd = substr($p->playlist_end_date, 0, 10);
                    return $pStart <= $weekEnd && $pEnd >= $weekStart;
                })->sortBy('playlist_start_date')->values();

                $rowOccupancy = [];

                foreach ($weekPlaylists as $p) {
                    $pStart = substr($p->playlist_start_date, 0, 10);
                    $pEnd = substr($p->playlist_end_date, 0, 10);
                    $clampedStart = max($pStart, $weekStart);
                    $clampedEnd = min($pEnd, $weekEnd);

                    $startCol = array_search($clampedStart, $week) + 1;
                    $endCol = array_search($clampedEnd, $week) + 2;

                    $assignedRow = null;
                    foreach ($rowOccupancy as $rowIdx => $occupiedUntil) {
                        if ($startCol >= $occupiedUntil) {
                            $assignedRow = $rowIdx;
                            break;
                        }
                    }
                    if ($assignedRow === null) {
                        $assignedRow = count($rowOccupancy);
                    }
                    $rowOccupancy[$assignedRow] = $endCol;

                    $mainCategory = strtolower($p->categories->first() ?? 'daily');
                    $colors = $categoryColors[$mainCategory] ?? ['bg' => 'bg-gray-100', 'border' => 'border-gray-300', 'text' => 'text-gray-600'];

                    $items = $p->details->sortBy('playlist_order')->map(function ($d) {
                        return [
                            'order' => $d->playlist_order,
                            'title' => $d->content->content_title ?? '-',
                            'duration' => $d->content->content_duration ?? 0,
                        ];
                    })->values();

                    $videos = $p->details->sortBy('playlist_order')->map(function ($d) {
                        $content = $d->content;
                        $extension = strtolower($content->content_type ?? '');
                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        $fullUrl = Content::resolveFileUrl($content->content_file_path_url ?? '', $content->content_type ?? null);

                        return [
                            'url' => $fullUrl,
                            'title' => $content->content_title ?? '-',
                            'duration' => $content->content_duration ?? ($isImage ? 5 : 10),
                            'isImage' => $isImage,
                        ];
                    })->values();

                    $bars[] = [
                        'id' => $p->playlist_id,
                        'start_col' => $startCol,
                        'end_col' => $endCol,
                        'row' => $assignedRow + 1,
                        'bg' => $colors['bg'],
                        'border' => $colors['border'],
                        'text' => $colors['text'],
                        'start_date' => $p->playlist_start_date,
                        'end_date' => $p->playlist_end_date,
                        'items' => $items,
                        'videos' => $videos,
                        'is_live' => $p->playlist_id == ($activePlaylistToday->playlist_id ?? null),
                    ];
                }
            }

            $calendarWeeks[] = [
                'days' => $week,
                'bars' => $bars,
                'row_count' => !empty($bars) ? max(array_column($bars, 'row')) : 0,
            ];
        }

        return view('dashboard', compact(
            'firstName',
            'totalContent',
            'activePlaylists',
            'averagePlaytime',
            'activePlaylistToday',
            'allContents',
            'trashedPlaylists',
            'month',
            'year',
            'categoryFilter',
            'availableCategories',
            'currentMonthCarbon',
            'todayStr',
            'calendarWeeks'
        ));
    }

    public function exportExcel()
    {
        $contents = DB::table('contents')
            ->join('users', 'contents.users_id', '=', 'users.users_id')
            ->where('contents.status_del', '0')
            ->select('contents.*', 'users.users_name')
            ->orderBy('contents.content_category')
            ->get();

        $spreadsheet = new Spreadsheet();

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Daftar Konten');

        $headers = ['No', 'Judul Konten', 'Kategori', 'Tipe File', 'Durasi (detik)', 'Pengunggah'];
        $sheet1->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F27D00']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet1->getStyle('A1:F1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($contents as $index => $content) {
            $sheet1->fromArray([
                $index + 1,
                $content->content_title,
                $content->content_category,
                strtoupper($content->content_type),
                $content->content_duration,
                $content->users_name,
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet1->getStyle('A1:F' . ($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ringkasan Kategori');

        $sheet2->fromArray(['Kategori', 'Total Konten'], null, 'A1');
        $sheet2->getStyle('A1:B1')->applyFromArray($headerStyle);

        $categorySummary = $contents->groupBy('content_category')->map->count();

        $row = 2;
        foreach ($categorySummary as $category => $total) {
            $sheet2->fromArray([$category ?: 'Tanpa Kategori', $total], null, 'A' . $row);
            $row++;
        }
        $sheet2->fromArray(['Total Keseluruhan', $contents->count()], null, 'A' . $row);
        $sheet2->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);

        foreach (range('A', 'B') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet2->getStyle('A1:B' . $row)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Ringkasan Pengunggah');

        $sheet3->fromArray(['Pengunggah', 'Total Upload'], null, 'A1');
        $sheet3->getStyle('A1:B1')->applyFromArray($headerStyle);

        $uploaderSummary = $contents->groupBy('users_name')->map->count();

        $row = 2;
        foreach ($uploaderSummary as $uploader => $total) {
            $sheet3->fromArray([$uploader, $total], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'B') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet3->getStyle('A1:B' . ($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Laporan_Konten_Signage_' . now()->format('Y-m-d_His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}