<?php

namespace App\Hospital\Application\UseCase;

use App\BloodStock\Domain\Repository\BloodStockRepositoryInterface;
use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Hospital\Domain\Repository\HospitalRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;

/**
 * Builds the data required by the hospital dashboard: the hospital profile,
 * an enriched blood-stock overview (available/reserved/expiring), stock and
 * request alerts, the hospital's own request summary with progress, the
 * recent stock activity feed, and a preview of recent notifications.
 */
class HospitalDashboardUseCase
{
    public function __construct(
        private HospitalRepositoryInterface $hospitalRepo,
        private HospitalBloodRequestRepositoryInterface $requestRepo,
        private BloodStockRepositoryInterface $stockRepo,
        private NotificationRepositoryInterface $notificationRepo
    ) {}

    public function execute(int $hospitalUserId): array
    {
        $profile = $this->hospitalRepo->findByUserId($hospitalUserId);
        $stock = $this->stockRepo->findAll();
        $requests = $this->requestRepo->findByHospital($hospitalUserId);

        $totalRequested = 0;
        $totalAllocated = 0;
        $totalPending = 0;

        foreach ($requests as $request) {
            $totalRequested += (int)$request['quantity_requested'];
            $totalAllocated += (int)$request['quantity_allocated'];
            $totalPending += (int)$request['quantity_pending'];
        }

        $enrichedStock = $this->enrichStock($stock);
        $alerts = $this->buildAlerts($stock, $requests);
        $recentNotifications = array_slice(
            $this->notificationRepo->findByUserId($hospitalUserId),
            0,
            3
        );

        return [
            'profile'              => $profile,
            'stock'                => $enrichedStock,
            'requests'             => $requests,
            'total_requests'       => count($requests),
            'open_requests'        => $this->requestRepo->countOpenByHospital($hospitalUserId),
            'total_requested'      => $totalRequested,
            'total_allocated'      => $totalAllocated,
            'total_pending'        => $totalPending,
            'recent_requests'      => array_slice($requests, 0, 5),
            'hospital_name'        => $profile['hospital_name'] ?? '',
            'alerts'               => $alerts,
            'recent_adjustments'   => $this->stockRepo->findRecentAdjustments(6),
            'recent_notifications' => $recentNotifications,
        ];
    }

    /**
     * Augment each stock row with reserved/expiring info, a relative "last
     * updated" label and a 0-100 level indicator so staff can scan quickly.
     */
    private function enrichStock(array $stock): array
    {
        $expiry = $this->stockRepo->findExpiryOverview();
        $reserved = $this->stockRepo->findReservedOverview();

        $maxStock = 1;
        foreach ($stock as $row) {
            $maxStock = max($maxStock, (int)$row['available_bags']);
        }

        $enriched = [];
        foreach ($stock as $row) {
            $group = (string)($row['blood_group'] ?? '-');
            $bags = (int)($row['available_bags'] ?? 0);

            $enriched[] = [
                'blood_group'      => $group,
                'available_bags'   => $bags,
                'reserved_bags'    => (int)($reserved[$group] ?? 0),
                'expiring_soon'    => (int)($expiry[$group]['expiring_soon'] ?? 0),
                'expired'          => (int)($expiry[$group]['expired'] ?? 0),
                'oldest_expiry'    => (string)($expiry[$group]['oldest_expiry'] ?? ''),
                'last_updated'     => (string)($row['last_updated'] ?? ''),
                'last_updated_label' => $this->relativeTime((string)($row['last_updated'] ?? '')),
                'stock_pct'        => (int)round(($bags / $maxStock) * 100),
            ];
        }

        return $enriched;
    }

    /**
     * Derive a compact alert feed: critical/low stock, still-pending request
     * quantities and requests that could be fulfilled from current stock.
     */
    private function buildAlerts(array $stock, array $requests): array
    {
        $alerts = [];

        $stockByGroup = [];
        foreach ($stock as $row) {
            $stockByGroup[(string)$row['blood_group']] = (int)($row['available_bags'] ?? 0);
        }

        foreach ($stock as $row) {
            $group = (string)($row['blood_group'] ?? '-');
            $bags = (int)($row['available_bags'] ?? 0);

            if ($bags <= 0) {
                $alerts[] = [
                    'severity'    => 'critical',
                    'title'       => 'Critical Stock',
                    'message'     => "{$group} blood has 0 bags available.",
                    'kind'        => 'stock',
                    'blood_group' => $group,
                    'bags'        => $bags,
                ];
            } elseif ($bags <= 2) {
                $alerts[] = [
                    'severity'    => 'warning',
                    'title'       => 'Low Stock',
                    'message'     => "{$group} blood has only {$bags} bag" . ($bags > 1 ? 's' : '') . ' remaining.',
                    'kind'        => 'stock',
                    'blood_group' => $group,
                    'bags'        => $bags,
                ];
            }
        }

        foreach ($requests as $request) {
            $status = (string)($request['status'] ?? '');
            $pending = (int)($request['quantity_pending'] ?? 0);
            $group = (string)($request['blood_group'] ?? '');
            $code = (string)($request['request_code'] ?? '');

            if (!in_array($status, ['PENDING', 'PARTIAL'], true) || $pending <= 0) {
                continue;
            }

            $alerts[] = [
                'severity'    => 'warning',
                'title'       => 'Pending Request',
                'message'     => "{$pending} {$group} bag" . ($pending > 1 ? 's' : '') . " still required for {$code}.",
                'kind'        => 'request',
                'blood_group' => $group,
                'bags'        => $pending,
                'code'        => $code,
            ];

            if (($stockByGroup[$group] ?? 0) >= $pending) {
                $alerts[] = [
                    'severity'    => 'success',
                    'title'       => 'Stock Available',
                    'message'     => "{$code} can be fulfilled immediately.",
                    'kind'        => 'request',
                    'blood_group' => $group,
                    'bags'        => $pending,
                    'code'        => $code,
                ];
            }
        }

        return array_slice($alerts, 0, 8);
    }

    private function relativeTime(string $datetime): string
    {
        if ($datetime === '') {
            return '—';
        }

        $ts = strtotime($datetime);
        if ($ts === false) {
            return '—';
        }

        $diff = time() - $ts;

        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            $mins = (int)round($diff / 60);
            return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
        }
        if ($diff < 86400) {
            $hours = (int)round($diff / 3600);
            return $hours . ' hr' . ($hours > 1 ? 's' : '') . ' ago';
        }

        return date('d M Y', $ts);
    }
}
