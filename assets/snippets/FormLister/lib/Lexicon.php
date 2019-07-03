<?php namespace Helpers;
use APIhelpers;
use DocumentParser;
/**
 * Class Lexicon
 * @package Helpers
 */
class Lexicon
{
    protected $modx = null;
    public $config = null;
    protected $lexicon = array();

    /**
     * Lexicon constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(DocumentParser $modx, $cfg = array())
    {
        $this->modx = $modx;
        $this->config = new Config($cfg);
    }

    /**
     * Загрузка языкового пакета
     *
     * @param string $name файл языкового пакета
     * @param string $lang имя языкового пакета
     * @param string $langDir папка с языковыми пакетами
     * @return array массив с лексиконом
     */
    public function fromFile($name = 'core', $lang = '', $langDir = '') {
        return $this->loadLang($name, $lang, $langDir);
    }

    /**
     * Загрузка языкового пакета
     *
     * @param string $name файл языкового пакета
     * @param string $lang имя языкового пакета
     * @param string $langDir папка с языковыми пакетами
     * @return array массив с лексиконом
     * @deprecated
     */
    public function loadLang($name = 'core', $lang = '', $langDir = '')
    {
        $langDir = empty($langDir) ? MODX_BASE_PATH . $this->config->getCFGDef('langDir',
                'lang/') : MODX_BASE_PATH . $langDir;
        if (empty($lang)) {
            $lang = $this->config->getCFGDef('lang', $this->modx->getConfig('manager_language'));
        }

        if (is_scalar($name) && !empty($name)) {
            $name = array($name);
        }

        foreach ($name as $n) {
            if ($lang != 'english') {
                $this->loadLexiconFile($n, 'english', $langDir);
            }
            $this->loadLexiconFile($n, $lang, $langDir);
        }

        return $this->getLexicon();
    }

    /**
     * @param string $name
     * @param string $lang
     * @param string $langDir
     */
    private function loadLexiconFile($name = 'core', $lang = '', $langDir = '')
    {
        $filepath = "{$langDir}{$lang}/{$name}.inc.php";
        if (file_exists($filepath)) {
            $tmp = include($filepath);
            if (is_array($tmp)) {
                $this->setLexicon($tmp);
            }
        }
    }

    /**
     * Получение строк из массива
     *
     * @param $lang
     * @return array
     */
    public function fromArray($lang = array())
    {
        $language = $this->config->getCFGDef('lang', $this->modx->getConfig('manager_language'));
        if (is_array($lang) && isset($lang[$language])) {
            $this->setLexicon($lang[$language]);
        }

        return $this->getLexicon();
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
        $out = APIhelpers::getkey($this->lexicon, $name, $def);
        if (class_exists('evoBabel', false) && isset($this->modx->snippetCache['lang'])) {
            $msg = $this->modx->runSnippet('lang', array('a' => $name));
            if (!empty($msg)) {
                $out = $msg;
            }
        }

        return $out;
    }

    /**
     * Замена в шаблоне фраз из лексикона
     *
     * @param string $tpl HTML шаблон
     * @return string
     */
    public function parseLang($tpl)
    {
        if (is_scalar($tpl) && !empty($tpl)) {
            if (preg_match_all("/\[\%([a-zA-Z0-9\.\_\-]+)\%\]/", $tpl, $match)) {
                $langVal = array();
                foreach ($match[1] as $item) {
                    $langVal[] = $this->getMsg($item);
                }
                $tpl = str_replace($match[0], $langVal, $tpl);
            }
        } else {
            $tpl = '';
        }

        return $tpl;
    }

    /**
     * @return bool
     */
    public function isReady()
    {
        return !empty($this->lexicon);
    }

    /**
     * @param array $lexicon
     * @param bool $overwrite
     * @return $this
     */
    public function setLexicon($lexicon = array(), $overwrite = false) {
        if ($overwrite) {
            $this->lexicon = $lexicon;
        } else {
            $this->lexicon = array_merge($this->lexicon, $lexicon);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getLexicon() {
        return $this->lexicon;
    }
}
