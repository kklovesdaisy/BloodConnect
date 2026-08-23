<?php

namespace App\Hospital\Application\UseCase;

use App\Hospital\Domain\Repository\HospitalBloodRequestRepositoryInterface;
use App\Hospital\Domain\Repository\HospitalBloodStockRepositoryInterface;
use App\Hospital\Domain\Repository\HospitalRepositoryInterface;
use App\Notification\Domain\Repository\NotificationRepositoryInterface;

/**
 * Builds the data required by the hospital dashboard: the hospital profile,
 * the hospital's OWN blood-stock overview (bags the system allocated against
 * this hospital's requests), stock and request alerts, the hospital's own
 * request summary with progress, and a preview of recent notifications.
 */
class HospitalDashboardUseCase
{
    private const ALL_BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public function __construct(
        private HospitalRepositoryInterface $hospitalRepo,
        private HospitalBloodRequestRepositoryInterface $requestRepo,
        private HospitalBloodStockRepositoryInterface $hospitalStockRepo,
        private NotificationRepositoryInterface $notificationRepo
    ) {}

    public function execute(int $hospitalUserId): array
    {
        $profile = $this->hospitalRepo->findByUserId($hospitalUserId);
        $stock = $this->hospitalOwnStock($hospitalUserId);
        $requests = $this->requestRepo->findByHospital($hospitalUserId);

        $totalRequested = 0;
        $totalAllocated = 0;
        $totalPending = 0;

        foreach ($requests as $request) {
            $totalRequested += (int)$request['quantity_requested'];
            $totalAllocated += (int)$request['quantity_allocated'];
            $totalPending += (int)$request['quantity_pending'];
        }

        $enrichedStock = $this->enrichStock($hospitalUserId, $stock);
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
            'recent_notifications' => $recentNotifications,
        ];
    }

    /**
     * The hospital's own inventory: bags the system allocated against this
     * hospital's stock requests that are still within their shelf life.
     * Always returns all 8 blood groups so the UI stays consistent.
     */
    private function hospitalOwnStock(int $hospitalUserId): array
    {
        $byGroup = [];
        foreach ($this->hospitalStockRepo->findAllByHospital($hospitalUserId) as $row) {
            $byGroup[(string)$row['blood_group']] = $row;
        }

        $stock = [];
        foreach (self::ALL_BLOOD_GROUPS as $group) {
            $stock[] = $byGroup[$group] ?? [
                'blood_group'    => $group,
                'available_bags' => 0,
                'last_updated'   => '',
            ];
        }

        return $stock;
    }

    /**
     * Augment each hospital stock row with expiry info and a 0-100 level
     * indicator so staff can scan quickly. Reserved does not apply here:
     * every counted bag is already owned by the hospital.
     */
    private function enrichStock(int $hospitalUserId, array $stock): array
    {
        $expiry = $this->hospitalStockRepo->findExpiryOverviewByHospital($hospitalUserId);

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
                'reserved_bags'    => 0,
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
     * Derive the alert feed: critical/low stock, still-pending request
     * quantities and requests that could be fulfilled from current stock.
     * No cap is applied — every alert is always returned.
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

        return $alerts;
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
