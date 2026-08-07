<?php

/** @var array $request */

$patientName  = htmlspecialchars((string)($request['patient_name'] ?? ''));
$bloodGroup   = htmlspecialchars((string)($request['blood_group_needed'] ?? ''));
$hospital     = htmlspecialchars((string)($request['hospital_name'] ?? ''));
$urgency      = htmlspecialchars((string)($request['urgency'] ?? ''));
$status       = htmlspecialchars((string)($request['status_name'] ?? ($request['status'] ?? '')));
$units        = (int)($request['unit'] ?? 1);
$location     = htmlspecialchars(trim(implode(', ', array_filter([
    (string)($request['township'] ?? ''),
    (string)($request['state_region'] ?? ''),
]))));
$requestCode  = htmlspecialchars((string)($request['request_code'] ?? ''));
$createdAt    = htmlspecialchars(date('d-M-Y', strtotime((string)($request['created_at'] ?? 'now'))));
$expiryDate   = !empty($request['expiry_date'])
    ? htmlspecialchars(date('d-M-Y', strtotime((string)$request['expiry_date'])))
    : '&mdash;';

$hasDonor    = !empty($request['donor_name']);
$donorName   = htmlspecialchars((string)($request['donor_name'] ?? ''));
$donorBlood  = htmlspecialchars((string)($request['donor_blood_group'] ?? ''));
$donorPhone  = htmlspecialchars((string)($request['donor_phone'] ?? ''));
$donorEmail  = htmlspecialchars((string)($request['donor_email'] ?? ''));
$donorAddress = htmlspecialchars((string)($request['donor_address'] ?? ''));
$donorLocation = htmlspecialchars(trim(implode(', ', array_filter([
    (string)($request['donor_township'] ?? ''),
    (string)($request['donor_state_region'] ?? ''),
]))));
$donorStatus = $status;

$generatedAt = date('d-M-Y h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blood Request Receipt</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.5;
            padding: 24px;
        }
        .header {
            border-bottom: 3px solid #dc2626;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 20px;
            font-weight: bold;
            color: #dc2626;
            letter-spacing: 0.5px;
        }
        .brand span { color: #1e293b; }
        .subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .receipt-title {
            background: #111625;
            color: #ffffff;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #dc2626;
            text-transform: uppercase;
            margin: 18px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
        }
        table.info td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
        }
        table.info td.label {
            width: 38%;
            background: #f8fafc;
            font-weight: bold;
            color: #475569;
        }
        table.info td.value {
            color: #0f172a;
            font-weight: normal;
        }
        .status-pill {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            background: #fef3c7;
            color: #92400e;
            font-weight: bold;
            border: 1px solid #fcd34d;
        }
        .status-pill.done { background: #d1fae5; color: #065f46; border-color: #6ee7b7; }
        .status-pill.red   { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
        .status-pill.gray  { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .no-donor {
            padding: 10px 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            color: #64748b;
            background: #f8fafc;
        }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <?php if ($logoDataUri !== ''): ?>
                <img src="<?= $logoDataUri ?>" width="46" height="46" style="display: inline-block; vertical-align: middle;">
            <?php endif; ?>
            <div>
                <div class="brand">BloodConnect <span>&bull; Blood Request Receipt</span></div>
                <div class="subtitle">Official record that a blood request has been submitted to the BloodConnect system.</div>
            </div>
        </div>
    </div>

    <div class="receipt-title">Request Information</div>

    <table class="info">
        <tr>
            <td class="label">Request ID</td>
            <td class="value"><?= $requestCode ?></td>
        </tr>
        <tr>
            <td class="label">Patient Name</td>
            <td class="value"><?= $patientName ?></td>
        </tr>
        <tr>
            <td class="label">Blood Group</td>
            <td class="value"><?= $bloodGroup ?></td>
        </tr>
        <tr>
            <td class="label">Hospital</td>
            <td class="value"><?= $hospital ?></td>
        </tr>
        <tr>
            <td class="label">Location</td>
            <td class="value"><?= $location !== '' ? $location : '&mdash;' ?></td>
        </tr>
        <tr>
            <td class="label">Urgency</td>
            <td class="value"><?= $urgency !== '' ? $urgency : '&mdash;' ?></td>
        </tr>
        <tr>
            <td class="label">Units Required</td>
            <td class="value"><?= $units ?> Bag<?= $units > 1 ? 's' : '' ?></td>
        </tr>
        <tr>
            <td class="label">Request Date</td>
            <td class="value"><?= $createdAt ?></td>
        </tr>
        <tr>
            <td class="label">Expiry Date</td>
            <td class="value"><?= $expiryDate ?></td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td class="value"><?= $status ?></td>
        </tr>
    </table>

    <div class="section-title">Assigned Donor</div>

    <?php if ($hasDonor): ?>
        <table class="info">
            <tr>
                <td class="label">Donor Name</td>
                <td class="value"><?= $donorName ?></td>
            </tr>
            <tr>
                <td class="label">Blood Group</td>
                <td class="value"><?= $donorBlood !== '' ? $donorBlood : '&mdash;' ?></td>
            </tr>
            <tr>
                <td class="label">Phone</td>
                <td class="value"><?= $donorPhone !== '' ? $donorPhone : '&mdash;' ?></td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td class="value"><?= $donorEmail !== '' ? $donorEmail : '&mdash;' ?></td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td class="value"><?= $donorAddress !== '' ? $donorAddress : '&mdash;' ?></td>
            </tr>
            <tr>
                <td class="label">Location</td>
                <td class="value"><?= $donorLocation !== '' ? $donorLocation : '&mdash;' ?></td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td class="value"><?= $donorStatus ?></td>
            </tr>
        </table>
    <?php else: ?>
        <div class="no-donor">No donor assigned yet.</div>
    <?php endif; ?>

    <div class="footer">
        Generated by BloodConnect System<br>
        Generated on: <?= $generatedAt ?>
    </div>

</body>
</html>
