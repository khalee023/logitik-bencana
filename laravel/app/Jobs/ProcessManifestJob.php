<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessManifestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $manifestId;

    /**
     * Jumlah percobaan ulang jika job gagal.
     */
    public int $tries = 3;

    /**
     * Waktu tunggu dalam detik sebelum job dianggap timeout.
     */
    public int $timeout = 120;

    public function __construct(int $manifestId)
    {
        $this->manifestId = $manifestId;
        $this->onQueue('high');
    }

    /**
     * Eksekusi job: pembentukan PDF manifes dan dispatch notifikasi.
     * Saat ini merupakan stub — akan diimplementasikan dengan DomPDF.
     */
    public function handle(): void
    {
        Log::info("ProcessManifestJob: Processing manifest ID {$this->manifestId}");

        // TODO: Generate PDF manifest menggunakan DomPDF
        // $manifest = ManifestPengiriman::findOrFail($this->manifestId);
        // $pdf = PDF::loadView('manifests.pdf', compact('manifest'));
        // Storage::put("manifests/{$manifest->kode_manifest}.pdf", $pdf->output());

        // TODO: Kirim notifikasi ke koordinator dan admin terkait
        // Notification::send($users, new ManifestApproved($manifest));

        Log::info("ProcessManifestJob: Manifest ID {$this->manifestId} processed successfully");
    }

    /**
     * Handle jika job gagal setelah semua percobaan ulang.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessManifestJob FAILED for manifest ID {$this->manifestId}: {$exception->getMessage()}");
    }
}
