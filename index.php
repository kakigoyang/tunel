<?php

// Fungsi untuk memberikan respons 404 jika file tidak ditemukan
function feedback404() {
    header("HTTP/1.0 404 Not Found");
    echo "404 Not Found";
}

// Nama file input berisi daftar folder dan brand
$filename = "list.txt"; // Pastikan file list.txt ada di direktori yang sama

// Cek apakah file list.txt ada, jika tidak ada berikan respons 404
if (!file_exists($filename)) {
    feedback404();
    exit();
}

// Membaca daftar dari file list.txt
$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Array untuk menyimpan URL yang akan dimasukkan ke dalam sitemap
$sitemap_urls = [];

foreach ($lines as $line) {
    // Nama folder tetap sesuai dengan teks dalam file (lowercase untuk URL)
    $folder_string = strtolower(trim($line));
    
    // Ubah elemen {{BRAND}} dengan menghapus tanda '-' dan mengubah menjadi huruf besar
    $brand_string = strtoupper(str_replace('-', ' ', $folder_string));
    
    // Tambahkan elemen {{BRAND_LOWER}} untuk string huruf kecil
    $brand_lower_string = strtolower($folder_string);

    // Buat nama folder berdasarkan target string
    $folder_name = "$folder_string";
    $folderPath = __DIR__ . "/$folder_name";  // Perbaiki penggunaan _DIR_ menjadi __DIR__
    $filePath = $folderPath . "/index.html";

    $amp_link = "https://id-zero.xyz/amp/tunel-10/$folder_string/";

    // Mendapatkan URL untuk folder saat ini
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $dir = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $urlPath = strtolower("$protocol://$host$dir/$folder_name/");
    $sitemap_urls[] = $urlPath;

    // Buat folder jika belum ada
    if (!is_dir($folderPath)) {
        if (!mkdir($folderPath, 0777, true)) {
            error_log("Failed to create directory: $folderPath");
            continue;
        }
    }

    // Menyiapkan konten dari template.php menggunakan output buffer
    ob_start();
    include 'template.php'; // Pastikan template.php ada dan dapat diakses
    $html_content = ob_get_clean();

    // Mengganti placeholder {{BRAND}} dan {{BRAND_LOWER}} di template.php
    $html_content = str_replace('{{BRAND}}', $brand_string, $html_content);
    $html_content = str_replace('{{BRAND_LOWER}}', $brand_lower_string, $html_content);
    $html_content = str_replace('{{AMP_LINK}}', $amp_link, $html_content);
    $html_content = str_replace('{{CANONICAL_URL}}', $urlPath, $html_content);

    // Menulis konten ke dalam file index.html di folder yang dibuat
    if (file_put_contents($filePath, $html_content) === false) {
        error_log("Failed to write file: $filePath");
    } else {
        echo "index.html created successfully in $folder_name.\n";
    }
}

// Membuat file gas.xml berdasarkan daftar URL yang dihasilkan
$sitemapFilePath = __DIR__ . "/gas.xml";  // Perbaiki penggunaan _DIR_ menjadi __DIR__
$sitemapContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$sitemapContent .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($sitemap_urls as $url) {
    $sitemapContent .= "  <url>\n";
    $sitemapContent .= "    <loc>$url</loc>\n";
    $sitemapContent .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
    $sitemapContent .= "    <priority>0.9</priority>\n";
    $sitemapContent .= "  </url>\n";
}

$sitemapContent .= "</urlset>\n";

// Menulis konten sitemap ke gas.xml
if (file_put_contents($sitemapFilePath, $sitemapContent) === false) {
    error_log("Failed to create gas.xml");
}

date_default_timezone_set('Asia/Jakarta');
$currentTime = date('Y-m-d\TH:i:sP');
echo "FILES DONE CREATED, SITEMAP GENERATED!\n";

?>
