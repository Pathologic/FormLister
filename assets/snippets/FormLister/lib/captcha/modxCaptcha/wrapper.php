<?php
/**
 * Обертка для работы с классом капчи
 */
include_once ('modxCaptcha.php');

class modxCaptchaWrapper
{
    /**
     * @var array $cfg
     * id
     * width
     * height
     * inline
     * connectorDir
     */
    protected $cfg = null;
    protected $captcha = null;

    public function __construct($modx, $cfg)
    {
        $cfg['id'] = isset($cfg['id']) ? $cfg['id'] : 'modx';
        $cfg['width'] = isset($cfg['width']) ? $cfg['width'] : 100;
        $cfg['height'] = isset($cfg['height']) ? $cfg['height'] : 60;
        $cfg['inline'] = isset($cfg['inline']) ? $cfg['inline'] : 1;
        $cfg['connectorDir'] = isset($cfg['connectorDir']) ? $cfg['connectorDir'] : 'assets/snippets/FormLister/lib/captcha/modxCaptcha/';
        $this->cfg = $cfg;
        $this->captcha = new \modxCaptcha($modx, $cfg['width'], $cfg['height']);
    }

    /**
     * Значение капчи
     * @return mixed
     */
    public function getValue() {
        $formid = $this->cfg['id'];
        $out = $_SESSION[$formid.'.captcha'];
        $_SESSION[$formid.'.captcha'] = $this->captcha->word;
        return $out;
    }

    /**
     * Плейсхолдер капчи для вывода в шаблон
     * Может быть ссылкой на коннектор (чтобы можно было обновлять c помощью js), может быть сразу картинкой в base64
     * @return string
     */
    public function getPlaceholder() {
        if ($this->cfg['inline']) {
            $out = $this->captcha->output_image(true);
        } else {
            $out = MODX_BASE_URL . $this->cfg['connectorDir'] . 'connector.php?formid=' . $this->cfg['id'];
            $out .= '&w=' . $this->cfg['width'];
            $out .= '&h=' . $this->cfg['height'];
        }
        return $out;
    }
}