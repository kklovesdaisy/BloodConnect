<?php

namespace App\Admin\Application\UseCase;

use App\Shared\Infrastructure\Database\Database;

/**
 * Supplies the filtered row sets and summary totals backing every admin
 * report screen (donor / patient / blood request / donation). Both the HTML
 * views and the PDF / Excel exporters consume this same data so the on-screen
 * figures always match the exported files.
 */
class ReportDataProvider
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @return array{filters: array, rows: array, totals: array}
     */
    public function donors(array $filters): array
    {
        $f = $this->normalize($filters, ['q', 'status', 'blood_group', 'location', 'from', 'to']);

        $where = 'u.user_type_id = 2 AND u.deleted_at IS NULL';
        $params = [];

        if ($f['status'] === 'active') {
            $where .= ' AND u.is_active = 1';
        } elseif ($f['status'] === 'inactive') {
            $where .= ' AND u.is_active != 1';
        }
        if ($f['location'] !== '') {
            $where .= ' AND (d.state_region = ? OR d.township = ?)';
            $params[] = $f['location'];
            $params[] = $f['location'];
        }
        if ($f['blood_group'] !== '') {
            $where .= ' AND (u.blood_group = ? OR md.label = ?)';
            $params[] = $f['blood_group'];
            $params[] = $f['blood_group'];
        }
        if ($f['q'] !== '') {
            $like = '%' . $f['q'] . '%';
            $where .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR COALESCE(md.label, u.blood_group) LIKE ? OR d.state_region LIKE ? OR d.township LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($f['from'] !== '') {
            $where .= ' AND DATE(u.created_at) >= ?';
            $params[] = $f['from'];
        }
        if ($f['to'] !== '') {
            $where .= ' AND DATE(u.created_at) <= ?';
            $params[] = $f['to'];
        }

        $stmt = $this->db->prepare("
            SELECT
                CONCAT('DNR-', u.user_id + 4200) AS donor_code,
                u.user_id,
                u.username,
                COALESCE(md.label, u.blood_group) AS blood_group,
                u.phone,
                TRIM(CONCAT_WS(', ', NULLIF(d.township, ''), NULLIF(d.state_region, ''))) AS location,
                u.is_active,
                u.available,
                u.next_available_date
            FROM users u
            LEFT JOIN donors d ON d.user_id = u.user_id
            LEFT JOIN master_data md ON md.category = 'BLOOD_GROUP' AND md.code = u.blood_group
            WHERE {$where}
            ORDER BY u.user_id DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $rows = array_map(function (array $donor): array {
            $status = (int)($donor['is_active'] ?? 1) === 1 ? 'Active' : 'Inactive';
            $available = $status === 'Active' && ((int)($donor['available'] ?? 0) === 1 || ($donor['next_available_date'] ?? null) === null)
                ? 'Available'
                : 'Unavailable';
            return [
                $donor['donor_code'],
                $donor['username'],
                $donor['blood_group'] ?: '—',
                $donor['phone'] ?: '—',
                $donor['location'] ?: '—',
                $status,
            ];
        }, $rows);

        return [
            'filters' => $f,
            'rows' => $rows,
            'totals' => $this->donorTotals($where, $params),
        ];
    }

    /**
     * @return array{filters: array, rows: array, totals: array}
     */
    public function patients(array $filters): array
    {
        $f = $this->normalize($filters, ['q', 'status', 'blood_group', 'from', 'to']);

        $where = 'u.user_type_id = 3 AND u.deleted_at IS NULL';
        $params = [];

        if ($f['status'] === 'active') {
            $where .= ' AND u.is_active = 1';
        } elseif ($f['status'] === 'inactive') {
            $where .= ' AND u.is_active != 1';
        }
        if ($f['blood_group'] !== '') {
            $where .= ' AND (u.blood_group = ? OR md.label = ?)';
            $params[] = $f['blood_group'];
            $params[] = $f['blood_group'];
        }
        if ($f['q'] !== '') {
            $like = '%' . $f['q'] . '%';
            $where .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR COALESCE(md.label, u.blood_group) LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($f['from'] !== '') {
            $where .= ' AND DATE(u.created_at) >= ?';
            $params[] = $f['from'];
        }
        if ($f['to'] !== '') {
            $where .= ' AND DATE(u.created_at) <= ?';
            $params[] = $f['to'];
        }

        $stmt = $this->db->prepare("
            SELECT
                CONCAT('PT-', u.user_id) AS patient_code,
                u.username,
                COALESCE(md.label, u.blood_group) AS blood_group,
                u.phone,
                CASE WHEN u.is_active = 1 THEN 'Active' ELSE 'Inactive' END AS status
            FROM users u
            LEFT JOIN master_data md ON md.category = 'BLOOD_GROUP' AND md.code = u.blood_group
            WHERE {$where}
            ORDER BY u.user_id DESC
        ");
        $stmt->execute($params);
        $rows = array_map(function (array $p): array {
            return [
                $p['patient_code'],
                $p['username'],
                $p['blood_group'] ?: '—',
                $p['phone'] ?: '—',
                $p['status'],
            ];
        }, $stmt->fetchAll());

        return [
            'filters' => $f,
            'rows' => $rows,
            'totals' => $this->patientTotals($where, $params),
        ];
    }

    /**
     * @return array{filters: array, rows: array, totals: array}
     */
    public function bloodRequests(array $filters): array
    {
        $f = $this->normalize($filters, ['q', 'status', 'urgency', 'blood_group', 'from', 'to']);

        $where = '1 = 1';
        $params = [];

        if ($f['status'] !== '') {
            $where .= ' AND br.status = ?';
            $params[] = (int)$f['status'];
        }
        if ($f['urgency'] !== '') {
            $where .= ' AND UPPER(br.urgency) = ?';
            $params[] = strtoupper($f['urgency']);
        }
        if ($f['blood_group'] !== '') {
            $where .= ' AND br.blood_group_needed = ?';
            $params[] = $f['blood_group'];
        }
        if ($f['q'] !== '') {
            $like = '%' . $f['q'] . '%';
            $where .= ' AND (br.request_code LIKE ? OR br.patient_name LIKE ? OR br.hospital_name LIKE ? OR br.contact_phone LIKE ? OR br.state_region LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($f['from'] !== '') {
            $where .= ' AND DATE(br.created_at) >= ?';
            $params[] = $f['from'];
        }
        if ($f['to'] !== '') {
            $where .= ' AND DATE(br.created_at) <= ?';
            $params[] = $f['to'];
        }

        $stmt = $this->db->prepare("
            SELECT
                br.request_code,
                br.patient_name,
                br.blood_group_needed AS blood_group,
                br.hospital_name,
                br.urgency,
                br.contact_phone,
                COALESCE(rs.label, CONCAT('Status #', br.status)) AS status_label
            FROM blood_requests br
            LEFT JOIN master_data rs ON rs.category = 'REQUEST_STATUS' AND rs.id = br.status
            WHERE {$where}
            ORDER BY br.request_id DESC
        ");
        $stmt->execute($params);
        $rows = array_map(function (array $r): array {
            return [
                $r['request_code'],
                $r['patient_name'],
                $r['blood_group'],
                $r['hospital_name'],
                $r['urgency'] ?: '—',
                $r['status_label'],
            ];
        }, $stmt->fetchAll());

        return [
            'filters' => $f,
            'rows' => $rows,
            'totals' => $this->bloodRequestTotals($where, $params),
        ];
    }

    /**
     * @return array{filters: array, rows: array, totals: array}
     */
    public function donations(array $filters): array
    {
        $f = $this->normalize($filters, ['q', 'status', 'blood_group', 'from', 'to']);

        $where = '1 = 1';
        $params = [];

        if ($f['status'] !== '') {
            $where .= ' AND dh.status = ?';
            $params[] = (int)$f['status'];
        }
        if ($f['blood_group'] !== '') {
            $where .= ' AND br.blood_group_needed = ?';
            $params[] = $f['blood_group'];
        }
        if ($f['q'] !== '') {
            $like = '%' . $f['q'] . '%';
            $where .= ' AND (donor.username LIKE ? OR br.patient_name LIKE ? OR br.hospital_name LIKE ? OR br.request_code LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($f['from'] !== '') {
            $where .= ' AND DATE(dh.donation_date) >= ?';
            $params[] = $f['from'];
        }
        if ($f['to'] !== '') {
            $where .= ' AND DATE(dh.donation_date) <= ?';
            $params[] = $f['to'];
        }

        $stmt = $this->db->prepare("
            SELECT
                CONCAT('DNT-', LPAD(dh.donation_id, 4, '0')) AS donation_code,
                donor.username AS donor_name,
                br.patient_name,
                dh.donation_date,
                br.hospital_name,
                br.blood_group_needed AS blood_group,
                COALESCE(rs.label, CONCAT('Status #', dh.status)) AS status_label
            FROM donation_history dh
            LEFT JOIN blood_requests br ON br.request_id = dh.request_id
            LEFT JOIN users donor ON donor.user_id = dh.donor_id
            LEFT JOIN master_data rs ON rs.category = 'REQUEST_STATUS' AND rs.id = dh.status
            WHERE {$where}
            ORDER BY dh.donation_date DESC, dh.donation_id DESC
        ");
        $stmt->execute($params);
        $rows = array_map(function (array $d): array {
            return [
                $d['donation_code'],
                $d['donor_name'] ?: '—',
                $d['patient_name'] ?: '—',
                date('Y-m-d', strtotime((string)$d['donation_date'])),
                $d['hospital_name'] ?: '—',
                $d['status_label'],
            ];
        }, $stmt->fetchAll());

        return [
            'filters' => $f,
            'rows' => $rows,
            'totals' => $this->donationTotals($where, $params),
        ];
    }

    /**
     * Blood bags allocated from central stock to hospitals (the system side
     * of donations), with full bag and hospital details.
     *
     * @return array{filters: array, rows: array, totals: array}
     */
    public function hospitalAllocations(array $filters): array
    {
        $f = $this->normalize($filters, ['q', 'status', 'blood_group', 'from', 'to']);

        $where = '1 = 1';
        $params = [];

        if ($f['status'] !== '') {
            $where .= ' AND r.status = ?';
            $params[] = $f['status'];
        }
        if ($f['blood_group'] !== '') {
            $where .= ' AND r.blood_group = ?';
            $params[] = $f['blood_group'];
        }
        if ($f['q'] !== '') {
            $like = '%' . $f['q'] . '%';
            $where .= ' AND (r.hospital_name LIKE ? OR r.request_code LIKE ? OR h.phone LIKE ? OR u.email LIKE ? OR h.address LIKE ?)';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if ($f['from'] !== '') {
            $where .= ' AND DATE(r.created_at) >= ?';
            $params[] = $f['from'];
        }
        if ($f['to'] !== '') {
            $where .= ' AND DATE(r.created_at) <= ?';
            $params[] = $f['to'];
        }

        $stmt = $this->db->prepare("
            SELECT
                r.request_code,
                r.hospital_name,
                TRIM(CONCAT_WS(', ', NULLIF(h.township, ''), NULLIF(h.state_region, ''))) AS location,
                r.blood_group,
                r.quantity_requested,
                r.quantity_allocated,
                r.quantity_pending,
                r.status,
                r.created_at
            FROM hospital_blood_requests r
            LEFT JOIN hospitals h ON h.user_id = r.hospital_user_id
            LEFT JOIN users u ON u.user_id = r.hospital_user_id
            WHERE {$where}
            ORDER BY r.created_at DESC, r.request_id DESC
        ");
        $stmt->execute($params);
        $rows = array_map(function (array $r): array {
            return [
                $r['request_code'],
                $r['hospital_name'] ?: '—',
                $r['location'] ?: '—',
                $r['blood_group'],
                (string)(int)$r['quantity_requested'],
                (string)(int)$r['quantity_allocated'],
                (string)(int)$r['quantity_pending'],
                $r['status'],
                date('Y-m-d', strtotime((string)$r['created_at'])),
            ];
        }, $stmt->fetchAll());

        return [
            'filters' => $f,
            'rows' => $rows,
            'totals' => $this->hospitalAllocationTotals($where, $params),
        ];
    }

    public static function statusOptions(): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT id, label FROM master_data WHERE category = 'REQUEST_STATUS' ORDER BY id"
        );
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
    }

    private function donorTotals(string $where, array $params): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN u.is_active != 1 THEN 1 ELSE 0 END) AS inactive,
                SUM(CASE WHEN u.is_active = 1 AND (u.available = 1 OR u.next_available_date IS NULL) THEN 1 ELSE 0 END) AS available
            FROM users u
            LEFT JOIN donors d ON d.user_id = u.user_id
            LEFT JOIN master_data md ON md.category = 'BLOOD_GROUP' AND md.code = u.blood_group
            WHERE {$where}
        ");
        $stmt->execute($params);
        $t = $stmt->fetch();

        return [
            'Total Donors' => (int)$t['total'],
            'Active' => (int)$t['active'],
            'Inactive' => (int)$t['inactive'],
            'Available' => (int)$t['available'],
            'Unavailable' => (int)$t['total'] - (int)$t['available'],
        ];
    }

    private function patientTotals(string $where, array $params): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN u.is_active != 1 THEN 1 ELSE 0 END) AS inactive
            FROM users u
            LEFT JOIN master_data md ON md.category = 'BLOOD_GROUP' AND md.code = u.blood_group
            WHERE {$where}
        ");
        $stmt->execute($params);
        $t = $stmt->fetch();

        return [
            'Total Patients' => (int)$t['total'],
            'Active' => (int)$t['active'],
            'Inactive' => (int)$t['inactive'],
        ];
    }

    private function bloodRequestTotals(string $where, array $params): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                COALESCE(rs.label, CONCAT('Status #', br.status)) AS status_label
            FROM blood_requests br
            LEFT JOIN master_data rs ON rs.category = 'REQUEST_STATUS' AND rs.id = br.status
            WHERE {$where}
            GROUP BY rs.id, rs.label, br.status
            ORDER BY rs.id
        ");
        $stmt->execute($params);
        $counts = $stmt->fetchAll();

        $totals = ['Total Requests' => 0];
        $grand = 0;
        foreach ($counts as $row) {
            $grand += (int)$row['total'];
            $totals[$row['status_label']] = (int)$row['total'];
        }
        $totals['Total Requests'] = $grand;

        return $totals;
    }

    private function donationTotals(string $where, array $params): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                COALESCE(rs.label, CONCAT('Status #', dh.status)) AS status_label
            FROM donation_history dh
            LEFT JOIN master_data rs ON rs.category = 'REQUEST_STATUS' AND rs.id = dh.status
            WHERE {$where}
            GROUP BY rs.id, rs.label, dh.status
            ORDER BY rs.id
        ");
        $stmt->execute($params);
        $counts = $stmt->fetchAll();

        $totals = ['Total Donations' => 0];
        $grand = 0;
        foreach ($counts as $row) {
            $grand += (int)$row['total'];
            $totals[$row['status_label']] = (int)$row['total'];
        }
        $totals['Total Donations'] = $grand;

        return $totals;
    }

    private function hospitalAllocationTotals(string $where, array $params): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(r.quantity_requested), 0) AS requested,
                COALESCE(SUM(r.quantity_allocated), 0) AS allocated,
                COALESCE(SUM(r.quantity_pending), 0) AS pending
            FROM hospital_blood_requests r
            LEFT JOIN hospitals h ON h.user_id = r.hospital_user_id
            LEFT JOIN users u ON u.user_id = r.hospital_user_id
            WHERE {$where}
        ");
        $stmt->execute($params);
        $t = $stmt->fetch();

        return [
            'Total Allocations' => (int)$t['total'],
            'Bags Requested' => (int)$t['requested'],
            'Bags Allocated' => (int)$t['allocated'],
            'Bags Pending' => (int)$t['pending'],
        ];
    }

    private function normalize(array $filters, array $allowed): array
    {
        $out = [];
        foreach ($allowed as $key) {
            $out[$key] = isset($filters[$key]) ? trim((string)$filters[$key]) : '';
        }
        return $out;
    }
}
