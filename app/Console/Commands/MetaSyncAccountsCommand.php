<?php

namespace App\Console\Commands;

use App\Models\ConnectedAccount;
use App\Models\MetaCredential;
use App\Models\TokenActivityLog;
use App\Services\MetaGraphService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MetaSyncAccountsCommand extends Command
{
    protected $signature = 'meta:sync-accounts';
    protected $description = 'Sinkronisasi daftar Facebook Pages dan Instagram Business Accounts via Meta Graph API';

    public function handle(MetaGraphService $metaService): int
    {
        $cred = MetaCredential::getActive();
        $token = $cred->getActiveToken();

        if (empty($token)) {
            $this->error('Access token Meta API belum dikonfigurasi.');
            return Command::FAILURE;
        }

        $this->info('Mengambil daftar Facebook Pages dari Meta Graph API (/me/accounts)...');
        $pagesRes = $metaService->getManagedPages($token);

        if (!$pagesRes['success']) {
            $errMsg = $pagesRes['error']['message'] ?? 'Gagal mengambil akun dari Meta';
            $this->error("Error: {$errMsg}");
            TokenActivityLog::create([
                'action' => 'full_sync',
                'status' => 'failed',
                'details' => $errMsg,
            ]);
            return Command::FAILURE;
        }

        $pages = $pagesRes['data'];
        $this->info("Berhasil menemukan " . count($pages) . " Facebook Page.");

        $syncedPageIds = [];

        foreach ($pages as $page) {
            $pageId = $page['id'];
            $pageName = $page['name'] ?? 'Halaman Facebook';
            $pageCategory = $page['category'] ?? null;
            $pageToken = $page['access_token'] ?? $token;

            $syncedPageIds[] = $pageId;

            // Ambil Instagram Business Account terkait
            $igAccount = $metaService->getLinkedInstagramAccount($pageId, $pageToken);
            $igUserId = $igAccount['id'] ?? null;
            $igUsername = $igAccount['username'] ?? null;
            $igName = $igAccount['name'] ?? null;
            $igPic = $igAccount['profile_picture_url'] ?? null;

            // Cek publishing quota jika IG terhubung
            $quotaUsage = 0;
            $quotaTotal = 100;
            if ($igUserId) {
                $limitRes = $metaService->getContentPublishingLimit($igUserId, $pageToken);
                if ($limitRes['success']) {
                    $quotaUsage = $limitRes['quota_usage'] ?? 0;
                    $quotaTotal = $limitRes['config']['quota_total'] ?? 100;
                }
            }

            ConnectedAccount::updateOrCreate(
                ['page_id' => $pageId],
                [
                    'page_name' => $pageName,
                    'page_category' => $pageCategory,
                    'page_access_token' => $pageToken,
                    'ig_user_id' => $igUserId,
                    'ig_username' => $igUsername,
                    'ig_name' => $igName,
                    'ig_profile_picture_url' => $igPic,
                    'is_active' => true,
                    'ig_publishing_quota_usage' => $quotaUsage,
                    'ig_publishing_quota_total' => $quotaTotal,
                    'last_synced_at' => Carbon::now(),
                    'last_verified_at' => Carbon::now(),
                ]
            );

            $this->line("  ✓ Synced: {$pageName}" . ($igUsername ? " (IG: @{$igUsername})" : " (Tanpa IG)"));
        }

        // Soft-Deactivation: Nonaktifkan akun yang sebelumnya ada di database tapi tidak lagi ada di Meta
        $deactivatedCount = ConnectedAccount::whereNotIn('page_id', $syncedPageIds)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        if ($deactivatedCount > 0) {
            $this->warn("  ⚠ {$deactivatedCount} akun dinonaktifkan (Soft-Deactivation) karena tidak lagi ditemukan di Meta.");
        }

        $cred->update([
            'token_status' => 'valid',
            'last_verified_at' => Carbon::now(),
        ]);

        TokenActivityLog::create([
            'action' => 'full_sync',
            'status' => 'success',
            'details' => sprintf(
                'Full sync berhasil: %d Page aktif diperbarui, %d akun dinonaktifkan.',
                count($syncedPageIds),
                $deactivatedCount
            ),
        ]);

        $this->info('Sinkronisasi akun Meta selesai dengan sukses!');
        return Command::SUCCESS;
    }
}