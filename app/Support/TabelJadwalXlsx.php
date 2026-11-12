<?php

namespace App\Support;

use ZipArchive;

/**
 * Penulis .xlsx minimal untuk tabel jadwal (jam ke- x hari) dengan sel gabungan dan halaman A4 lanskap.
 * Tidak memerlukan library tambahan (hanya ZipArchive).
 */
class TabelJadwalXlsx
{
    private static function xml(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function huruf(int $i): string
    {
        return chr(ord('A') + $i);
    }

    private static function namaSheet(string $nama, array &$dipakai): string
    {
        $bersih = trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $nama));
        $bersih = mb_substr($bersih !== '' ? $bersih : 'Jadwal', 0, 28);
        $kandidat = $bersih;
        $n = 2;
        while (isset($dipakai[mb_strtolower($kandidat)])) {
            $kandidat = mb_substr($bersih, 0, 25) . ' ' . $n++;
        }
        $dipakai[mb_strtolower($kandidat)] = true;
        return $kandidat;
    }

    /** Isi satu kotak jadwal sebagai teks bertingkat. */
    public static function teksKotak(array $items, string $mode): string
    {
        $bagian = [];
        foreach ($items as $it) {
            $baris = [];
            if ($mode === 'guru') {
                $baris[] = (string) $it['kelas'];
                $baris[] = (string) $it['mapel'];
            } else {
                $baris[] = (string) $it['mapel'];
                if (!empty($it['guru'])) {
                    $baris[] = (string) $it['guru'];
                }
            }
            if (!empty($it['ruangan'])) {
                $baris[] = (string) $it['ruangan'];
            }
            $baris[] = $it['mulai'] . '–' . $it['selesai'];
            $bagian[] = implode("\n", $baris);
        }
        return implode("\n\n", $bagian);
    }

    /** @param array<int, array> $daftar hasil JadwalTabelService::susun() */
    public static function buat(array $daftar, string $dihasilkan): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tjx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        $dipakai = [];
        foreach ($daftar as $i => $m) {
            $n = $i + 1;
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $wb .= '<sheet name="' . self::xml(self::namaSheet($m['judul'], $dipakai)) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', self::sheet($m, $dihasilkan));
        }

        $ct .= '</Types>';
        $wb .= '</sheets></workbook>';
        $rels .= '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';

        $border = '<border><left style="thin"><color rgb="FF9AA5B1"/></left><right style="thin"><color rgb="FF9AA5B1"/></right><top style="thin"><color rgb="FF9AA5B1"/></top><bottom style="thin"><color rgb="FF9AA5B1"/></bottom><diagonal/></border>';
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="7">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="12"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><sz val="9"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF555555"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F3864"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE9EDF4"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F3F3"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $border . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="49" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="49" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            . '</cellXfs></styleSheet>';

        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->close();

        $isi = file_get_contents($tmp);
        @unlink($tmp);
        return $isi;
    }

    private static function sel(int $kol, int $baris, int $gaya, string $teks = ''): string
    {
        $ref = self::huruf($kol) . $baris;
        if ($teks === '') {
            return '<c r="' . $ref . '" s="' . $gaya . '"/>';
        }
        return '<c r="' . $ref . '" s="' . $gaya . '" t="inlineStr"><is><t xml:space="preserve">' . self::xml($teks) . '</t></is></c>';
    }

    private static function sheet(array $m, string $dihasilkan): string
    {
        $hari = $m['hari'];
        $lastCol = self::huruf(count($hari));
        $baris = [];
        $gabung = [];

        $baris[] = ['tinggi' => 24, 'isi' => self::sel(0, 1, 1, 'SMK NEGERI 1 BANTUL')];
        $gabung[] = 'A1:' . $lastCol . '1';
        $sub = $m['judul'] . ' — ' . $m['putaran'] . ($m['mode'] === 'guru' ? ' — ' . $m['jp'] . ' jam pelajaran' : '');
        $baris[] = ['tinggi' => 22, 'isi' => self::sel(0, 2, 2, $sub)];
        $gabung[] = 'A2:' . $lastCol . '2';

        $h = self::sel(0, 3, 3, 'Jam ke');
        foreach ($hari as $c => $nama) {
            $h .= self::sel($c + 1, 3, 3, $nama);
        }
        $baris[] = ['tinggi' => 20, 'isi' => $h];

        for ($b = 1; $b <= $m['baris']; $b++) {
            $r = 3 + $b;
            $isi = self::sel(0, $r, 4, (string) $b);
            foreach ($hari as $c => $nama) {
                $k = $m['grid'][$nama][$b] ?? ['tipe' => 'kosong'];
                if ($k['tipe'] === 'mulai') {
                    $isi .= self::sel($c + 1, $r, 5, self::teksKotak($k['items'], $m['mode']));
                    if ($k['rowspan'] > 1) {
                        $kolom = self::huruf($c + 1);
                        $gabung[] = $kolom . $r . ':' . $kolom . ($r + $k['rowspan'] - 1);
                    }
                } elseif ($k['tipe'] === 'tutup') {
                    $isi .= self::sel($c + 1, $r, 5);
                } elseif ($k['tipe'] === 'libur') {
                    $isi .= self::sel($c + 1, $r, 6);
                } else {
                    $isi .= self::sel($c + 1, $r, 6, '-');
                }
            }
            $baris[] = ['tinggi' => 64, 'isi' => $isi];
        }

        $r = 3 + $m['baris'] + 1;
        $kegiatan = [];
        foreach ($m['kegiatan'] as $hr => $kg) {
            $kegiatan[] = $hr . ' ' . $kg['mulai'] . '–' . $kg['selesai'] . ' ' . $kg['nama'];
        }
        if ($kegiatan) {
            $baris[] = ['tinggi' => 16, 'isi' => self::sel(0, $r, 7, 'Sebelum jam ke-1: ' . implode('; ', $kegiatan))];
            $gabung[] = 'A' . $r . ':' . $lastCol . $r;
            $r++;
        }
        $baris[] = ['tinggi' => 16, 'isi' => self::sel(0, $r, 7, 'Jadwal dihasilkan oleh UKK SCP pada ' . $dihasilkan)];
        $gabung[] = 'A' . $r . ':' . $lastCol . $r;

        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $x .= '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>';
        $x .= '<sheetViews><sheetView workbookViewId="0" showGridLines="0"/></sheetViews>';
        $x .= '<cols><col min="1" max="1" width="8" customWidth="1"/><col min="2" max="' . (count($hari) + 1) . '" width="27" customWidth="1"/></cols><sheetData>';
        foreach ($baris as $i => $br) {
            $x .= '<row r="' . ($i + 1) . '" ht="' . $br['tinggi'] . '" customHeight="1">' . $br['isi'] . '</row>';
        }
        $x .= '</sheetData><mergeCells count="' . count($gabung) . '">';
        foreach ($gabung as $g) {
            $x .= '<mergeCell ref="' . $g . '"/>';
        }
        $x .= '</mergeCells>';
        $x .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $x .= '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="1"/>';
        $x .= '</worksheet>';
        return $x;
    }
}
