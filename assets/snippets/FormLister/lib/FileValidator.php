<?php namespace FormLister;

/**
 * Правила проверки файлов
 * Class FileValidator
 * @package FormLister
 */
class FileValidator
{
    /**
     * @param $value
     * @return bool
     */
    public static function required ($value)
    {
        $value = self::value($value);
        $flag = false;
        foreach ($value as $file) {
            $flag = !empty($file) && !$file['error'] && is_uploaded_file($file['tmp_name']);
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @return bool
     * @deprecated
     */
    public static function optional ($value)
    {
        return self::required($value);
    }

    /**
     * @param $value
     * @param $allowed
     * @return bool
     */
    public static function allowed ($value, $allowed)
    {
        $value = self::value($value);
        $flag = false;
        foreach ($value as $file) {
            $ext = strtolower(substr(strrchr($file['name'], '.'), 1));
            $flag = in_array($ext, $allowed);
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @return bool
     */
    public static function images ($value)
    {
        return self::allowed($value, array("jpg", "jpeg", "png", "gif", "bmp"));
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public static function maxSize ($value, $max)
    {
        $value = self::value($value);
        $flag = false;
        foreach ($value as $file) {
            $size = round($file['size'] / 1024, 0);
            $flag = $size < $max;
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public static function minSize ($value, $min)
    {
        $value = self::value($value);
        $flag = false;
        foreach ($value as $file) {
            $size = round($file['size'] / 1024, 0);
            $flag = $size > $min;
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public static function sizeBetween ($value, $min, $max)
    {
        $value = self::value($value);
        $flag = false;
        foreach ($value as $file) {
            $size = round($file['size'] / 1024, 0);
            $flag = $size > $min && $size < $max;
            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $value
     * @param $max
     * @return bool
     */
    public static function maxCount ($value, $max)
    {
        $value = self::value($value);

        return self::getCount($value) < $max;
    }

    /**
     * @param $value
     * @param $min
     * @return bool
     */
    public static function minCount ($value, $min)
    {
        $value = self::value($value);

        return self::getCount($value) > $min;
    }

    /**
     * @param $value
     * @param $min
     * @param $max
     * @return bool
     */
    public static function countBetween ($value, $min, $max)
    {
        $value = self::value($value);

        return self::getCount($value) > $min && self::getCount($value) < $max;
    }

    /**
     * @param $value
     * @return bool
     */
    protected static function isArray ($value)
    {
        return isset($value[0]);
    }

    /**
     * @param $value
     * @return array
     */
    protected static function value ($value) {
        $out = [];
        if (!empty($value) && !self::isArray($value)) {
            $out = [$value];
        }

        return $out;
    }

    /**
     * @param $value
     * @return int
     */
    protected static function getCount ($value)
    {
        $out = 0;
        foreach ($value as $file) {
            if (!$file['error'] && is_uploaded_file($file['tmp_name'])) {
                $out++;
            }
        }

        return $out;
    }
}
