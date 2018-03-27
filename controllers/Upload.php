<?php

class UploadController extends Controller {
    public $view = null;

    function run($filename) {
        $path = APP_ATTACHMENT_URL . $filename;
        $finfo = new finfo(FILEINFO_MIME);
        $content = file_get_contents($path);
        $file = new File();
        $name = $file->findFileByUrl($filename) ? $file->getTitle() : $filename;
        $mime = $finfo->buffer($content);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        echo $content;
    }
}
