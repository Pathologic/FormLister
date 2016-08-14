<?php namespace FormLister;

/**
 * Правила проверки файлов
 * Class fValidator
 * @package FormLister
 */

class FileValidator
{
    public function required($value) {
        if (!$this->isArray($value)) $value = array($value);
        $flag = false;
        foreach ($value as $file) {
            $flag = !$file['error'] && is_uploaded_file($file['tmp_name']);
        }
        return $flag;
    }

    public function optional($value) {
        if (!$this->isArray($value)) $value = array($value);
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $flag = !$file['error'] && is_uploaded_file($file['tmp_name']);
            }
        }
        return $flag;
    }

    public function allowed($value, $allowed) {
        if (!$this->isArray($value)) $value = array($value);
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $ext = strtolower(array_pop(explode('.', $file['name'])));
                $flag = in_array($ext, $allowed);
            }
        }
        return $flag;
    }

    public function images($value) {
        return $this->allowed($value,array("jpg","jpeg","png","gif","bmp"));
    }

    public function maxSize($value, $max) {
        if (!$this->isArray($value)) $value = array($value);
        $flag = false;
        foreach ($value as $file) {
            $size = round($file['size'] / 1024,0);
            $flag = $size < $max;
        }
        return $flag;
    }

    public function minSize($value, $min) {
        if (!$this->isArray($value)) $value = array($value);
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $size = round($file['size'] / 1024, 0);
                $flag = $size > $min;
            }
        }
        return $flag;
    }
    
    public function sizeBetween ($value, $min, $max) {
        if (!$this->isArray($value)) $value = array($value);
        $flag = false;
        foreach ($value as $file) {
            if ($file['error'] === 4) {
                $flag = true;
            } else {
                $size = round($file['size'] / 1024, 0);
                $flag = $size > $min && $size < $max;
            }
        }
        return $flag;
    }

    public function maxCount($value, $max) {
        if (!$this->isArray($value)) $value = array($value);
        return count($value) < $max;
    }

    public function minCount($value, $min) {
        if (!$this->isArray($value)) $value = array($value);
        return count($value) > $min;
    }

    public function countBetween ($value, $min, $max) {
        if (!$this->isArray($value)) $value = array($value);
        return count($value) > $min && count($value) < $max;
    }

    private function isArray($value) {
        return !is_null($value[0]);
    }
}