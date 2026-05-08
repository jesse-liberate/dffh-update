<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A4_embedded certificate type
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$font_folder = realpath(__DIR__ . '/../../fonts');
$tcpdf_font_folder = realpath($font_folder . '/tcpdf');

$pdf = new PDF($certificate->orientation, 'mm', 'A4', true, 'UTF-8', false);

$pdf->SetTitle($certificate->name);
$pdf->SetProtection(array('modify'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
// $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, 110);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

//remember to change fonts folder permition \lib\tcpdf\fonts
// $futurabook = TCPDF_FONTS::addTTFfont('/var/www/html/live/aquila/lms/lib/tcpdf/fonts/futurabook.ttf', 'TrueTypeUnicode', '', 32);

// $futurabook = TCPDF_FONTS::addTTFfont('F:\Xampp\htdocs\aquila\lib\tcpdf\fonts\futurabook.ttf', 'TrueTypeUnicode', '', 32);

$title_font = TCPDF_FONTS::addTTFfont(
    realpath($font_folder . '/proximanova-extrabld.ttf'),
    '',
    '',
    32,
    $tcpdf_font_folder . '/'
);
$heading_font = TCPDF_FONTS::addTTFfont(
    realpath($font_folder . '/proximanova-bold.ttf'),
    '',
    '',
    32,
    $tcpdf_font_folder . '/'
);
$text_font = TCPDF_FONTS::addTTFfont(
    realpath($font_folder . '/proximanova-regular.ttf'),
    '',
    '',
    32,
    $tcpdf_font_folder . '/'
);

// Define variables
// Landscape
if ($certificate->orientation == 'L') {
    $x = 0;
    $y = 30;
    $sealx = 230;
    $sealy = 150;
    $sigx = 47;
    $sigy = 155;
    $custx = 47;
    $custy = 155;
    $wmarkx = 40;
    $wmarky = 31;
    $wmarkw = 212;
    $wmarkh = 148;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 297;
    $brdrh = 210;
    $codey = 175;
} else { // Portrait
    $x = 10;
    $y = 40;
    $sealx = 150;
    $sealy = 220;
    $sigx = 30;
    $sigy = 230;
    $custx = 30;
    $custy = 230;
    $wmarkx = 26;
    $wmarky = 58;
    $wmarkw = 158;
    $wmarkh = 170;
    $brdrx = 0;
    $brdry = 0;
    $brdrw = 210;
    $brdrh = 297;
    $codey = 250;
}

// Get font families.
$fontsans = get_config('certificate', 'fontsans');
$fontserif = get_config('certificate', 'fontserif');

// Add background image
$pdf->Image(__DIR__ . '/../../pix/certificate-bg.png', $brdrx, $brdry, $brdrw, $brdrh);

// Add images and lines
certificate_print_image($pdf, $certificate, CERT_IMAGE_BORDER, $brdrx, $brdry, $brdrw, $brdrh);
certificate_draw_frame($pdf, $certificate);
// Set alpha to semi-transparency
$pdf->SetAlpha(0.2);
certificate_print_image($pdf, $certificate, CERT_IMAGE_WATERMARK, $wmarkx, $wmarky, $wmarkw, $wmarkh);
$pdf->SetAlpha(1);
certificate_print_image($pdf, $certificate, CERT_IMAGE_SEAL, $sealx, $sealy, '', '');
certificate_print_image($pdf, $certificate, CERT_IMAGE_SIGNATURE, $sigx, $sigy, '', '');

// Add text
$pdf->SetTextColor(13, 44, 67);
$pdf->SetFont($text_font, '', 18, "$tcpdf_font_folder/$text_font");
$pdf->setFontSpacing(.3);
$pdf->SetXY($x + 40, $y + 41);
$pdf->writeHTMLCell(0, 0, '', '', 'Certificate of completion', 0, 0, 0, true, 'L');
$pdf->setFontSpacing(0);

$pdf->SetFont($text_font, '', 14, "$tcpdf_font_folder/$text_font");
$pdf->setFontSpacing(.3);
$pdf->SetXY($x + 40, $y + 60);
$pdf->writeHTMLCell(0, 0, '', '', 'This certificate is awarded to', 0, 0, 0, true, 'L');
$pdf->setFontSpacing(0);

// certificate_print_text($pdf, $x, $y, 'C', $fontsans, '', 40, get_string('title', 'certificate'));
// $pdf->SetTextColor(255, 255, 255);
// certificate_print_text($pdf, $x, $y + 20, 'C', $fontserif, '', 20, get_string('certify', 'certificate'));
$pdf->SetFont($text_font, 'B', 35, "$tcpdf_font_folder/$text_font");
$pdf->setFontSpacing(.3);
$pdf->SetXY($x + 40, $y + 65);
$pdf->writeHTMLCell(0, 0, '', '', ucfirst(fullname($USER)), 0, 0, 0, true, 'L');
$pdf->setFontSpacing(0);

$pdf->SetFont($text_font, '', 14, "$tcpdf_font_folder/$text_font");
$pdf->setFontSpacing(.3);
$pdf->SetXY($x + 40, $y + 85);
$pdf->writeHTMLCell(0, 0, '', '', 'In recognition of completing', 0, 0, 0, true, 'L');
$pdf->setFontSpacing(0);
// certificate_print_text($pdf, $x, $y + 55, 'C', $fontsans, '', 20, get_string('statement', 'certificate'));

$pdf->SetFont($text_font, 'B', 35, "$tcpdf_font_folder/$text_font");
$pdf->setFontSpacing(.3);
$pdf->SetXY($x + 40, $y + 90);
$pdf->writeHTMLCell(0, 0, '', '', ucfirst(format_string($course->fullname)), 0, 0, 0, true, 'L');
$pdf->setFontSpacing(0);

// $pdf->SetFont($title_font, 'B', 14, "$tcpdf_font_folder/$title_font");
// $pdf->SetXY($x + 10, $y + 125);
// $pdf->writeHTMLCell(0, 0, '', '', 'DATE', 0, 0, 0, true, 'C');
// $pdf->setFontSpacing(0);
// certificate_print_text($pdf, $x, $y + 92, 'C', $fontsans, '', 14,  certificate_get_date($certificate, $certrecord, $course));
// certificate_print_text($pdf, $x, $y + 102, 'C', $fontserif, '', 10, certificate_get_grade($certificate, $course));
// certificate_print_text($pdf, $x, $y + 112, 'C', $fontserif, '', 10, certificate_get_outcome($certificate, $course));


// use MultiCell
// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
// $pdf->MultiCell(180, 50, strtolower(format_string($course->fullname)), 0, 'C', 1, 0, $x + 90, $y + 105, true);


$date = '';
$splited_date_array = preg_split('/[\ \,]+/', certificate_get_date($certificate, $certrecord, $course));
foreach ($splited_date_array as $element) {
    $date .= $element . ' ';
}

// $newDate = date("d M Y", strtotime(certificate_get_date($certificate, $certrecord, $course)));

// $pdf->SetTextColor(0, 0, 0);
certificate_print_text($pdf, $x + 40, $y + 133, 'L', $fontsans, 'B', 16, $date);


if ($certificate->printhours) {
    // certificate_print_text($pdf, $x, $y + 122, 'C', $fontserif, '', 10, get_string('credithours', 'certificate') . ': ' . $certificate->printhours);
}
// certificate_print_text($pdf, $x, $codey, 'C', $fontserif, '', 10, certificate_get_code($certificate, $certrecord));
$i = 0;
if ($certificate->printteacher) {
    // $context = context_module::instance($cm->id);
    // if ($teachers = get_users_by_capability($context, 'mod/certificate:printteacher', '', $sort = 'u.lastname ASC', '', '', '', '', false)) {
    //     foreach ($teachers as $teacher) {
    //         $i++;
    //         certificate_print_text($pdf, $sigx, $sigy + ($i * 4), 'L', $fontserif, '', 12, fullname($teacher));
    //     }
    // }
}

// certificate_print_text($pdf, $custx, $custy, 'L', null, null, null, $certificate->customtext);
