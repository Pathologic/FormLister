<?php

/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 19.11.2016
 * Time: 0:27
 */
class smsCaptchaWrapper
{
    /**
     * @var null
     * id, errorEmptyCode, errorCodeRequired, errorCodeFailed, errorCodeExpired, errorCodeUsed
     */
    public $cfg = null;

    /**
     * modxCaptchaWrapper constructor.
     * @param $modx
     * @param $cfg
     */
    public function __construct($modx, $cfg)
    {
        $this->cfg = $cfg;
    }

    /**
     * Устанавливает значение капчи
     * @return mixed
     */
    public function init()
    {
        return;
    }

    /**
     * Плейсхолдер капчи для вывода в шаблон
     * Может быть ссылкой на коннектор (чтобы можно было обновлять c помощью js), может быть сразу картинкой в base64
     * @return string
     */
    public function getPlaceholder()
    {
        return '';
    }

    /**
     * @param $value
     * @return bool
     */
    public static function validate($FormLister, $value, $captcha)
    {
        $id = \APIhelpers::getkey($captcha->cfg, 'id');
        if (empty($value)) {
            return \APIhelpers::getkey($captcha->cfg, 'errorEmptyCode',
                'Введите код авторизации');
        }

        if (empty($_SESSION[$id . '.smscaptcha'])) {
            return \APIhelpers::getkey($captcha->cfg, 'errorCodeRequired',
                'Получите код авторизации');
        }

        $sms = $FormLister->loadModel('sms', 'assets/snippets/FormLister/lib/captcha/smsCaptcha/model.php');

        if (is_null($sms->getData($_SESSION[$id . '.smscaptcha'], $id)->getID())) {

            return \APIhelpers::getkey($captcha->cfg, 'errorCodeRequired', 'Получите код авторизации');
        }

        if ($sms->get('code') != $value) {

            return \APIhelpers::getkey($captcha->cfg, 'errorCodeFailed', 'Неверный код авторизации');
        }

        if ($sms->get('expires') < time()) {
            $sms->delete($sms->getID());

            return \APIhelpers::getkey($captcha->cfg, 'errorCodeExpired',
                'Код авторизации истек, получите новый');
        } else {
            if (!$sms->get('active')) {
                $sms->set('active', 1)->set('expires', time() + \APIhelpers::getkey($captcha->cfg, 'codeLifeTime',
                        86400))->set('ip', \APIhelpers::getUserIP())->save();
            } else {

                return \APIhelpers::getkey($captcha->cfg, 'errorCodeUsed',
                    'Код авторизации уже использовался');
            }
            $out = true;
            if (method_exists($FormLister, 'setField')) {
                $FormLister->setField('captcha.phone', $sms->get('phone'));
            }
        }

        return $out;
    }
}

