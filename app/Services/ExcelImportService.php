<?php

namespace App\Services;

use ZipArchive;
use SimpleXMLElement;
use Exception;

class ExcelImportService
{
    /**
     * Membaca file Excel (.xlsx) atau CSV (.csv) dan mengembalikan seluruh lembar kerja (sheets).
     *
     * @param string $filePath Path absolut file yang diunggah
     * @param string $extension Ekstensi file ('xlsx' atau 'csv')
     * @return array Daftar lembar kerja, tiap item: ['sheet_name' => ..., 'rows' => [...]]
     * @throws Exception
     */
    public static function parseAllSheets(string $filePath, string $extension): array
    {
        $extension = strtolower($extension);

        if ($extension === 'csv') {
            return [
                [
                    'sheet_name' => pathinfo($filePath, PATHINFO_FILENAME),
                    'rows'       => self::parseCsv($filePath),
                ]
            ];
        } elseif ($extension === 'xlsx') {
            return self::parseXlsxAllSheets($filePath);
        }

        throw new Exception("Format file '{$extension}' tidak didukung. Harap unggah file .xlsx atau .csv.");
    }

    /**
     * Parser CSV yang mendukung pemisah koma (,) dan titik koma (;).
     */
    public static function parseCsv(string $filePath): array
    {
        $rows = [];
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new Exception("File CSV tidak dapat dibaca.");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new Exception("Gagal membuka file CSV.");
        }

        // Deteksi delimiter (koma atau titik koma)
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = ',';
        if ($firstLine !== false && substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        }

        // Tangani BOM UTF-8 jika ada
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $cleaned = array_map(function ($val) {
                return trim((string) $val);
            }, $data);

            if (count(array_filter($cleaned)) > 0) {
                $rows[] = $cleaned;
            }
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Parser XLSX native yang membaca seluruh lembar kerja (multi-sheet) beserta nama sheet-nya.
     */
    public static function parseXlsxAllSheets(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File Excel tidak ditemukan.");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception("Gagal membuka file XLSX. Pastikan file tidak rusak atau terenkripsi.");
        }

        // 1. Ambil tabel shared strings (xl/sharedStrings.xml)
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_NOERROR);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Ambil daftar lembar kerja (sheets) dari xl/workbook.xml & relasinya
        $sheetsInfo = [];
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        if ($workbookXml !== false) {
            $wb = simplexml_load_string($workbookXml, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_NOERROR);
            if ($wb && isset($wb->sheets->sheet)) {
                $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
                $relMap = [];
                if ($relsXml !== false) {
                    $rels = simplexml_load_string($relsXml, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_NOERROR);
                    if ($rels && isset($rels->Relationship)) {
                        foreach ($rels->Relationship as $r) {
                            $relMap[(string) $r['Id']] = (string) $r['Target'];
                        }
                    }
                }

                foreach ($wb->sheets->sheet as $s) {
                    $sheetName = (string) $s['name'];
                    // Ambil relId dengan berbagai format namespace
                    $rId = (string) ($s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id']
                        ?? $s->attributes('r', true)['id']
                        ?? $s['id']
                        ?? '');
                    $sheetTarget = $relMap[$rId] ?? null;

                    if ($sheetTarget) {
                        $possiblePaths = [
                            $sheetTarget,
                            'xl/' . ltrim($sheetTarget, '/'),
                            'xl/worksheets/' . basename($sheetTarget),
                        ];
                        $resolvedPath = null;
                        foreach ($possiblePaths as $p) {
                            if ($zip->locateName($p) !== false) {
                                $resolvedPath = $p;
                                break;
                            }
                        }

                        if ($resolvedPath) {
                            $sheetsInfo[] = [
                                'name' => $sheetName,
                                'path' => $resolvedPath,
                            ];
                        }
                    }
                }
            }
        }

        // Fallback jika xl/workbook.xml tidak terurai: scan file xl/worksheets/*.xml
        if (empty($sheetsInfo)) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (preg_match('#worksheets/sheet.*\.xml$#i', $stat['name'])) {
                    $sheetsInfo[] = [
                        'name' => pathinfo($stat['name'], PATHINFO_FILENAME),
                        'path' => $stat['name'],
                    ];
                }
            }
        }

        $allSheetsResult = [];

        foreach ($sheetsInfo as $info) {
            $sheetContent = $zip->getFromName($info['path']);
            if ($sheetContent === false) {
                continue;
            }

            $sheetXml = simplexml_load_string($sheetContent, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_NOERROR);
            if (!$sheetXml || !isset($sheetXml->sheetData)) {
                continue;
            }

            $rows = [];
            foreach ($sheetXml->sheetData->row as $row) {
                $rowArray = [];
                $maxColIndex = 0;

                foreach ($row->c as $cell) {
                    $cellRef = (string) $cell['r'];
                    $colIndex = self::coordinateToColumnIndex($cellRef);

                    $cellType = (string) $cell['t'];
                    $val = '';

                    if ($cellType === 's') {
                        $stringIndex = (int) $cell->v;
                        $val = $sharedStrings[$stringIndex] ?? '';
                    } elseif ($cellType === 'inlineStr' && isset($cell->is->t)) {
                        $val = (string) $cell->is->t;
                    } elseif (isset($cell->v)) {
                        $val = (string) $cell->v;
                    }

                    $val = trim($val);
                    $rowArray[$colIndex] = $val;
                    if ($colIndex > $maxColIndex) {
                        $maxColIndex = $colIndex;
                    }
                }

                $normalizedRow = [];
                for ($i = 0; $i <= $maxColIndex; $i++) {
                    $normalizedRow[$i] = $rowArray[$i] ?? '';
                }

                if (count(array_filter($normalizedRow)) > 0) {
                    $rows[] = $normalizedRow;
                }
            }

            if (!empty($rows)) {
                $allSheetsResult[] = [
                    'sheet_name' => $info['name'],
                    'rows'       => $rows,
                ];
            }
        }

        $zip->close();

        if (empty($allSheetsResult)) {
            throw new Exception("File Excel tidak memiliki lembar kerja (worksheet) yang berisi data.");
        }

        return $allSheetsResult;
    }

    /**
     * Mengubah koordinat sel Excel (misal "B2", "AA5") menjadi indeks kolom berbasis 0.
     */
    private static function coordinateToColumnIndex(string $cellCoord): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($cellCoord));
        $columnIndex = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $columnIndex = $columnIndex * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $columnIndex - 1);
    }

    /**
     * Menganalisis format lembar kerja:
     * - Apakah format 'DAFTAR_HADIR_SISWA' (seperti presensi kelas: ada KELAS: X RPL 1, NIS, NAMA SISWA)
     * - Atau format 'GENERAL_USERS' (tabel standar: username, nama, role, dll.)
     */
    public static function analyzeSheet(array $rows, string $sheetName = ''): array
    {
        $detectedKelas = null;
        $headerRowIndex = null;
        $colNis = null;
        $colNama = null;
        $colKelas = null;

        // 1. Cari tulisan KELAS di 15 baris pertama (misal: "KELAS : X RPL 1" atau "KELAS: 10 RPL 1")
        for ($r = 0; $r < min(15, count($rows)); $r++) {
            foreach ($rows[$r] as $cell) {
                $cellStr = trim((string)$cell);
                if (preg_match('/kelas\s*[:=\-]?\s*([a-zA-Z0-9\s\-]+)/i', $cellStr, $matches)) {
                    $detectedKelas = trim($matches[1]);
                    // Bersihkan jika ada teks tambahan di belakangnya
                    $detectedKelas = preg_replace('/(tahun|semester|guru|jumlah).*/i', '', $detectedKelas);
                    $detectedKelas = trim($detectedKelas);
                    break 2;
                }
            }
        }

        // Jika tidak ada tulisan "KELAS :" di header teks, periksa apakah nama sheet adalah nama kelas (misal "X RPL 1")
        if (empty($detectedKelas) && !empty($sheetName)) {
            if (preg_match('/(X|XI|XII|10|11|12)\s*[\-_]?\s*[A-Z0-9]+/i', $sheetName)) {
                $detectedKelas = trim($sheetName);
            }
        }

        // 2. Cari header kolom (NIS, NAMA SISWA, KELAS)
        // Catatan: Pada tabel absensi Excel resmi, header sering di-merge secara vertikal
        // (contoh: baris 7 ada "NAMA SISWA", baris 8 ada "NIS" di bawah kolom "NOMOR")
        for ($r = 0; $r < min(25, count($rows)); $r++) {
            foreach ($rows[$r] as $cIdx => $cellText) {
                $clean = strtolower(trim((string)$cellText));

                // Deteksi kolom NIS / NISN
                if (in_array($clean, ['nis', 'nisn', 'no_induk', 'nomor_induk', 'nis/nisn', 'no. induk', 'nis/nisn', 'nisn/nis']) || preg_match('/^nis(\/nisn)?$/i', $clean)) {
                    if ($colNis === null) {
                        $colNis = $cIdx;
                        $headerRowIndex = max($headerRowIndex ?? 0, $r);
                    }
                }
                // Deteksi kolom Nama Siswa
                elseif (
                    in_array($clean, ['nama siswa', 'nama_siswa', 'nama peserta didik', 'nama lengkap', 'siswa']) ||
                    (str_contains($clean, 'nama') && !str_contains($clean, 'guru') && !str_contains($clean, 'wali') && !str_contains($clean, 'mata pelajaran') && !str_contains($clean, 'sekolah'))
                ) {
                    if ($colNama === null) {
                        $colNama = $cIdx;
                        $headerRowIndex = max($headerRowIndex ?? 0, $r);
                    }
                }
                // Deteksi kolom Kelas (hanya jika sebagai header kolom mandiri)
                elseif (in_array($clean, ['kelas', 'nama_kelas', 'rombel']) && $cIdx !== $colNis && $cIdx !== $colNama) {
                    if ($colKelas === null && $r >= ($headerRowIndex ?? 0) - 2) {
                        $colKelas = $cIdx;
                    }
                }
            }

            if ($colNis !== null && $colNama !== null) {
                break;
            }
        }

        // 3. Fallback Heuristik: Jika judul kolom tidak terdeteksi teksnya, periksa pola data
        // (Misal: Baris data memuat urutan: Col A = angka 1..36, Col B = NIS 4-12 digit, Col C = Nama Teks Siswa)
        if ($colNis === null || $colNama === null) {
            for ($r = 0; $r < min(30, count($rows)); $r++) {
                $row = $rows[$r];
                $candidateNis = null;
                $candidateNama = null;

                foreach ($row as $cIdx => $val) {
                    $val = trim((string)$val);
                    // NIS: 4-12 digit angka murni
                    if (preg_match('/^[0-9]{4,12}$/', $val) && $candidateNis === null) {
                        $candidateNis = $cIdx;
                    }
                    // Nama: Huruf & spasi minimal 4 huruf, bukan angka
                    elseif (
                        preg_match('/^[a-zA-Z\s\.\',\-]{4,}$/', $val) &&
                        !preg_match('/^(senin|selasa|rabu|kamis|jumat|sabtu|januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|semester|ganjil|genap|tahun|kelas)$/i', $val) &&
                        $candidateNama === null
                    ) {
                        $candidateNama = $cIdx;
                    }
                }

                if ($candidateNis !== null && $candidateNama !== null && $candidateNis !== $candidateNama) {
                    $colNis = $candidateNis;
                    $colNama = $candidateNama;
                    $headerRowIndex = max(0, $r - 1);
                    break;
                }
            }
        }

        if ($colNis !== null && $colNama !== null) {
            return [
                'format'           => 'DAFTAR_HADIR_SISWA',
                'detected_kelas'   => $detectedKelas,
                'header_row_index' => $headerRowIndex ?? 7,
                'col_nis'          => $colNis,
                'col_nama'         => $colNama,
                'col_kelas'        => $colKelas,
            ];
        }

        // Jika tidak cocok format daftar hadir siswa, gunakan tabel pengguna umum
        return [
            'format'         => 'GENERAL_USERS',
            'detected_kelas' => $detectedKelas,
        ];
    }

    /**
     * Membuat template file CSV untuk format Daftar Hadir Siswa per Kelas
     */
    public static function generateTemplateDaftarHadir(): string
    {
        $lines = [
            ['DAFTAR HADIR SISWA'],
            ['KELAS : X RPL 1'],
            ['TAHUN PELAJARAN 2026 - 2027'],
            [''],
            ['NO', 'NIS', 'NAMA SISWA', 'L/P'],
            ['1', '18910', 'ALLIF MUKHLIS SETYAWAN', 'L'],
            ['2', '18911', 'ALUB MASSYAFANY TABA PRASA', 'L'],
            ['3', '18912', 'ANGGER RAKA PUTRA SADEWA', 'L'],
            ['4', '18913', 'ARIKA MAULANA', 'L'],
            ['5', '18914', 'ARNAWA BERLIANA WICAKSONO', 'P'],
            ['6', '18915', 'AZKA KURNIA CAKRAWANGSA', 'L'],
        ];

        $output = fopen('php://temp', 'r+');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM untuk Excel

        foreach ($lines as $line) {
            fputcsv($output, $line, ',');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Membuat template file CSV standar untuk import semua jenis pengguna (Guru, Wali, BK, Siswa, Waka)
     */
    public static function generateTemplateCsv(): string
    {
        $headers = ['username', 'nama_lengkap', 'password', 'role', 'no_hp'];
        $samples = [
            ['198501012010011001', 'Drs. Ahmad Fauzi, M.Pd.', '123456', 'Guru', '081234567890'],
            ['198703152015022002', 'Siti Rahmawati, S.Pd.', '123456', 'Wali Kelas', '081298765432'],
            ['199008202020031003', 'Rudi Pratama, S.Pd. (BK)', '123456', 'Guru BK', '081377889900'],
            ['197505102000031001', 'Bambang Irawan, M.Kom.', '123456', 'Waka Kurikulum', '081211223344'],
            ['18910', 'ALLIF MUKHLIS SETYAWAN', '123456', 'Siswa', '085711223344'],
            ['18911', 'ALUB MASSYAFANY TABA PRASA', '123456', 'Siswa', '085711223345'],
        ];

        $output = fopen('php://temp', 'r+');
        fputs($output, "\xEF\xBB\xBF");

        fputcsv($output, $headers, ',');
        foreach ($samples as $sample) {
            fputcsv($output, $sample, ',');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}
