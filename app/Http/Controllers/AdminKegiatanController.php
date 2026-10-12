<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\Kehadiran;
use App\Support\TandaTangan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Otorisasi kepemilikan kegiatan dipasang di rute (middleware `can:manage,kegiatan`),
 * jadi tidak ada method di sini yang bisa lupa mengeceknya.
 */
class AdminKegiatanController extends Controller
{
    public function index(Request $request): View
    {
        $kegiatan = $request->user()->kegiatan()->withCount('kehadiran')->latest()->get();

        return view('admin.dashboard', [
            'kegiatan' => $kegiatan,
            'formUrl' => route('absen.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->aturan($request));

        $kegiatan = new Kegiatan($data);
        $kegiatan->slug = $this->buatSlug($data['nama']);
        $kegiatan->status = 'aktif';
        $kegiatan->created_by = $request->user()->id;
        $kegiatan->save();

        return redirect()->route('admin.index')->with('status', 'Kegiatan berhasil dibuat.');
    }

    public function edit(Kegiatan $kegiatan): View
    {
        return view('admin.edit-kegiatan', ['kegiatan' => $kegiatan]);
    }

    public function update(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $kegiatan->update($request->validate($this->aturan($request)));

        return redirect()->route('admin.index')->with('status', 'Kegiatan berhasil diperbarui.');
    }

    public function status(Request $request, Kegiatan $kegiatan): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['aktif', 'ditutup'])]]);

        $kegiatan->status = $data['status'];
        $kegiatan->save();

        return redirect()->route('admin.index');
    }

    public function destroy(Kegiatan $kegiatan): RedirectResponse
    {
        $berkas = $kegiatan->kehadiran()->pluck('tanda_tangan_path')->all();

        $kegiatan->delete(); // baris kehadiran ikut terhapus (cascade)
        Storage::disk('local')->delete($berkas);

        return redirect()->route('admin.index')->with('status', 'Kegiatan dan seluruh datanya telah dihapus.');
    }

    public function show(Request $request, Kegiatan $kegiatan): View
    {
        $q = trim((string) $request->query('q', ''));

        $kehadiran = $kegiatan->kehadiran()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('nama', 'like', $like)
                    ->orWhere('instansi', 'like', $like)
                    ->orWhere('unit_kerja', 'like', $like));
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.rekap', [
            'kegiatan' => $kegiatan,
            'kehadiran' => $kehadiran,
            'q' => $q,
            'formUrl' => route('absen.index'),
            'linkLangsung' => route('absen.jenis', $kegiatan),
        ]);
    }

    public function exportPdf(Kegiatan $kegiatan): Response
    {
        $baris = $kegiatan->kehadiran()->oldest()->get()->map(function (Kehadiran $k) {
            $png = Storage::disk('local')->exists($k->tanda_tangan_path)
                ? Storage::disk('local')->get($k->tanda_tangan_path)
                : null;

            return ['data' => $k, 'ttd' => $png ? TandaTangan::dataUri($png) : null];
        });

        return Pdf::loadView('admin.rekap-pdf', ['kegiatan' => $kegiatan, 'baris' => $baris])
            ->setPaper('a4')
            ->download('rekap-'.$kegiatan->slug.'.pdf');
    }

    public function exportCsv(Kegiatan $kegiatan): StreamedResponse
    {
        $judul = [
            'Nama', 'Jenis Peserta', 'Tipe Peserta', 'Jabatan', 'Instansi',
            'Unit Kerja', 'NIP/NIK', 'No. Telepon', 'Tanda Tangan', 'Waktu Submit',
        ];

        return response()->streamDownload(function () use ($kegiatan, $judul) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
            fputcsv($out, $judul);

            foreach ($kegiatan->kehadiran()->oldest()->lazyById() as $k) {
                fputcsv($out, array_map([$this, 'amankanSelCsv'], [
                    $k->nama, $k->jenis_peserta, $k->tipe_peserta, $k->jabatan, $k->instansi,
                    $k->unit_kerja, $k->nip_nik, $k->no_telp,
                    'tersedia (lihat export PDF untuk gambar tanda tangan)',
                    $k->created_at?->format('Y-m-d H:i:s'),
                ]));
            }

            fclose($out);
        }, 'rekap-'.$kegiatan->slug.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function tandaTangan(Kegiatan $kegiatan, Kehadiran $kehadiran): Response
    {
        // Pastikan baris ini benar-benar milik kegiatan yang sudah lolos otorisasi.
        abort_unless((int) $kehadiran->kegiatan_id === (int) $kegiatan->id, 404);
        abort_unless(Storage::disk('local')->exists($kehadiran->tanda_tangan_path), 404);

        return Storage::disk('local')->response($kehadiran->tanda_tangan_path, null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Mencegah CSV/formula injection saat file dibuka di Excel/LibreOffice. */
    private function amankanSelCsv(mixed $nilai): string
    {
        $teks = (string) ($nilai ?? '');

        // Nomor telepon internasional (+62812...) hanya berisi angka, jadi bukan formula.
        if (preg_match('/^\+[0-9]+$/', $teks) === 1) {
            return $teks;
        }

        return $teks !== '' && str_contains("=+-@\t\r", $teks[0]) ? "'".$teks : $teks;
    }

    private function aturan(Request $request): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'tanggal' => ['nullable', 'string', 'max:60'],
            'mulai_at' => ['nullable', 'date'],
            'selesai_at' => array_merge(
                ['nullable', 'date'],
                $request->filled('mulai_at') ? ['after:mulai_at'] : []
            ),
        ];
    }

    private function buatSlug(string $nama): string
    {
        $dasar = Str::slug($nama) ?: 'kegiatan';

        return Str::limit($dasar, 120, '').'-'.Str::lower(Str::random(5));
    }
}
