<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$outputDir = $root . '/storage/imports/curriculum';
$pdfs = [
    $root . '/ctdt/qldtCnttChuan.pdf',
    $root . '/ctdt/qldtKhmt.pdf',
    $root . '/ctdt/qldtHtttql.pdf',
];

$configured = getenv('PDFTOTEXT_BIN') ?: null;
$candidates = array_filter([
    $configured,
    'C:\\texlive\\2026\\bin\\windows\\pdftotext.exe',
    'pdftotext',
]);
$binary = null;
foreach ($candidates as $candidate) {
    if ($candidate === 'pdftotext' || is_file($candidate)) {
        $binary = $candidate;
        break;
    }
}
if ($binary === null) {
    fwrite(STDERR, "Không tìm thấy pdftotext. Hãy đặt PDFTOTEXT_BIN.\n");
    exit(1);
}
if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    throw new RuntimeException('Không tạo được thư mục output.');
}

foreach ($pdfs as $pdf) {
    if (!is_file($pdf)) {
        throw new RuntimeException('Thiếu PDF: ' . $pdf);
    }
    $output = $outputDir . '/' . pathinfo($pdf, PATHINFO_FILENAME) . '.txt';
    $process = proc_open(
        [$binary, '-layout', '-nopgbrk', $pdf, $output],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $root,
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Không chạy được pdftotext.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        throw new RuntimeException("pdftotext thất bại: {$stderr}{$stdout}");
    }
    echo basename($pdf) . ' -> ' . str_replace($root . '/', '', str_replace('\\', '/', $output)) . PHP_EOL;
}

echo "Các tệp text chỉ là bản nháp trích xuất; cần rà soát trước khi đưa vào seed học phần.\n";

