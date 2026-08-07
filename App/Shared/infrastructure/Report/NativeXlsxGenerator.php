<?php

namespace App\Shared\Infrastructure\Report;

/**
 * Minimal, dependency-free XLSX (Office Open XML Spreadsheet) generator.
 *
 * Produces a valid .xlsx workbook using only core PHP (zlib + XML), so it
 * works even when the php_zip extension is unavailable. The workbook is
 * fully editable in Excel / LibreOffice (sorting + autofilter enabled).
 */
class NativeXlsxGenerator
{
    public function generate(
        string $title,
        array $columns,
        array $rows,
        array $totals = [],
        array $meta = [],
        string $subtitle = ''
    ): string {
        $sheetXml = $this->buildSheetXml($title, $columns, $rows, $totals, $meta, $subtitle);

        $parts = [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'docProps/core.xml' => $this->corePropsXml($title),
            'xl/workbook.xml' => $this->workbookXml($title),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelsXml(),
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $sheetXml,
        ];

        return $this->zip($parts);
    }

    private function buildSheetXml(string $title, array $columns, array $rows, array $totals, array $meta, string $subtitle): string
    {
        $cellRefs = [];
        $xml = '<row r="1"><c r="A1" t="inlineStr" s="1"><is><t>' . $this->esc($title) . '</t></is></c></row>';

        $sub = $subtitle !== '' ? $subtitle : 'All records';
        $xml .= '<row r="2"><c r="A2" t="inlineStr"><is><t>' . $this->esc($sub) . '</t></is></c></row>';

        // Header row (row 4 after a blank spacer row 3)
        $headerRowNum = 4;
        $xml .= '<row r="3"></row>';
        $xml .= '<row r="' . $headerRowNum . '">';
        foreach ($columns as $i => $label) {
            $ref = $this->colLetter($i) . $headerRowNum;
            $xml .= '<c r="' . $ref . '" t="inlineStr" s="2"><is><t>' . $this->esc((string)$label) . '</t></is></c>';
        }
        $xml .= '</row>';

        // Data rows
        $rowNum = $headerRowNum + 1;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '">';
            foreach ($columns as $i => $label) {
                $value = is_array($row) ? ($row[$i] ?? '') : $row;
                $ref = $this->colLetter($i) . $rowNum;
                $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $this->esc((string)$value) . '</t></is></c>';
            }
            $xml .= '</row>';
            $rowNum++;
        }

        $lastDataRow = $rowNum - 1;

        // Totals block
        if (!empty($totals)) {
            $xml .= '<row r="' . $rowNum . '"></row>';
            $rowNum++;
            foreach ($totals as $label => $value) {
                $xml .= '<row r="' . $rowNum . '"><c r="A' . $rowNum . '" t="inlineStr" s="1"><is><t>'
                    . $this->esc($label) . '</t></is></c><c r="B' . $rowNum . '" t="inlineStr" s="1"><is><t>'
                    . $this->esc((string)$value) . '</t></is></c></row>';
                $rowNum++;
            }
        }

        // Meta footer block
        if (!empty($meta)) {
            $xml .= '<row r="' . $rowNum . '"></row>';
            $rowNum++;
            foreach ($meta as $label => $value) {
                $xml .= '<row r="' . $rowNum . '"><c r="A' . $rowNum . '" t="inlineStr"><is><t>'
                    . $this->esc($label) . ' : </t></is></c><c r="B' . $rowNum . '" t="inlineStr"><is><t>'
                    . $this->esc((string)$value) . '</t></is></c></row>';
                $rowNum++;
            }
        }

        $colWidths = '';
        foreach ($columns as $i => $label) {
            $width = $this->columnWidth($label, $rows, $i);
            $colWidths .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $width . '" customWidth="1"/>';
        }

        $autoFilter = '';
        if (!empty($rows) && !empty($columns)) {
            $lastCol = $this->colLetter(count($columns) - 1);
            $autoFilter = '<autoFilter ref="A' . $headerRowNum . ':' . $lastCol . $lastDataRow . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $colWidths . '</cols>'
            . '<sheetData>' . $xml . '</sheetData>'
            . $autoFilter
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    private function columnWidth(string $header, array $rows, int $colIndex): float
    {
        $maxLen = strlen($header);
        foreach ($rows as $row) {
            $value = is_array($row) ? ($row[$colIndex] ?? '') : $row;
            $len = strlen((string)$value);
            if ($len > $maxLen) {
                $maxLen = $len;
            }
        }
        return (float)max(10, min(50, $maxLen + 3));
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = (int)($index / 26) - 1;
        }
        return $letter;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>';
    }

    private function corePropsXml(string $title): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"'
            . ' xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . $this->esc($title) . '</dc:title>'
            . '<dc:creator>Administrator</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function workbookXml(string $title): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->esc($title) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFCE2424"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /**
     * Build a minimal but valid ZIP archive from [name => content] parts.
     * Entries are deflate-compressed (zlib), with a central directory and
     * end-of-central-directory record so Windows Explorer and Excel both
     * accept the file.
     */
    private function zip(array $files): string
    {
        $offset = 0;
        $central = '';
        $out = '';
        $now = getdate();

        foreach ($files as $name => $content) {
            $name = ltrim($name, '/');
            $nameLen = strlen($name);
            $crc = crc32($content);
            $compressed = gzdeflate($content, 6);
            $compSize = strlen($compressed);
            $uncompSize = strlen($content);

            $dosTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | (int)($now['seconds'] / 2);
            $dosDate = (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 8, $dosTime, $dosDate, $crc, $compSize, $uncompSize, $nameLen, 0);
            $out .= $localHeader . $name . $compressed;

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0x0800,
                8,
                $dosTime,
                $dosDate,
                $crc,
                $compSize,
                $uncompSize,
                $nameLen,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;

            $offset += strlen($localHeader) + $nameLen + $compSize;
        }

        $centralOffset = strlen($out);
        $out .= $central;

        $out .= pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), $centralOffset, 0);

        return $out;
    }
}
