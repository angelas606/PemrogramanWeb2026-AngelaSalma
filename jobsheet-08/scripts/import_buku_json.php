<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip migrasi hanya dapat dijalankan lewat CLI.\n");
}

$jsonPath = dirname(__DIR__, 2) . '/jobsheet-06/data/buku.json';
$jsonContent = file_get_contents($jsonPath);

if ($jsonContent === false) {
    fwrite(STDERR, "File data buku tidak dapat dibaca: {$jsonPath}\n");
    exit(1);
}

try {
    $daftarBuku = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    fwrite(STDERR, "Format JSON buku tidak valid: {$e->getMessage()}\n");
    exit(1);
}

if (!is_array($daftarBuku) || $daftarBuku === [] || !array_is_list($daftarBuku)) {
    fwrite(STDERR, "File JSON harus berisi daftar buku yang tidak kosong.\n");
    exit(1);
}

foreach ($daftarBuku as $nomor => $buku) {
    if (
        !is_array($buku)
        || !isset($buku['judul'], $buku['pengarang'], $buku['tahun'], $buku['stok'])
        || !is_string($buku['judul'])
        || !is_string($buku['pengarang'])
        || !is_int($buku['tahun'])
        || !is_int($buku['stok'])
        || (isset($buku['kategori']) && !is_string($buku['kategori']))
    ) {
        fwrite(STDERR, "Format data buku pada item ke-" . ($nomor + 1) . " tidak valid.\n");
        exit(1);
    }
}

require dirname(__DIR__) . '/includes/koneksi.php';

$cekBuku = $pdo->prepare(
    "SELECT 1
     FROM buku
     WHERE judul = :judul AND pengarang = :pengarang
     LIMIT 1"
);
$stmt = $pdo->prepare(
    "INSERT INTO buku (judul, pengarang, tahun, stok, kategori)
     VALUES (:judul, :pengarang, :tahun, :stok, :kategori)"
);

$pdo->beginTransaction();
$jumlahDiimpor = 0;
$jumlahDilewati = 0;
try {
    foreach ($daftarBuku as $buku) {
        $cekBuku->execute([
            'judul' => $buku['judul'],
            'pengarang' => $buku['pengarang'],
        ]);

        if ($cekBuku->fetchColumn() !== false) {
            $jumlahDilewati++;
            continue;
        }

        $stmt->execute([
            'judul' => $buku['judul'],
            'pengarang' => $buku['pengarang'],
            'tahun' => $buku['tahun'],
            'stok' => $buku['stok'],
            'kategori' => $buku['kategori'] ?? null,
        ]);
        $jumlahDiimpor++;
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Migrasi gagal dan semua perubahan dibatalkan: {$e->getMessage()}\n");
    exit(1);
}

echo "{$jumlahDiimpor} buku berhasil dimigrasikan; {$jumlahDilewati} buku dilewati karena sudah ada.\n";
