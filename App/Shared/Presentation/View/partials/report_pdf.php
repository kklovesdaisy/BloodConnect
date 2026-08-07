<?php
/**
 * Shared PDF report template (A4 portrait).
 * Expects: $reportTitle, $reportSubtitle, $reportColumns, $reportRows,
 *          $reportTotals, $reportMeta, $logoDataUri
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 18mm 14mm 18mm 14mm; }
    * { box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        color: #1e293b;
        font-size: 10px;
        line-height: 1.45;
    }
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid #ce2424;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand img { height: 34px; }
    .brand-text { font-weight: bold; font-size: 15px; color: #0b1325; }
    .brand-text small { display: block; font-weight: normal; font-size: 9px; color: #64748b; letter-spacing: 1px; }
    .report-title { font-size: 16px; font-weight: bold; color: #0b1325; }
    .meta-line { color: #64748b; font-size: 9px; text-align: right; }
    .subtitle { color: #64748b; font-size: 9px; margin-bottom: 10px; }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }
    table.data th {
        background: #ce2424;
        color: #ffffff;
        font-weight: bold;
        text-align: left;
        padding: 7px 8px;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        border: 1px solid #a01a1a;
    }
    table.data td {
        padding: 6px 8px;
        border: 1px solid #e2e8f0;
        vertical-align: top;
    }
    table.data tr:nth-child(even) td { background: #faf6f6; }
    .totals {
        margin-top: 14px;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #ce2424;
        background: #fdf6f6;
        padding: 10px 14px;
    }
    .totals h4 { margin: 0 0 6px 0; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #ce2424; }
    .totals table { margin-top: 0; }
    .totals td { border: none; padding: 2px 8px 2px 0; font-size: 10px; }
    .totals td.v { font-weight: bold; color: #0b1325; width: 60px; }
    .footer {
        margin-top: 18px;
        border-top: 1px solid #e2e8f0;
        padding-top: 8px;
        color: #64748b;
        font-size: 9px;
    }
    .footer table { margin-top: 0; }
    .footer td { border: none; padding: 1px 8px 1px 0; }
    .empty { color: #94a3b8; font-style: italic; text-align: center; padding: 20px; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">
        <?php if (!empty($logoDataUri)): ?>
            <img src="<?= htmlspecialchars($logoDataUri) ?>">
        <?php endif; ?>
        <div class="brand-text">
            BloodConnect
            <small>ADMIN REPORTS</small>
        </div>
    </div>
    <div>
        <div class="report-title"><?= htmlspecialchars($reportTitle) ?></div>
        <div class="meta-line"><?= htmlspecialchars($reportSubtitle !== '' ? $reportSubtitle : 'All records') ?></div>
    </div>
</div>

<?php if (empty($reportRows)): ?>
    <p class="empty">No records match the current filters.</p>
<?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <?php foreach ($reportColumns as $column): ?>
                    <th><?= htmlspecialchars((string)$column) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportRows as $row): ?>
                <tr>
                    <?php foreach ($reportColumns as $i => $column): ?>
                        <td><?= htmlspecialchars((string)(is_array($row) ? ($row[$i] ?? '') : $row)) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (!empty($reportTotals)): ?>
    <div class="totals">
        <h4>Summary</h4>
        <table>
            <?php foreach ($reportTotals as $label => $value): ?>
                <tr>
                    <td><?= htmlspecialchars($label) ?></td>
                    <td class="v"><?= htmlspecialchars((string)$value) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<div class="footer">
    <table>
        <?php foreach ($reportMeta as $label => $value): ?>
            <tr>
                <td><strong><?= htmlspecialchars($label) ?>:</strong></td>
                <td><?= htmlspecialchars((string)$value) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
