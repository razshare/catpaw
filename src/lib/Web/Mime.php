<?php

namespace CatPaw\Web;

abstract class Mime {
    private function __construct() {
    }

    /**
     * 
     * @param  string $path
     * @return string
     */
    private static function findExtension(string $path):string {
        return ($len = count($pieces = explode('.', basename($path)))) < 1?'':$pieces[$len - 1];
    }

    /**
     * 
     * @param  string $path
     * @return bool
     */
    public static function isAttachment(string $path):bool {
        $extension = self::findExtension($path);
        return match (strtolower($extension)) {
            "css",
            "weba",
            "wav",
            "mp4",
            "mp3",
            "jpeg",
            "jpg",
            "png",
            "gif",
            "ico",
            "svg",
            "xhtml",
            "xml",
            "md",
            "wasm",
            "js",
            "html"  => false,
            default => true,
        };
    }

    /**
     * Returns the mime type of the given resource.
     * For example, given the filename "/index.html", the mime type returned will be "text/html".
     * This can be useful when sending data to your clients.
     * @param  string $path
     * @return string the mime type of the given resource as a String.
     */
    public static function findContentType(string $path): string {
        $extension = self::findExtension($path);
        return match (strtolower($extension)) {
            "wasm" => "application/wasm",
            "mkv"  => "video/x-matroska",
            "html" => "text/html",
            "css"  => "text/css",
            "csv"  => "text/csv",
            "ics"  => "text/calendar",
            "txt"  => "text/plain",

            "ttf"   => "font/ttf",
            "woff"  => "font/woff",
            "woff2" => "font/woff2",

            "aac" => "audio/aac",
            "midi", "mid" => "audio/midi",
            "oga"  => "audio/og",
            "wav"  => "audio/x-wav",
            "weba" => "audio/webm",
            "mp3"  => "audio/mpeg",

            "ico" => "image/x-icon",
            "jpeg",
            "jpg" => "image/jpeg",
            "png" => "image/png",
            "gif" => "image/gif",
            "bmp" => "image/bmp",
            "svg" => "image/svg+xml",
            "tif",
            "tiff" => "image/tiff",
            "webp" => "image/webp",

            "avi"  => "video/x-msvideo",
            "mp4"  => "video/mp4",
            "mpeg" => "video/mpeg",
            "ogv"  => "video/ogg",
            "webm" => "video/webm",
            "3gp"  => "video/3gpp",
            "3g2"  => "video/3gpp2",
            "jpgv" => "video/jpg",

            "abw"   => "application/x-abiword",
            "arc"   => "application/octet-stream",
            "azw"   => "application/vnd.amazon.ebook",
            "bin"   => "application/octet-stream",
            "bz"    => "application/x-bzip",
            "bz2"   => "application/x-bzip2",
            "csh"   => "application/x-csh",
            "doc"   => "application/msword",
            "epub"  => "application/epub+zip",
            "jar"   => "application/java-archive",
            "js"    => "text/javascript",
            "json"  => "application/json",
            "mpkg"  => "application/vnd.apple.installer+xml",
            "odp"   => "application/vnd.oasis.opendocument.presentation",
            "ods"   => "application/vnd.oasis.opendocument.spreadsheet",
            "odt"   => "application/vnd.oasis.opendocument.text",
            "ogx"   => "application/ogg",
            "pdf"   => "application/pdf",
            "ppt"   => "application/vnd.ms-powerpoint",
            "rar"   => "application/x-rar-compressed",
            "rtf"   => "application/rtf",
            "sh"    => "application/x-sh",
            "swf"   => "application/x-shockwave-flash",
            "tar"   => "application/x-tar",
            "vsd"   => "application/vnd.visio",
            "xhtml" => "application/xhtml+xml",
            "xls"   => "application/vnd.ms-excel",
            "xml"   => "application/xml",
            "xul"   => "application/vnd.mozilla.xul+xml",
            "zip"   => "application/zip",
            "7z"    => "application/x-7z-compressed",
            "apk"   => "application/vnd.android.package-archive",
            default => '',
        };
    }
}
