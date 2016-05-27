<?php
/**
 * Обертка для работы с классом капчи
 */
include_once ('modxCaptcha.php');

class modxCaptchaWrapper
{
    protected $FL = null;
    protected $captcha = null;

    public function __construct(FormLister\Core $FL)
    {
        $this->FL = $FL;
        $width = $this->FL->getCFGDef('captchaWidth',100);
        $height = $this->FL->getCFGDef('captchaHeight',60);
        $this->captcha = new \modxCaptcha($FL->getMODX(), $width, $height);
    }

    /**
     * Значение капчи
     * @return mixed
     */
    public function getValue() {
        $formid = $this->FL->getFormId();
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
        if ($this->FL->getCFGDef('captchaInline',1)) {
            $out = $this->captcha->output_image(true);
        } else {
            $out = MODX_BASE_URL . 'assets/snippets/FormLister/lib/captcha/modxCaptcha/connector.php?formid=' . $this->FL->getFormId();
            $out .= '&w=' . $this->FL->getCFGDef('captchaWidth',200);
            $out .= '&h=' . $this->FL->getCFGDef('captchaHeight',160);
        }
        return $out;
    }

    /**
     * Установка правила валидации поля с капчей
     * @return array
     */
    public function getRule() {
        return array(
            "required" => $this->FL->getCFGDef('captchaRequiredMessage', 'Введите проверочный код'),
            "equals"   => array(
                "params"  => array($this->getValue()),
                "message" => $this->FL->getCFGDef('captchaErrorMessage', 'Неверный проверочный код')
            )
        );
    }
}