<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WasteReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Demo seeder hanya tersedia pada environment local/testing.');

            return;
        }
        $admin = User::firstOrCreate(['email' => 'admin@admin.com'], ['name' => 'Admin Bersih', 'password' => '12341234']);
        $admin->role = 'admin';
        $admin->save();
        $residents = collect([
            ['Nadia Putri', 'user@bersih.test'], ['Rizky Pratama', 'rizky@bersih.test'],
            ['Yaya', 'yaya@bersih.test'], ['Arif Hidayat', 'arif@bersih.test'], ['Siti Rahma', 'siti@bersih.test'],
        ])->map(fn ($person) => User::firstOrCreate(['email' => $person[1]], ['name' => $person[0], 'password' => 'Bersih123!']));
        if (WasteReport::exists()) {
            return;
        }
        $places = ['Jl. Melati, Menteng', 'Taman Suropati, Menteng', 'Jl. Cikini Raya', 'Pasar Gondangdia', 'Jl. Diponegoro, Menteng', 'Taman Situ Lembang', 'Jl. Kebon Sirih', 'Jl. Proklamasi'];
        $titles = [
            'organic' => ['Sisa sayuran di area pasar', 'Tumpukan sampah organik', 'Sisa makanan di dekat taman'],
            'plastic' => ['Botol plastik di tepi jalan', 'Sampah plastik di selokan', 'Kemasan plastik di area taman'],
            'paper' => ['Tumpukan kardus dan kertas', 'Kertas berserakan di trotoar', 'Sampah kertas dekat halte'],
        ];
        for ($i = 0; $i < 48; $i++) {
            $category = ['organic', 'plastic', 'paper'][$i % 3];
            $status = ['completed', 'completed', 'in_progress', 'pending', 'completed', 'rejected', 'pending', 'in_progress'][$i % 8];
            $owner = $residents[$i % 5];
            $date = now()->subDays((int) floor((47 - $i) * 29 / 47))->subHours($i % 8);
            $photo = 'reports/demo-'.($i + 1).'.jpg';
            $source = public_path('demo/'.$category.'.jpg');
            if (! is_file($source)) {
                throw new \RuntimeException('Photo demo missing: '.$source);
            }
            Storage::disk('local')->put($photo, file_get_contents($source));
            $report = new WasteReport([
                'title' => $titles[$category][intdiv($i, 3) % 3], 'description' => 'Contoh laporan demonstrasi. Sampah terlihat menumpuk di area ini dan perlu dipilah serta dibersihkan. Mohon bantuan petugas untuk menindaklanjuti.',
                'category' => $category, 'address' => $places[$i % 8], 'latitude' => -6.19 - ($i % 8) * 0.002, 'longitude' => 106.83 + ($i % 7) * 0.002,
                'photo_path' => $photo,
            ]);
            $report->user_id = $owner->id;
            $report->status = $status;
            $report->created_at = $date;
            $report->updated_at = $date;
            $report->save();
            $report->histories()->create(['user_id' => $owner->id, 'status' => 'pending', 'note' => 'Laporan diterima. Data contoh untuk demonstrasi.'])->update(['created_at' => $date]);
            if ($status !== 'pending') {
                $report->histories()->create(['user_id' => $admin->id, 'status' => $status, 'note' => $status === 'rejected' ? 'Laporan duplikat; lokasi sudah tercatat pada laporan lain.' : 'Laporan sudah ditinjau petugas.'])->update(['created_at' => $date->copy()->addMinutes(30)]);
            }
        }
    }
}
