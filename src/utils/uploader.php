<?php
/**
 * Safe Image Upload Handler
 */

if (!function_exists('handleImageUpload')) {
    function handleImageUpload($inputName, $targetDir = 'assets/uploads/') {
        if (!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $file = $_FILES[$inputName];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowedExtensions)) {
            return '';
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            return '';
        }

        // Resolve target directory relative to project root
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__DIR__));
        $normalizedDir = rtrim($targetDir, '/') . '/';
        $fullTargetDir = $rootPath . '/' . $normalizedDir;

        if (!is_dir($fullTargetDir)) {
            mkdir($fullTargetDir, 0777, true);
        }

        $safeName = uniqid('img_', true) . '.' . $ext;
        $destination = $fullTargetDir . $safeName;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $normalizedDir . $safeName;
        }

        return '';
    }
}
