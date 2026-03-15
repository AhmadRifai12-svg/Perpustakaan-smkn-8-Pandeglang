<?php

/**
 * Simple generator that converts headings from presentasi.html into basic PPTX slides.
 * Requires composer install of phpoffice/phppresentation in the tools/ vendor folder.
 * Usage: POST source=presentasi.html or call via the form in presentasi.html
 */

$source = $_POST['source'] ?? 'presentasi.html';
$sourcePath = realpath(__DIR__ . '/../' . $source);
if (!$sourcePath || !file_exists($sourcePath)) {
    echo "Source file not found: " . htmlspecialchars($source);
    exit;
}

$autoload1 = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload1)) {
    echo "PHPPresentation library not found.\n";
    echo "Install dependencies by running in the tools folder:\n";
    echo "composer require phpoffice/phppresentation\n";
    echo "Then retry this action from the browser or command line.";
    exit;
}

require $autoload1;

use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;

$html = file_get_contents($sourcePath);
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();

$headings = $dom->getElementsByTagName('h3');

$ppt = new PhpPresentation();
$first = true;
foreach ($headings as $h) {
    $text = trim($h->textContent);
    if ($first) {
        $slide = $ppt->getActiveSlide();
        $first = false;
    } else {
        $slide = $ppt->createSlide();
    }
    $shape = $slide->createRichTextShape()
        ->setHeight(300)
        ->setWidth(800)
        ->setOffsetX(50)
        ->setOffsetY(50);
    $rt = $shape->createTextRun($text);
    $rt->getFont()->setBold(true)->setSize(28);
}

$tmp = tempnam(sys_get_temp_dir(), 'pptx');
$outFile = $tmp . '.pptx';
$writer = IOFactory::createWriter($ppt, 'PowerPoint2007');
$writer->save($outFile);

header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
header('Content-Disposition: attachment; filename="presentasi-perpustakaan.pptx"');
readfile($outFile);
@unlink($outFile);
exit;
