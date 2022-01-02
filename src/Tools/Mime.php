<?php
namespace CatPaw\Tools;

abstract class Mime{
    /**
     * Alias of Mime::getContentType
     * Returns the mime type of the given resource.
     * For example, given the filename "/index.html", the mime type returned will be "text/html".
     * This can be useful when sending data to your clients.
     * @param string $location resource name.
     * @return string the mime type of the given resource as a String.
     */
    public static function resolveContentType(string $location):string{
        return self::getContentType($location);
    }
    
    /**
     * Returns the mime type of the given resource.
     * For example, given the filename "/index.html", the mime type returned will be "text/html".
     * This can be useful when sending data to your clients.
     * @param string $location location resource name.
     * @return string the mime type of the given resource as a String.
     */
    public static function getContentType(string $location):string{
        $tmpType = "";
        $tmpType0 = preg_split("/\\//",$location);
        $tmpType0Length = count($tmpType0);
        if($tmpType0Length > 0){
            $tmpType1 = preg_split("/\\./",$tmpType0[$tmpType0Length-1]);
            $tmpType1Length = count($tmpType1);
            if($tmpType1Length > 1){
                $tmpType = $tmpType1[$tmpType1Length-1];
            }else{
                $tmpType = "";
            }
        }else{
            $tmpType = "";
        }
        
        switch($tmpType){
            case "mkv":return "video/x-matroska";
            case "html":return "text/html";
            case "css": return "text/css";
            case "csv": return "text/csv";
            case "ics": return "text/calendar";
            case "txt": return "text/plain";

            case "ttf": return "font/ttf";
            case "woff": return "font/woff";
            case "woff2": return "font/woff2";

            case "aac":return "audio/aac";
            case "mid": 
            case "midi":return "audio/midi";
            case "oga":return "audio/og";
            case "wav":return "audio/x-wav";
            case "weba":return "audio/webm";
            case "mp3":return "audio/mpeg";

            case "ico":return "image/x-icon";
            case "jpeg": 
            case "jpg":return "image/jpeg";
            case "png":return "image/png";
            case "gif":return "image/gif";
            case "bmp":return "image/bmp";
            case "svg":return "image/svg+xml";
            case "tif": 
            case "tiff":return "image/tiff";
            case "webp":return "image/webp";

            case "avi":return "video/x-msvideo";
            case "mp4":return "video/mp4";
            case "mpeg":return "video/mpeg";
            case "ogv":return "video/ogg";
            case "webm":return "video/webm";
            case "3gp":return "video/3gpp";
            case "3g2":return "video/3gpp2";
            case "jpgv":return "video/jpg";

            case "abw":return "application/x-abiword";
            case "arc":return "application/octet-stream";
            case "azw":return "application/vnd.amazon.ebook";
            case "bin":return "application/octet-stream";
            case "bz":return "application/x-bzip";
            case "bz2":return "application/x-bzip2";
            case "csh":return "application/x-csh";
            case "doc":return "application/msword";
            case "epub":return "application/epub+zip";
            case "jar":return "application/java-archive";
            case "js":return "application/javascript";
            case "json":return "application/json";
            case "mpkg":return "application/vnd.apple.installer+xml";
            case "odp":return "application/vnd.oasis.opendocument.presentation";
            case "ods":return "application/vnd.oasis.opendocument.spreadsheet";
            case "odt":return "application/vnd.oasis.opendocument.text";
            case "ogx":return "application/ogg";
            case "pdf":return "application/pdf";
            case "ppt":return "application/vnd.ms-powerpoint";
            case "rar":return "application/x-rar-compressed";
            case "rtf":return "application/rtf";
            case "sh":return "application/x-sh";
            case "swf":return "application/x-shockwave-flash";
            case "tar":return "application/x-tar";
            case "vsd":return "application/vnd.visio";
            case "xhtml":return "application/xhtml+xml";
            case "xls":return "application/vnd.ms-excel";
            case "xml":return "application/xml";
            case "xul":return "application/vnd.mozilla.xul+xml";
            case "zip":return "application/zip";
            case "7z":return "application/x-7z-compressed";
            case "apk":return "application/vnd.android.package-archive";
            
            default: return "";
        }
    }
}