<?php namespace Helpers;

use DocumentParser;

include_once (MODX_BASE_PATH . 'assets/lib/Helpers/APIHelpers.class.php');

class Lexicon
{
    protected $modx = null;
    protected $langDir = '';
    protected $_lang = array();

    /**
     * Lexicon constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct($modx, $cfg = array()) {
        $this->modx = $modx;
        $this->langDir = isset($cfg['langDir']) ? MODX_BASE_PATH . $cfg['langDir'] : __DIR__."/lang/";
    }

    /**
     * Загрузка языкового пакета
     *
     * @param string $name ключ языкового пакета
     * @param string $lang имя языкового пакета
     * @return array массив с лексиконом
     */
    public function loadLang($name = 'core', $lang = '')
    {
        if (empty($lang)) {
            $lang = $this->modx->config['manager_language'];
        }

        if (is_scalar($name)) {
            $name = array($name);
        }
        foreach ($name as $n) {
            if (file_exists($this->langDir . "{$lang}/{$n}.inc.php")) {
                $tmp = include($this->langDir . "{$lang}/{$n}.inc.php");
                if (is_array($tmp)) {
                    $this->_lang = array_merge($this->_lang, $tmp);
                }
            }
        }
        return $this->_lang;
    }

    /**
     * Получение строки из языкового пакета
     *
     * @param string $name имя записи в языковом пакете
     * @param string $def Строка по умолчанию, если запись в языковом пакете не будет обнаружена
     * @return string строка в соответствии с текущими языковыми настройками
     */
    public function getMsg($name, $def = '')
    {
        return \APIhelpers::getkey($this->_lang, $name, $def);
    }
}