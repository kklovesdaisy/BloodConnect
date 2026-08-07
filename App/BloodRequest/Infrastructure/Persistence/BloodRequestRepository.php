<?php

namespace App\BloodRequest\Infrastructure\Persistence;

use App\BloodRequest\Domain\Repository\BloodRequestRepositoryInterface;
use App\Donor\Domain\Repository\DonorRepositoryInterface;
use App\Donor\Domain\Service\DonorDonationEligibilityService;
use App\Donor\Domain\Service\DonorEligibilityService;
use App\Shared\Infrastructure\Database\Database;
use App\Shared\Infrastructure\Persistence\MasterDataRepository;
use PDO;
use PDOException;

class BloodRequestRepository implements BloodRequestRepositoryInterface
{
    private PDO $db;
    private MasterDataRepository $masterRepo;
    private DonorRepositoryInterface $donorRepo;
    private DonorDonationEligibilityService $donationEligibilityService;
    private DonorEligibilityService $donorEligibilityService;


    public function __construct(
        MasterDataRepository $masterRepo,
        DonorRepositoryInterface $donorRepo,
        DonorDonationEligibilityService $donationEligibilityService,
        DonorEligibilityService $donorEligibilityService
    ) {
        $this->db = Database::getConnection();
        $this->masterRepo = $masterRepo;
        $this->donorRepo = $donorRepo;
        $this->donationEligibilityService = $donationEligibilityService;
        $this->donorEligibilityService = $donorEligibilityService;
    }


    // ================= FIND REQUESTS BY PATIENT =================

    public function findByPatientId(int $patientId): array
    {
                $sql = "
        SELECT
            br.request_id,
            br.request_code,
            br.patient_id,
            br.patient_name,
            br.blood_group_needed,
            br.unit,
            br.hospital_name,
            br.state_region,
            br.township,
            br.hospital_address,
            br.urgency,
            br.contact_phone,
            br.created_at,
            br.expiry_date,
            md.label AS status

        FROM blood_requests br

        LEFT JOIN master_data md
            ON br.status = md.id

        WHERE br.patient_id = ?

        ORDER BY br.created_at DESC
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $patientId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ================= FIND BY ID =================

    public function findById(int $id)
    {
        $stmt = $this->db->prepare(
            "
            SELECT *
            FROM blood_requests
            WHERE request_id = ?
            "
        );


        $stmt->execute([$id]);


        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByCode(string $requestCode): ?array
    {
        $stmt = $this->db->prepare(
            "
            SELECT *
            FROM blood_requests
            WHERE request_code = ?
            LIMIT 1
            "
        );

        $stmt->execute([$requestCode]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findPatientRequestDetail(int $requestId, int $patientId): array
    {
                $stmt = $this->db->prepare(
            "
            SELECT
                br.request_id,
                br.request_code,
                br.patient_id,
                br.patient_name,
                br.blood_group_needed,
                br.unit,
                br.hospital_name,
                br.state_region,
                br.township,
                br.hospital_address,
                br.urgency,
                br.contact_phone,
                br.created_at,
                br.expiry_date,
                br.donor_id,
                br.status,
                md.label AS status_name,
                donor.user_id AS donor_user_id,
                COALESCE(donor.username, CONCAT('Donor #', NULLIF(br.donor_id, 0))) AS donor_name,
                donor.email AS donor_email,
                donor.phone AS donor_phone,
                donor.blood_group AS donor_blood_group,
                donor.address AS donor_address,
                dnr.state_region AS donor_state_region,
                dnr.township AS donor_township
            FROM blood_requests br
            LEFT JOIN master_data md ON md.id = br.status
            LEFT JOIN users donor ON donor.user_id = br.donor_id
            LEFT JOIN donors dnr ON dnr.user_id = br.donor_id
            WHERE br.request_id = ?
              AND br.patient_id = ?
            LIMIT 1
            "
        );

        $stmt->execute([$requestId, $patientId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }







    public function findAcceptedRequestsForDonor(int $donorId, int $acceptedStatus): array
    {
        $stmt = $this->db->prepare(
            "
            SELECT
                br.request_id,
                br.request_code,
                br.patient_id,
                br.patient_name,
                br.blood_group_needed,
                br.unit,
                br.hospital_name,
                br.urgency,
                br.contact_phone,
                br.created_at,
                br.expiry_date,
                br.donor_id,
                br.status,
                md.label AS status_name,
                patient.user_id AS patient_user_id,
                patient.username AS patient_username,
                patient.email AS patient_email,
                patient.phone AS patient_phone,
                patient.address AS patient_address
            FROM blood_requests br
            LEFT JOIN master_data md ON md.id = br.status
            LEFT JOIN users patient ON patient.user_id = br.patient_id
            WHERE br.donor_id = ?
              AND br.status = ?
            ORDER BY br.created_at DESC
            "
        );

        $stmt->execute([$donorId, $acceptedStatus]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findDonorRequestDetail(int $requestId, int $donorId, int $acceptedStatus): array
    {
                $stmt = $this->db->prepare(
            "
            SELECT
                br.request_id,
                br.request_code,
                br.patient_id,
                br.patient_name,
                br.blood_group_needed,
                br.unit,
                br.hospital_name,
                br.state_region,
                br.township,
                br.hospital_address,
                br.urgency,
                br.contact_phone,
                br.created_at,
                br.expiry_date,
                br.donor_id,
                br.status,
                md.label AS status_name,
                patient.user_id AS patient_user_id,
                patient.username AS patient_username,
                patient.email AS patient_email,
                patient.phone AS patient_phone,
                patient.address AS patient_address
            FROM blood_requests br
            LEFT JOIN master_data md ON md.id = br.status
            LEFT JOIN users patient ON patient.user_id = br.patient_id
            WHERE br.request_id = ?
              AND br.donor_id = ?
              AND br.status = ?
            LIMIT 1
            "
        );

        $stmt->execute([$requestId, $donorId, $acceptedStatus]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // ================= CREATE BLOOD REQUEST =================

    public function create(array $data): bool
    {
                $sql = "
            INSERT INTO blood_requests
            (
                request_code,
                patient_id,
                patient_name,
                blood_group_needed,
                unit,
                hospital_name,
                state_region,
                township,
                hospital_address,
                urgency,
                contact_phone,
                status,
                expiry_date
            )

            VALUES
            (
                :request_code,
                :patient_id,
                :patient_name,
                :blood_group_needed,
                :unit,
                :hospital_name,
                :state_region,
                :township,
                :hospital_address,
                :urgency,
                :contact_phone,
                :status,
                :expiry_date
            )
        ";

        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute([
                ':request_code' => $data['request_code'],
                ':patient_id' => $data['patient_id'],
                ':patient_name' => $data['patient_name'],
                ':blood_group_needed' => $data['blood_group_needed'],
                ':unit' => $data['unit'],
                ':hospital_name' => $data['hospital_name'],
                ':state_region' => $data['state_region'] ?? null,
                ':township' => $data['township'] ?? null,
                ':hospital_address' => $data['hospital_address'] ?? null,
                ':urgency' => $data['urgency'],
                ':contact_phone' => $data['contact_phone'],
                ':status' => $data['status'],
                ':expiry_date' => $data['expiry_date'] ?? null
            ]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'a foreign key constraint fails')) {
                throw new \RuntimeException('Your account could not be verified. Please log out and log in again.');
            }
            throw $e;
        }
    }







    // ================= FIND ALL REQUESTS =================

    public function findAll(): array
    {

        $sql = "
            SELECT
                br.*,
                md.label AS status_name

            FROM blood_requests br

            LEFT JOIN master_data md

                ON br.status = md.id

            ORDER BY br.created_at DESC
        ";


        $stmt = $this->db->prepare($sql);

        $stmt->execute();


        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }








    // ================= EXPIRE OVERDUE REQUESTS =================

    public function findOverdueRequests(): array
    {
        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

        $stmt = $this->db->prepare("
            SELECT
                br.request_id,
                br.request_code,
                br.patient_id,
                br.patient_name,
                br.blood_group_needed,
                br.hospital_name,
                br.urgency,
                br.expiry_date,
                u.email AS patient_email
            FROM blood_requests br
            JOIN users u ON u.user_id = br.patient_id
            WHERE br.status = ?
              AND br.expiry_date IS NOT NULL
              AND br.expiry_date < NOW()
        ");
        $stmt->execute([$pendingStatus]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function expireOverdueRequests(): int
    {
        $expiredStatus = $this->masterRepo->getId('REQUEST_STATUS', 'EXPIRED') ?? 48;
        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

        $stmt = $this->db->prepare("
            UPDATE blood_requests
            SET status = ?
            WHERE status = ?
              AND expiry_date IS NOT NULL
              AND expiry_date < NOW()
        ");
        $stmt->execute([$expiredStatus, $pendingStatus]);
        return $stmt->rowCount();
    }

    // ================= FIND DONORS BY BLOOD GROUP AND LOCATION =================

    public function findDonorsByBloodGroupAndLocation(string $bloodGroup, ?string $township = null, ?string $stateRegion = null): array
    {
        if ($bloodGroup === '') {
            return [];
        }

                $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;
        $donationEligibilityService = $this->donationEligibilityService;
        $donorEligibilityService = $this->donorEligibilityService;

        $sql = "
            SELECT
                u.user_id,
                u.username,
                u.email,
                u.phone,
                u.blood_group,
                u.address,
                u.available,
                u.is_active,
                u.next_available_date,
                d.date_of_birth,
                d.weight,
                d.state_region,
                d.township,
                latest_request.created_at AS last_donation_date
            FROM users u
            JOIN donors d ON d.user_id = u.user_id
            LEFT JOIN (
                SELECT donor_id, MAX(created_at) AS created_at
                FROM blood_requests
                WHERE donor_id IS NOT NULL
                  AND status = ?
                GROUP BY donor_id
            ) latest_request ON latest_request.donor_id = u.user_id
            WHERE u.user_type_id = 2
              AND u.is_active = 1
              AND u.blood_group = ?
              AND d.date_of_birth IS NOT NULL
              AND d.weight IS NOT NULL
              AND NOT EXISTS (
                  SELECT 1
                  FROM request_donors rd
                  JOIN blood_requests br ON br.request_id = rd.request_id
                  WHERE rd.donor_id = u.user_id
                    AND br.status NOT IN (?, ?, ?)
                    AND rd.response_status_id IN (?, ?)
              )
        ";

        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;
        $cancelledStatus = $this->masterRepo->getId('REQUEST_STATUS', 'CANCELLED') ?? 10;
        $declinedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;
        $pendingResponse = $this->masterRepo->getId('RESPONSE_STATUS', 'PENDING') ?? 11;
        $acceptedResponse = $this->masterRepo->getId('RESPONSE_STATUS', 'ACCEPTED') ?? 12;
        $params = [$acceptedStatus, $bloodGroup, $completedStatus, $cancelledStatus, $declinedStatus, $pendingResponse, $acceptedResponse];

        if ($township !== null && $township !== '') {
            $sql .= " AND d.township = ?";
            $params[] = $township;
        } elseif ($stateRegion !== null && $stateRegion !== '') {
            $sql .= " AND d.state_region = ?";
            $params[] = $stateRegion;
        }

        $sql .= " ORDER BY u.username ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_filter($donors, function (array $donor) use ($donationEligibilityService, $donorEligibilityService): bool {
            $donationEligibility = $donationEligibilityService->evaluate(
                (string)($donor['last_donation_date'] ?? ''),
                (string)($donor['next_available_date'] ?? '')
            );
            if (!$donationEligibility['is_available']) {
                return false;
            }

            $profileEligibility = $donorEligibilityService->evaluate(
                (string)($donor['date_of_birth'] ?? ''),
                (string)($donor['weight'] ?? '')
            );
            if (!$profileEligibility['eligible']) {
                return false;
            }

            return true;
        }));
    }

    // ================= BEST DONOR BY LOCATION (priority hierarchy) =================
    //
    // Returns the highest-priority available donor for a request following the
    // location priority: same township -> same region -> any region.
    // Donors already assigned to another active request are excluded.
    public function findBestDonorByLocation(string $bloodGroup, ?string $township, ?string $stateRegion): ?array
    {
        if ($bloodGroup === '') {
            return null;
        }

        $townshipMatches = [];
        $regionMatches = [];
        $anyMatches = [];

        if ($township !== null && $township !== '') {
            $townshipMatches = $this->findDonorsByBloodGroupAndLocation($bloodGroup, $township, null);
        }

        if (empty($townshipMatches) && $stateRegion !== null && $stateRegion !== '') {
            $regionMatches = $this->findDonorsByBloodGroupAndLocation($bloodGroup, null, $stateRegion);
        }

        if (empty($townshipMatches) && empty($regionMatches)) {
            $anyMatches = $this->findDonorsByBloodGroupAndLocation($bloodGroup, null, null);
        }

        if (!empty($townshipMatches)) {
            return ['donor' => $townshipMatches[0], 'tier' => 'township'];
        }
        if (!empty($regionMatches)) {
            return ['donor' => $regionMatches[0], 'tier' => 'region'];
        }
        if (!empty($anyMatches)) {
            return ['donor' => $anyMatches[0], 'tier' => 'all'];
        }

        return null;
    }

    // ================= ASSIGN DONORS TO REQUEST =================

    public function assignDonorsToRequest(int $requestId, array $donorIds, int $statusId): bool
    {
        if (empty($donorIds)) {
            return false;
        }

        $existing = $this->getDonorResponseStatuses($requestId, $donorIds);
        $toInsert = [];
        foreach ($donorIds as $donorId) {
            $did = (int)$donorId;
            if (!isset($existing[$did])) {
                $toInsert[] = $did;
            }
        }

        if (empty($toInsert)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $pendingStatus = $this->masterRepo->getId('RESPONSE_STATUS', 'PENDING') ?? 11;

            $stmt = $this->db->prepare(
                "INSERT INTO request_donors (request_id, donor_id, response_status_id) VALUES (?, ?, ?)"
            );

            foreach ($toInsert as $donorId) {
                $stmt->execute([$requestId, $donorId, $pendingStatus]);
            }

            // Set the first donor as the primary assigned donor on the request
            // and mark the request as ASSIGNED (awaiting donor acceptance).
            $firstDonorId = (int)$toInsert[0];
            $updateStmt = $this->db->prepare(
                "UPDATE blood_requests SET donor_id = ?, status = ? WHERE request_id = ?"
            );
            $updateStmt->execute([$firstDonorId, $statusId, $requestId]);

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log("Failed assigning donors to request {$requestId}: " . $e->getMessage());
            return false;
        }
    }

    // ================= GET ASSIGNED DONORS =================

    public function getAssignedDonors(int $requestId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                rd.request_donor_id,
                rd.donor_id,
                rd.response_status_id,
                rd.response_date,
                u.username,
                u.email,
                u.phone,
                u.blood_group,
                d.state_region,
                d.township
            FROM request_donors rd
            JOIN users u ON u.user_id = rd.donor_id
            LEFT JOIN donors d ON d.user_id = rd.donor_id
            WHERE rd.request_id = ?
            ORDER BY rd.response_date ASC
        ");
        $stmt->execute([$requestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDonorResponseStatuses(int $requestId, array $donorIds): array
    {
        if (empty($donorIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($donorIds), '?'));
        $params = array_merge([$requestId], $donorIds);
        $stmt = $this->db->prepare("
            SELECT donor_id, response_status_id
            FROM request_donors
            WHERE request_id = ?
              AND donor_id IN ({$placeholders})
        ");
        $stmt->execute($params);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int)$row['donor_id']] = (int)$row['response_status_id'];
        }
        return $result;
    }

    // ================= CHECK DONORS ALREADY ASSIGNED ELSEWHERE =================

    public function getDonorsAssignedToOtherRequests(array $donorIds, int $excludeRequestId): array
    {
        if (empty($donorIds)) {
            return [];
        }

        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;
        $cancelledStatus = $this->masterRepo->getId('REQUEST_STATUS', 'CANCELLED') ?? 10;
        $declinedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;
        $declinedResponse = $this->masterRepo->getId('RESPONSE_STATUS', 'DECLINED') ?? 13;

        $placeholders = implode(',', array_fill(0, count($donorIds), '?'));
        $sql = "
            SELECT
                rd.donor_id,
                br.request_id,
                br.request_code,
                br.patient_id,
                br.patient_name,
                br.urgency,
                br.blood_group_needed,
                br.township,
                br.state_region
            FROM request_donors rd
            JOIN blood_requests br ON br.request_id = rd.request_id
            WHERE rd.donor_id IN ({$placeholders})
              AND br.request_id != ?
              AND br.status NOT IN (?, ?, ?)
              AND rd.response_status_id != ?
            ORDER BY
                CASE UPPER(br.urgency)
                    WHEN 'CRITICAL' THEN 0
                    WHEN 'URGENT' THEN 1
                    ELSE 2
                END
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($donorIds, [$excludeRequestId, $completedStatus, $cancelledStatus, $declinedStatus, $declinedResponse]));
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $did = (int)$row['donor_id'];
            $grouped[$did][] = $row;
        }
        return $grouped;
    }

    // ================= FIND DONOR MATCHING REQUESTS =================

    public function findPendingRequestsForDonor(
        string $bloodGroup,
        int $donorId = 0
    ): array {

        if ($bloodGroup === '') {
            return [];
        }

        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

        $sql = "
            SELECT
                br.request_id,
                br.request_code,
                br.patient_name,
                br.blood_group_needed,
                br.unit,
                br.hospital_name,
                br.urgency,
                br.contact_phone,
                br.created_at,
                br.expiry_date,
                br.status,
                md.label AS status_name";

        if ($donorId > 0) {
            $sql .= ",
                rd.response_status_id AS donor_response_status";
        }

        $sql .= "
            FROM blood_requests br
            LEFT JOIN master_data md ON br.status = md.id";

        if ($donorId > 0) {
            $sql .= "
            LEFT JOIN request_donors rd ON rd.request_id = br.request_id AND rd.donor_id = :donor_id";
        }

        $sql .= "
            WHERE br.blood_group_needed = :blood_group
              AND br.status = :status
              AND (br.expiry_date IS NULL OR br.expiry_date > NOW())
            ORDER BY br.created_at DESC";

        $params = [
            ':blood_group' => $bloodGroup,
            ':status' => $pendingStatus,
        ];

        if ($donorId > 0) {
            $params[':donor_id'] = $donorId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findCompetingRequests(
        string $bloodGroup,
        string $stateRegion,
        int $excludeRequestId
    ): array {
        if ($bloodGroup === '' || $stateRegion === '') {
            return [];
        }

        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

        $sql = "
            SELECT
                br.request_id,
                br.request_code,
                br.patient_name,
                br.blood_group_needed,
                br.unit,
                br.hospital_name,
                br.urgency,
                br.township,
                br.state_region,
                br.donor_id,
                br.created_at
            FROM blood_requests br
            WHERE br.blood_group_needed = :blood_group
              AND br.state_region = :state_region
              AND br.request_id != :exclude_id
              AND br.status = :status
            ORDER BY br.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':blood_group' => $bloodGroup,
            ':state_region' => $stateRegion,
            ':exclude_id' => $excludeRequestId,
            ':status' => $pendingStatus,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMatchingDonors(string $bloodGroup, ?string $township = null, ?string $stateRegion = null): array
    {
        if ($bloodGroup === '') {
            return [];
        }

        $donationEligibilityService = $this->donationEligibilityService;
        $donorEligibilityService = $this->donorEligibilityService;

        // Location-aware matching follows the priority hierarchy:
        // same township -> same region -> any region. When a location is
        // supplied we only surface donors from that region (falling back to
        // all regions only when none exist in the same region), so a patient
        // in a different township but the same region still matches a donor.
        $townshipMatches = [];
        $regionMatches = [];
        $anyMatches = [];

        if ($township !== null && $township !== '') {
            $townshipMatches = $this->findDonorsByBloodGroupAndLocation($bloodGroup, $township, null);
        }

        if (empty($townshipMatches) && $stateRegion !== null && $stateRegion !== '') {
            $regionMatches = $this->findDonorsByBloodGroupAndLocation($bloodGroup, null, $stateRegion);
        }

        if (empty($townshipMatches) && empty($regionMatches)) {
            $anyMatches = $this->findDonorsByBloodGroupAndLocation($bloodGroup, null, null);
        }

        $ordered = array_merge($townshipMatches, $regionMatches, $anyMatches);

        if (empty($ordered)) {
            return [];
        }

        return $ordered;
    }

    public function acceptByAdmin(int $requestId, int $donorId, int $statusId): bool    {
        $stmt = $this->db->prepare(
            "
            UPDATE blood_requests
            SET status = ?, donor_id = ?
            WHERE request_id = ?
            "
        );

        return $stmt->execute([$statusId, $donorId, $requestId]);
    }

    public function completeRequest(int $requestId, int $completedStatus): bool
    {
        $stmt = $this->db->prepare(
            "
            UPDATE blood_requests
            SET status = ?
            WHERE request_id = ?
            "
        );

        return $stmt->execute([$completedStatus, $requestId]);
    }

    public function getPatientStats(int $patientId): array
    {
        $cancelledStatus = $this->masterRepo->getId('REQUEST_STATUS', 'CANCELLED') ?? 10;

        // Total requests
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM blood_requests
        WHERE patient_id = ?
    ");
        $stmt->execute([$patientId]);
        $total = (int)$stmt->fetchColumn();

        // Pending requests (includes assigned and declined — patient sees all as Pending)
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM blood_requests
        WHERE patient_id = ?
        AND status IN (7, 42, 47)
    ");
        $stmt->execute([$patientId]);
        $pending = (int)$stmt->fetchColumn();

        // Accepted requests
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM blood_requests
        WHERE patient_id = ?
        AND status = 8
    ");
        $stmt->execute([$patientId]);
        $accepted = (int)$stmt->fetchColumn();

        // Cancelled requests
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM blood_requests
        WHERE patient_id = ?
        AND status = ?
    ");
        $stmt->execute([$patientId, $cancelledStatus]);
        $cancelled = (int)$stmt->fetchColumn();

        return [
            'total_requests'     => $total,
            'pending_requests'   => $pending,
            'accepted_requests'  => $accepted,
            'cancelled_requests' => $cancelled
        ];
    }








    public function hasPendingRequest(int $patientId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM blood_requests
            WHERE patient_id = ?
              AND status = 7
              AND (expiry_date IS NULL OR expiry_date > NOW())
        ");
        $stmt->execute([$patientId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ================= UPDATE DONOR ACCEPT / DECLINE =================

    public function updateDonorDecision(

        int $requestId,

        int $donorId,

        int $statusId

    ): bool {


        $sql = "

            UPDATE blood_requests

            SET

                status = :status,

                donor_id = :donor_id


            WHERE request_id = :request_id

        ";


        $stmt = $this->db->prepare($sql);



        return $stmt->execute([

            ':status' => $statusId,

            ':donor_id' => $donorId,

            ':request_id' => $requestId

        ]);
    }

    public function updateDonorResponse(int $requestId, int $donorId, int $responseStatusId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE request_donors
            SET response_status_id = ?, response_date = NOW()
            WHERE request_id = ? AND donor_id = ?
        ");
        $stmt->execute([$responseStatusId, $requestId, $donorId]);

        if ($stmt->rowCount() > 0) {
            return true;
        }

        $stmt = $this->db->prepare("
            INSERT INTO request_donors (request_id, donor_id, response_status_id, response_date)
            VALUES (?, ?, ?, NOW())
        ");

        return $stmt->execute([$requestId, $donorId, $responseStatusId]);
    }

    public function cancelRequest(int $requestId, int $patientId, int $cancelledStatus, int $assignedStatus = 42): bool
    {
        $stmt = $this->db->prepare("
            UPDATE blood_requests
            SET status = ?
            WHERE request_id = ?
              AND patient_id = ?
              AND status IN (7, ?)
        ");
        $stmt->execute([$cancelledStatus, $requestId, $patientId, $assignedStatus]);
        return $stmt->rowCount() > 0;
    }

    public function deleteRequest(int $requestId): bool
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("DELETE FROM donation_history WHERE request_id = ?");
            $stmt->execute([$requestId]);

            $stmt = $this->db->prepare("DELETE FROM request_donors WHERE request_id = ?");
            $stmt->execute([$requestId]);

            $stmt = $this->db->prepare("DELETE FROM blood_requests WHERE request_id = ?");
            $stmt->execute([$requestId]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Failed deleting blood request {$requestId}: " . $e->getMessage());
            return false;
        }
    }

    public function countAcceptedByDonors(): int
    {
        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM blood_requests
            WHERE donor_id IS NOT NULL
              AND status = ?
        ");

        $stmt->execute([$acceptedStatus]);

        return (int)$stmt->fetchColumn();
    }

    public function unassignDonorFromRequest(int $requestId, int $donorId): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->prepare("DELETE FROM request_donors WHERE request_id = ? AND donor_id = ?")
                ->execute([$requestId, $donorId]);

            $this->db->prepare("UPDATE blood_requests SET donor_id = NULL WHERE request_id = ? AND donor_id = ?")
                ->execute([$requestId, $donorId]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Failed unassigning donor {$donorId} from request {$requestId}: " . $e->getMessage());
            return false;
        }
    }

    public function findAssignedRequestsForDonor(int $donorId, int $assignedStatus): array
    {
        $stmt = $this->db->prepare(
            "
            SELECT
                br.request_id,
                br.request_code,
                br.patient_id,
                br.patient_name,
                br.blood_group_needed,
                br.unit,
                br.hospital_name,
                br.urgency,
                br.contact_phone,
                br.created_at,
                br.expiry_date,
                br.donor_id,
                br.status,
                md.label AS status_name,
                rd.response_status_id AS donor_response_status,
                patient.user_id AS patient_user_id,
                patient.username AS patient_username,
                patient.email AS patient_email,
                patient.phone AS patient_phone,
                patient.address AS patient_address
            FROM blood_requests br
            LEFT JOIN master_data md ON md.id = br.status
            LEFT JOIN users patient ON patient.user_id = br.patient_id
            LEFT JOIN request_donors rd ON rd.request_id = br.request_id AND rd.donor_id = ?
            WHERE br.donor_id = ?
              AND br.status = ?
            ORDER BY br.created_at DESC
            "
        );

        $stmt->execute([$donorId, $donorId, $assignedStatus]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countRequestsGroupedByDate(int $days): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;
        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;
        $cancelledStatus = $this->masterRepo->getId('REQUEST_STATUS', 'CANCELLED') ?? 10;
        $declinedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;

        $stmt = $this->db->prepare("
            SELECT
                DATE(br.created_at) AS request_date,
                br.status,
                COUNT(*) AS total
            FROM blood_requests br
            WHERE br.created_at >= ?
            GROUP BY DATE(br.created_at), br.status
            ORDER BY request_date ASC
        ");

        $stmt->execute([$startDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($rows as $row) {
            $date = $row['request_date'];
            $statusId = (int) $row['status'];
            $count = (int) $row['total'];

            if (!isset($grouped[$date])) {
                $grouped[$date] = ['date' => $date, 'pending' => 0, 'accepted' => 0, 'cancelled' => 0, 'declined' => 0];
            }

            if ($statusId === $pendingStatus) {
                $grouped[$date]['pending'] = $count;
            } elseif ($statusId === $acceptedStatus || $statusId === $completedStatus) {
                $grouped[$date]['accepted'] += $count;
            } elseif ($statusId === $cancelledStatus) {
                $grouped[$date]['cancelled'] = $count;
            } elseif ($statusId === $declinedStatus) {
                $grouped[$date]['declined'] = $count;
            }
        }

        return array_values($grouped);
    }

    public function findLatest(int $limit): array
    {
        $stmt = $this->db->prepare("
            SELECT
                br.blood_group_needed,
                br.hospital_name,
                br.created_at
            FROM blood_requests br
            ORDER BY br.created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$limit]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resetRequestToDeclined(int $requestId): bool
    {
        $declinedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;
        $acceptedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'ACCEPTED') ?? 8;
        $completedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'COMPLETED') ?? 9;
        $cancelledStatus = $this->masterRepo->getId('REQUEST_STATUS', 'CANCELLED') ?? 10;

        $stmt = $this->db->prepare("
            UPDATE blood_requests
            SET status = ?, donor_id = NULL
            WHERE request_id = ? AND status NOT IN (?, ?, ?, ?)
        ");
        $stmt->execute([$declinedStatus, $requestId, $acceptedStatus, $completedStatus, $cancelledStatus, $declinedStatus]);
        return $stmt->rowCount() > 0;
    }

    public function resetRequestToPending(int $requestId, int $patientId): bool
    {
        $declinedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;
        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

        $stmt = $this->db->prepare("
            UPDATE blood_requests
            SET status = ?, donor_id = NULL, expiry_date = DATE_ADD(NOW(), INTERVAL 2 DAY)
            WHERE request_id = ? AND patient_id = ? AND status = ?
        ");
        $stmt->execute([$pendingStatus, $requestId, $patientId, $declinedStatus]);
        return $stmt->rowCount() > 0;
    }

    public function resetRequestToPendingByAdmin(int $requestId): bool
    {
        $declinedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;
        $pendingStatus = $this->masterRepo->getId('REQUEST_STATUS', 'PENDING') ?? 7;

        $stmt = $this->db->prepare("
            UPDATE blood_requests
            SET status = ?, donor_id = NULL, expiry_date = DATE_ADD(NOW(), INTERVAL 2 DAY)
            WHERE request_id = ? AND status = ?
        ");
        $stmt->execute([$pendingStatus, $requestId, $declinedStatus]);
        return $stmt->rowCount() > 0;
    }

    public function findDeclinedRequestsForDonor(int $donorId): array
    {
        $declinedStatus = $this->masterRepo->getId('REQUEST_STATUS', 'DECLINED') ?? 47;
        $declinedResponse = $this->masterRepo->getId('RESPONSE_STATUS', 'DECLINED') ?? 13;

        $stmt = $this->db->prepare("
            SELECT DISTINCT
                br.request_id,
                br.request_code,
                br.patient_id,
                br.patient_name,
                br.blood_group_needed,
                br.unit,
                br.hospital_name,
                br.urgency,
                br.contact_phone,
                br.created_at,
                br.expiry_date,
                br.donor_id,
                br.status,
                md.label AS status_name,
                rd.response_status_id AS donor_response_status,
                patient.user_id AS patient_user_id,
                patient.username AS patient_username,
                patient.email AS patient_email,
                patient.phone AS patient_phone,
                patient.address AS patient_address
            FROM blood_requests br
            LEFT JOIN master_data md ON md.id = br.status
            LEFT JOIN users patient ON patient.user_id = br.patient_id
            LEFT JOIN request_donors rd ON rd.request_id = br.request_id AND rd.donor_id = ?
            WHERE br.status = ?
              AND rd.response_status_id = ?
            ORDER BY br.created_at DESC
        ");

        $stmt->execute([$donorId, $declinedStatus, $declinedResponse]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

