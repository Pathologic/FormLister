<?php namespace FormLister;
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 06.02.2016
 * Time: 1:07
 */

include_once ('modxCaptcha.php');

class captcha
{
    protected $modx = null;
    protected $captcha = null;
    protected $formid = '';

    public static function init($modx, $params = array()) {
        if (!isset($params['formid'])) {
            return false;
        } else {
            return new self($modx, $params);
        }
    }

    public function __construct($modx, $params = array())
    {
        $this->modx = $modx;
        $this->params = $params;
        $width = isset($params['captchaWidth']) ? $params['captchaWidth'] : 200;
        $height = isset($params['captchaHeight']) ? $params['captchaHeight'] : 160;
        $this->captcha = new \modxCaptcha($modx, $width, $height);
        $this->formid = $params['formid'];
    }

    public function getCaptcha() {
        $out = $_SESSION[$this->formid.'.captcha'];
        $_SESSION[$this->formid.'.captcha'] = $this->captcha->word;
        return $out;

    }

    public function getPlaceholder() {
        if (isset($this->params['captchaInline']) && $this->params['captchaInline']) {
            $out = $this->captcha->output_image(true);
        } else {
            $out = MODX_BASE_URL . 'assets/snippets/FormLister/lib/captcha/modxCaptcha/connector.php?formid=' . $this->formid;
            if (isset($this->params['captchaWidth'])) $out .= '&w=' . $this->params['captchaWidth'];
            if (isset($this->params['captchaHeight'])) $out .= '&h=' . $this->params['captchaHeight'];
        }
        return $out;
    }
}