<?php

namespace app\core\helpers;

use app\core\SEException;
use app\core\SNEMLibrary;
use Exception;

class SEFileHelper
{
    /**
     * @throws SEException
     */
    public function createDirectory($path, $mode = 0775, $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $parentDir = dirname($path);

        // recurse if parent dir does not exist, and we are not at the root of the file system.
        if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
            self::createDirectory($parentDir, $mode, true);
        }

        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (Exception $e) {
            if (!is_dir($path)) {
                throw new SEException("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }

        try {
            return chmod($path, $mode);
        } catch (Exception $e) {
            throw new SEException(
                "Failed to change permissions for directory \"$path\": " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws SEException
     */
    public function checkDirectory($path, $createIfNotExists = false): bool
    {
        if (($check = (!file_exists($path) || !is_dir($path))) && $createIfNotExists) {
            self::createDirectory($path);
        }

        return !$check;
    }

    public function uploadFile($file, $path, $checkExists = false): bool
    {
        $fileName = $file['name'];
        $tmpFile = $file['tmp_name'];
        $desPath = $path . SNEMLibrary::DS . $fileName;

        if ($checkExists) {
            if (file_exists($desPath)) {
                return false;
            }
        }

        return move_uploaded_file($tmpFile, $desPath);
    }
}
