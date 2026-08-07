<?php

/** @var array $donation */

$donorName     = htmlspecialchars((string)($donation['donor_name'] ?? ''));
$donorEmail    = htmlspecialchars((string)($donation['donor_email'] ?? ''));
$bloodGroup    = htmlspecialchars((string)($donation['donor_blood_group'] ?? ($donation['blood_group_needed'] ?? '')));
$donationDate  = !empty($donation['donation_date'])
    ? htmlspecialchars(date('d-M-Y', strtotime((string)$donation['donation_date'])))
    : '&mdash;';
$hospital      = htmlspecialchars((string)($donation['hospital_name'] ?? ''));
$requestCode   = htmlspecialchars((string)($donation['request_code'] ?? ''));
$certNumber    = htmlspecialchars((string)($donation['certificate_number'] ?? 'BC-000000'));

$generatedAt = date('d-M-Y h:i A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BloodConnect Donation Certificate</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.5;
        }
        .page {
            padding: 18px;
            background: #ffffff;
        }
        .frame {
            border: 3px solid #dc2626;
            border-radius: 14px;
            padding: 6px;
        }
        .inner {
            border: 1px solid #dc2626;
            border-radius: 10px;
            padding: 16px 34px 14px;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
            margin-bottom: 4px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td.logo-cell {
            width: 70px;
            text-align: right;
            padding: 0 6px;
        }
        .header td.text-cell {
            text-align: left;
        }
        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #dc2626;
            letter-spacing: 0.5px;
        }
        .brand span { color: #0f172a; }
        .subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .title {
            text-align: center;
            margin: 12px 0 2px;
        }
        .title .main {
            font-size: 27px;
            font-weight: bold;
            color: #111625;
            text-transform: uppercase;
            letter-spacing: 4px;
        }
        .title .sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 3px;
        }
        .cert-no {
            text-align: center;
            margin: 9px 0 14px;
        }
        .cert-no .box {
            display: inline-block;
            border: 1px dashed #cbd5e1;
            background: #f8fafc;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 12px;
            color: #475569;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .body {
            text-align: center;
            margin-top: 2px;
        }
        .body .line {
            font-size: 13px;
            color: #475569;
            margin-bottom: 5px;
        }
        .body .name {
            font-size: 28px;
            font-weight: bold;
            color: #111625;
            margin: 8px 0 3px;
            letter-spacing: 1px;
        }
        table.info {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0 4px;
        }
        table.info td {
            padding: 8px 14px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
        }
        table.info td.label {
            width: 45%;
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
        }
        table.info td.value {
            color: #0f172a;
            font-weight: bold;
        }
        .blood {
            color: #dc2626;
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .stamp {
            text-align: center;
            margin-top: 14px;
        }
        .stamp .stamp-box {
            display: inline-block;
            border: 3px solid #dc2626;
            color: #dc2626;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 13px;
            padding: 5px 18px;
            border-radius: 8px;
            opacity: 0.85;
            transform: rotate(-6deg);
        }
        .footer {
            margin-top: 12px;
            border-top: 1px solid #e2e8f0;
            padding-top: 7px;
            text-align: center;
            color: #94a3b8;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="frame">
            <div class="inner">

                <div class="header">
                    <table>
                        <tr>
                            <td class="logo-cell">
                                <?php if ($logoDataUri !== ''): ?>
                                    <img src="<?= $logoDataUri ?>" width="54" height="54" style="vertical-align: middle;">
                                <?php endif; ?>
                            </td>
                            <td class="text-cell">
                                <div class="brand">BloodConnect <span>&bull; Blood Donation Network</span></div>
                                <div class="subtitle">Connecting donors with patients to save lives.</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="title">
                    <div class="main">Certificate of Donation</div>
                    <div class="sub">Acknowledging a voluntary, life-saving blood donation</div>
                </div>

                <div class="cert-no">
                    <span class="box">Certificate No. <?= $certNumber ?></span>
                </div>

                <div class="body">
                    <div class="line">This is to proudly certify that</div>
                    <div class="name"><?= $donorName !== '' ? $donorName : '&mdash;' ?></div>
                    <div class="line">has made a blood donation through the BloodConnect network on the date stated below.</div>
                </div>

                <table class="info">
                    <tr>
                        <td class="label">Blood Group Donated</td>
                        <td class="value"><span class="blood"><?= $bloodGroup !== '' ? $bloodGroup : '&mdash;' ?></span></td>
                    </tr>
                    <tr>
                        <td class="label">Donation Date</td>
                        <td class="value"><?= $donationDate ?></td>
                    </tr>
                    <tr>
                        <td class="label">Donation Hospital</td>
                        <td class="value"><?= $hospital !== '' ? $hospital : '&mdash;' ?></td>
                    </tr>
                    <?php if ($requestCode !== ''): ?>
                    <tr>
                        <td class="label">Request Reference</td>
                        <td class="value"><?= $requestCode ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($donorEmail !== ''): ?>
                    <tr>
                        <td class="label">Donor Email</td>
                        <td class="value"><?= $donorEmail ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <div class="stamp">
                    <span class="stamp-box">Verified by BloodConnect</span>
                </div>

                <div class="footer">
                    Generated by BloodConnect System &bull; Generated on: <?= $generatedAt ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
