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

    public function exportExcel(Request $request)
    {
        $exportStart = $request->query('export_start');
        $exportEnd = $request->query('export_end');

        if ($exportStart && $exportEnd && $exportStart > $exportEnd) {
            return redirect()->back()->withErrors(['export_error' => 'Tanggal "Sampai" tidak boleh lebih awal dari tanggal "Dari"!']);
        }

        $contents = DB::table('contents')
            ->join('users', 'contents.users_id', '=', 'users.users_id')
            ->where('contents.status_del', '0')
            ->select('contents.*', 'users.users_name')
            ->orderBy('contents.content_category')
            ->get();

        $playlistsQuery = Playlist::where('status_del', '0')
            ->orderBy('playlist_start_date');

        if ($exportStart && $exportEnd) {
            $playlistsQuery->where(function ($q) use ($exportStart, $exportEnd) {
                $q->whereDate('playlist_start_date', '<=', $exportEnd)
                    ->whereDate('playlist_end_date', '>=', $exportStart);
            });
        } elseif ($exportStart) {
            $playlistsQuery->whereDate('playlist_end_date', '>=', $exportStart);
        } elseif ($exportEnd) {
            $playlistsQuery->whereDate('playlist_start_date', '<=', $exportEnd);
        }

        $playlists = $playlistsQuery->get();

        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F27D00']],
            'alignment' => ['horizontal' => 'center'],
        ];

        // ===== SHEET 1: Daftar Konten =====
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Daftar Konten');

        $sheet1->fromArray(
            ['No', 'Judul Konten', 'Kategori', 'Major / Department', 'Tipe File', 'Durasi (detik)', 'Pengunggah', 'Tanggal Upload'],
            null,
            'A1'
        );
        $sheet1->getStyle('A1:H1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($contents as $index => $content) {
            $sheet1->fromArray([
                $index + 1,
                $content->content_title,
                $content->content_category,
                $content->content_major_and_department ?? '-',
                strtoupper($content->content_type),
                $content->content_duration,
                $content->users_name,
                $content->content_upload_date ?? '-',
            ], null, 'A' . $row);
            $row++;
        }
        $sheet1->setAutoFilter('A1:H' . max(1, $row - 1));
        foreach (range('A', 'H') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet1->getStyle('A1:H' . max(1, $row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // ===== SHEET 2: Ringkasan Kategori & Major/Department =====
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ringkasan Kategori');

        // --- Tabel Kategori (Kolom A-B) ---
        $sheet2->fromArray(['Kategori', 'Total Konten'], null, 'A1');
        $sheet2->getStyle('A1:B1')->applyFromArray($headerStyle);

        $categorySummary = $contents->groupBy('content_category')->map->count();
        $rowCat = 2;
        foreach ($categorySummary as $category => $total) {
            $sheet2->fromArray([$category ?: 'Tanpa Kategori', $total], null, 'A' . $rowCat);
            $rowCat++;
        }

        // Filter hanya sampai baris data terakhir
        if ($rowCat > 2) {
            $sheet2->setAutoFilter('A1:B' . ($rowCat - 1));
        }

        // Jarak 1 baris kosong sebagai pembatas filter
        $rowCat++;
        $sheet2->setCellValue('A' . $rowCat, 'Total Keseluruhan');
        $sheet2->setCellValue('B' . $rowCat, $contents->count());
        $sheet2->getStyle('A' . $rowCat . ':B' . $rowCat)->getFont()->setBold(true);
        $sheet2->getStyle('A1:B' . ($rowCat - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet2->getStyle('A' . $rowCat . ':B' . $rowCat)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


        // --- Tabel Major / Department (Kolom D-E) ---
        $sheet2->fromArray(['Major / Department', 'Total Konten'], null, 'D1');
        $sheet2->getStyle('D1:E1')->applyFromArray($headerStyle);

        $deptSummary = $contents->groupBy('content_major_and_department')->map->count();
        $rowDept = 2;
        foreach ($deptSummary as $dept => $total) {
            $sheet2->fromArray([$dept ?: 'Lainnya', $total], null, 'D' . $rowDept);
            $rowDept++;
        }

        // Filter hanya sampai baris data terakhir
        if ($rowDept > 2) {
            $sheet2->setAutoFilter('D1:E' . ($rowDept - 1));
        }

        // Jarak 1 baris kosong sebagai pembatas filter
        $rowDept++;
        $sheet2->setCellValue('D' . $rowDept, 'Total Keseluruhan');
        $sheet2->setCellValue('E' . $rowDept, $contents->count());
        $sheet2->getStyle('D' . $rowDept . ':E' . $rowDept)->getFont()->setBold(true);
        $sheet2->getStyle('D1:E' . ($rowDept - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet2->getStyle('D' . $rowDept . ':E' . $rowDept)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


        // >>> AUTO-FIT KOLOM OTOMATIS + EXTRA PADDING SUPAYA TIDAK KEPOTONG <<<
        foreach (range('A', 'E') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // Tambahan sedikit lebar ekstra khusus untuk kolom A, B, D, dan E (karena ada header filter & teks panjang)
        $sheet2->getColumnDimension('A')->setWidth($sheet2->getColumnDimension('A')->getWidth() + 4);
        $sheet2->getColumnDimension('B')->setWidth($sheet2->getColumnDimension('B')->getWidth() + 4);
        $sheet2->getColumnDimension('D')->setWidth($sheet2->getColumnDimension('D')->getWidth() + 6); // Extra space untuk icon filter
        $sheet2->getColumnDimension('E')->setWidth($sheet2->getColumnDimension('E')->getWidth() + 4);

        // ===== SHEET 3: Ringkasan Pengunggah =====
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Ringkasan Pengunggah');
        $sheet3->fromArray(['Pengunggah', 'Total Upload'], null, 'A1');
        $sheet3->getStyle('A1:B1')->applyFromArray($headerStyle);

        $rowUploader = 2;
        $uploaderSummary = $contents->groupBy('users_name')->map->count();
        foreach ($uploaderSummary as $uploader => $total) {
            $sheet3->fromArray([$uploader, $total], null, 'A' . $rowUploader);
            $rowUploader++;
        }
        $sheet3->setAutoFilter('A1:B' . max(1, $rowUploader - 1));
        foreach (range('A', 'B') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet3->getStyle('A1:B' . max(1, $rowUploader - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // ===== SHEET 4: Jadwal Playlist =====
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Jadwal Playlist');
        $periodLabel = ($exportStart || $exportEnd)
            ? 'Periode: ' . ($exportStart ?? 'awal') . ' s/d ' . ($exportEnd ?? 'akhir')
            : 'Semua Periode';
        $sheet4->setCellValue('A1', 'Jadwal Playlist — ' . $periodLabel);
        $sheet4->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet4->mergeCells('A1:C1');

        $sheet4->fromArray(['Playlist ID', 'Start Date', 'End Date'], null, 'A2');
        $sheet4->getStyle('A2:C2')->applyFromArray($headerStyle);

        $rowPlaylist = 3;
        foreach ($playlists as $playlist) {
            $sheet4->fromArray([
                'Playlist ' . $playlist->playlist_id,
                date('Y-m-d', strtotime($playlist->playlist_start_date)),
                date('Y-m-d', strtotime($playlist->playlist_end_date)),
            ], null, 'A' . $rowPlaylist);
            $rowPlaylist++;
        }
        $sheet4->setAutoFilter('A2:C' . max(2, $rowPlaylist - 1));
        foreach (range('A', 'C') as $col) {
            $sheet4->getColumnDimension($col)->setAutoSize(true);
        }
        if ($rowPlaylist > 3) {
            $sheet4->getStyle('A2:C' . ($rowPlaylist - 1))
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $fileSuffix = ($exportStart || $exportEnd)
            ? ($exportStart ?? 'awal') . '_sd_' . ($exportEnd ?? 'akhir')
            : now()->format('Y-m-d_His');
        $filename = 'Laporan_Signage_' . $fileSuffix . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
