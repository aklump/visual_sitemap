<?php


namespace AKlump\LoftLib\Component\Pdf;


class PdfFile
{

    /**
     * Force a pdf file to download.
     *
     * @param string $path Path to existing pdf file.
     */
    public static function download($path)
    {
        static::validateFile($path);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        return static::serve($path);
    }

    protected static function validateFile($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("There is no file at: \"$path\"");
        }
    }

    /**
     * Helper function
     *
     * @param $path
     */
    protected static function serve($path)
    {

        header('Content-Description: File Transfer');
//        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * Given a filepath serve the file to a browser with headers.
     *
     * @param string $path Path to existing pdf file.
     */
//    public static function browse($path)
//    {
//static::validateFile($path);
//        header('Content-Type: application/pdf');
//        header('Content-Disposition: inline; filename="' . basename($path) . '"');
//        header('Accept-Ranges: bytes');
//        return static::stream($path);
//    }


}
