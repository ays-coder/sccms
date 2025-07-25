 <?php
/*
 * PHP QR Code encoder
 * Based on libqrencode C library version 3.1.5
 * This is a single file wrapper for QR code generation
 * https://sourceforge.net/projects/phpqrcode/
 */

// Remove these lines (constants are defined in qrconfig.php)
// define('QR_CACHEABLE', false);
// define('QR_CACHE_DIR', false);
// define('QR_LOG_DIR', false);
// define('QR_FIND_BEST_MASK', true);
// define('QR_DEFAULT_MASK', 2);
// define('QR_PNG_MAXIMUM_SIZE', 1024);

include dirname(__FILE__) . '/qrconst.php';
include dirname(__FILE__) . '/qrconfig.php';
include dirname(__FILE__) . '/qrtools.php';
include dirname(__FILE__) . '/qrspec.php';
include dirname(__FILE__) . '/qrimage.php';
include dirname(__FILE__) . '/qrinput.php';
include dirname(__FILE__) . '/qrbitstream.php';
include dirname(__FILE__) . '/qrsplit.php';
include dirname(__FILE__) . '/qrrscode.php';
include dirname(__FILE__) . '/qrmask.php';
include dirname(__FILE__) . '/qrencode.php';

// Only define QRcode class if not already defined
if (!class_exists('QRcode')) {
    class QRcode
    {
        public static function png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint = false)
        {
            $enc = QRencode::factory($level, $size, $margin);
            return $enc->encodePNG($text, $outfile, $saveandprint = false);
        }

        public static function text($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4)
        {
            $enc = QRencode::factory($level, $size, $margin);
            return $enc->encode($text, $outfile);
        }
    }
}