<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Insert Users Custom
        DB::table('users')->insert([
            [
                'users_id' => 'U0001',
                'users_name' => 'Angeline Liemanto',
                'users_password' => bcrypt('password123'),
                'users_role' => '1',
                'users_email' => 'angeline@student.ciputra.ac.id',
                'users_acc_created' => '2026-01-15',
                'status_del' => '0',
            ],
            [
                'users_id' => 'U0002',
                'users_name' => 'Admin Kampus',
                'users_password' => bcrypt('adminpass'),
                'users_role' => '1',
                'users_email' => 'admin@ciputra.ac.id',
                'users_acc_created' => '2026-02-01',
                'status_del' => '0',
            ],
        ]);

        // 2. Insert Contents
        DB::table('contents')->insert([
            [
                'users_id' => 'U0001',
                'content_title' => 'Video Profil Universitas Ciputra',
                'content_file_path_url' => 'https://storage.ciputra.ac.id/videos/profile_uc.mp4',
                'content_category' => 'Daily',
                'content_type' => 'mp4',
                'content_duration' => 120,
                'content_start_date' => '2026-07-01 00:00:00',
                'content_end_date' => '2026-12-31 23:59:59',
                'content_status' => true,
                'status_del' => '0',
            ],
            [
                'users_id' => 'U0001',
                'content_title' => 'Banner Pengumuman Welcoming Week',
                'content_file_path_url' => 'https://storage.ciputra.ac.id/images/welcoming_week.png',
                'content_category' => 'Event',
                'content_type' => 'png',
                'content_duration' => 15,
                'content_start_date' => '2026-07-20 08:00:00',
                'content_end_date' => '2026-08-05 17:00:00',
                'content_status' => true,
                'status_del' => '0',
            ],
            [
                'users_id' => 'U0002',
                'content_title' => 'Iklan Seminar AI & Innovation',
                'content_file_path_url' => 'https://storage.ciputra.ac.id/videos/seminar_ai.mp4',
                'content_category' => 'Event',
                'content_type' => 'mp4',
                'content_duration' => 45,
                'content_start_date' => '2026-07-22 09:30:00',
                'content_end_date' => '2026-07-30 15:00:00',
                'content_status' => false,
                'status_del' => '0',
            ],
        ]);

        // 3. Insert Playlists
        DB::table('playlists')->insert([
            ['playlist_date' => '2026-07-25', 'playlist_duration' => 180, 'status_del' => '0'],
            ['playlist_date' => '2026-07-26', 'playlist_duration' => 135, 'status_del' => '0'],
        ]);

        // 4. Insert Playlist Details
        DB::table('playlist_details')->insert([
            ['contents_id' => 1, 'playlist_id' => 1, 'playlist_order' => 1],
            ['contents_id' => 2, 'playlist_id' => 1, 'playlist_order' => 2],
            ['contents_id' => 3, 'playlist_id' => 1, 'playlist_order' => 3],
            ['contents_id' => 1, 'playlist_id' => 2, 'playlist_order' => 1],
            ['contents_id' => 2, 'playlist_id' => 2, 'playlist_order' => 2],
        ]);
    }
}