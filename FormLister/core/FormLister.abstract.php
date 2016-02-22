<?php namespace FormLister;

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
include_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
require_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/jsonHelper.class.php");
require_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/DLTemplate.class.php");

/**
 * Class FormLister
 * @package FormLister
 */
abstract class FormLister
{
    /**
     * @var array
     * Массив $_REQUEST
     */
    protected $_rq = array();

    protected $modx = null;

    protected $fs = null;

    protected $formid = '';

    protected $captcha = null;
    /**
     * Массив настроек переданный через параметры сниппету
     * @var array
     * @access private
     */
    private $_cfg = array();

    public $renderTpl = '';


    /**
     * Данные формы
     * fields - значения полей
     * errors - ошибки (поле => сообщение)
     * messages - сообщения
     * status - для api-режима, результат использования формы
     * @var array
     */
    private $formData = array(
        'fields'   => array(),
        'errors'   => array(),
        'messages' => array(),
        'status'   => false
    );

    /**
     * Массив с правилами валидации полей
     * @var array
     */
    protected $rules = array();

    /**
     * Флаг успешной валидации
     * @var bool
     */
    protected $isValid = true;

    protected $isSubmitted = false;

    public function __construct($modx, $cfg = array())
    {
        $this->modx = $modx;
        $this->fs = \Helpers\FS::getInstance();
        if (isset($cfg['config'])) {
            $cfg = array_merge($this->loadConfig($cfg['config']), $cfg);
        }
        $this->setConfig($cfg);
        if (!isset($this->_cfg['formid'])) {
            return false;
        }
        $this->formid = $this->getCFGDef('formid');
        $this->setRequestParams(array_merge($_GET, $_POST));
        $this->setFields();
        $this->renderTpl = $this->getCFGDef('formTpl');
        $this->initCaptcha();
    }

    /**
     * Загрузка конфигов из файла
     *
     * @param $name string имя конфига
     * @return array массив с настройками
     */
    public function loadConfig($name)
    {
        //$this->debug->debug('Load json config: ' . $this->debug->dumpData($name), 'loadconfig', 2);
        if (!is_scalar($name)) {
            $name = '';
        }
        $config = array();
        $name = explode(";", $name);
        foreach ($name as $cfgName) {
            $cfgName = explode(":", $cfgName, 2);
            if (empty($cfgName[1])) {
                $cfgName[1] = 'custom';
            }
            $cfgName[1] = rtrim($cfgName[1], '/');
            switch ($cfgName[1]) {
                case 'custom':
                case 'core':
                    $configFile = dirname(__DIR__) . "/config/{$cfgName[1]}/{$cfgName[0]}.json";
                    break;
                default:
                    $configFile = $this->fs->relativePath($cfgName[1] . '/' . $cfgName[0] . ".json");
                    break;
            }

            if ($this->fs->checkFile($configFile)) {
                $json = file_get_contents($configFile);
                $config = array_merge($config, \jsonHelper::jsonDecode($json, array('assoc' => true), true));
            }
        }

        //$this->debug->debugEnd("loadconfig");
        return $config;
    }

    /**
     * Получение всего списка настроек
     * @return array
     */
    public function getConfig()
    {
        return $this->_cfg;
    }

    /**
     * Сохранение настроек вызова сниппета
     * @param array $cfg массив настроек
     * @return int результат сохранения настроек
     */
    public function setConfig($cfg)
    {
        if (is_array($cfg)) {
            $this->_cfg = array_merge($this->_cfg, $cfg);
            $ret = count($this->_cfg);
        } else {
            $ret = false;
        }
        return $ret;
    }

    /**
     * Закгрузка значений по умолчанию, вызывается один раз при выводе формы
     */
    public function setDefaults() {
        $sources = array_unique(explode(',',$this->getCFGDef('defaultsSource','json')));
        $defaults = array();
        foreach ($sources as $source) {
            switch ($source) {
                case 'json':
                    $defaults = array_merge($defaults,\jsonHelper::jsonDecode($this->getCFGDef('defaults'), array('assoc' => true), true));
                    break;
                case 'session':
                    $_source = explode(':',$source);
                    $defaults = isset($_source[1]) && isset($_SESSION[$_source[1]]) ?
                        array_merge($defaults,$_SESSION[$_source[1]]) :
                        array_merge($defaults, $_SESSION);
                    break;
                case 'plh':
                    $_source = explode(':',$source);
                    $defaults = isset($_source[1]) && isset($this->modx->placeholders[$_source[1]]) ?
                        array_merge($defaults,$this->modx->placeholders[$_source[1]]) :
                        array_merge($defaults, $this->modx->placeholders);
                    break;
                case 'config':
                    $defaults = array_merge($defaults,$this->modx->config);
                    break;
                case 'cookie':
                    $_source = explode(':',$source);
                    $defaults = isset($_source[1]) && isset($_COOKIE[$_source[1]]) ?
                        array_merge($defaults,$_COOKIE[$_source[1]]) :
                        array_merge($defaults, $_COOKIE);
                    break;
                default:
                    $_source = explode(':',$source);
                    $classname = $_source[0];
                    if (class_exists($classname) && isset($_source[1])) {
                        $obj = new $classname($this->modx);
                        if ($data = $obj->edit($_source[1])) {
                            $defaults = array_merge($defaults,$data->toArray());
                        }
                    }
            }
        }
        foreach($defaults as $key => $value) {
            if (!empty($value)) $this->setField($key, $value);
        }
    }

    /**
     * Сохранение массива $_REQUEST
     * @param array $rq
     * @return int результат сохранения
     */
    public function setRequestParams($rq)
    {
        if (is_array($rq)) {
            $this->_rq = array_merge($this->_rq, $rq);
            $ret = count($this->_rq);
        } else {
            $ret = false;
        }
        $this->isSubmitted = isset($this->_rq['formid']) && $this->_rq['formid'] == $this->formid;
        return $ret;
    }

    /**
     * Полная перезапись настроек вызова сниппета
     * @param array $cfg массив настроек
     * @return int Общее число новых настроек
     */
    public function replaceConfig($cfg)
    {
        if (!is_array($cfg)) {
            $cfg = array();
        }
        $this->_cfg = $cfg;
        return count($this->_cfg);
    }

    /**
     * Получение информации из конфига
     *
     * @param string $name имя параметра в конфиге
     * @param mixed $def значение по умолчанию, если в конфиге нет искомого параметра
     * @return mixed значение из конфига
     */
    public function getCFGDef($name, $def = null)
    {
        return \APIHelpers::getkey($this->_cfg, $name, $def);
    }

    /*
     * Сценарий работы
     */
    public function render()
    {
        if ($this->isSubmitted) {
            $this->validateForm();
            if ($this->isValid) {
                $this->process();
            } //здесь обрабатываем данные формы
        }
        return $this->renderForm();
    }

    /**
     * Формирует массив плейсхолдеров
     * @return array
     */
    public function prerenderForm() {
        if ($this->getFormStatus()) {
            $plh = $this->fieldsToPlaceholders($this->getFormFields(), '', '', true);
        } else {
            $plh = array_merge(
                $this->fieldsToPlaceholders($this->getFormFields(), '', 'value'),
                $this->controlsToPlaceholders(),
                $this->errorsToPlaceholders()
            );
            $plh['form.messages'] = $this->renderMessages();
        }
        return $plh;
    }

    /**
     * Вывод шаблона
     *
     * @param int $api
     * @return null|string
     */
    public function renderForm($api = 0)
    {
        if ($api) {
            return json_encode($this->getFormData());
        }
        $form = $this->parseChunk($this->renderTpl, $this->prerenderForm());
        if ($this->getFormStatus()) {
            $form = nl2br($form);
        }
        return $form;
    }

    /**
     * Устанавливает значения полей формы
     */
    public function setFields()
    {
        if ($this->isSubmitted) {
            foreach ($this->_rq as $key => $value) {
                $this->setField($key, $value);
            }
        } else {
            $this->setDefaults();
        }
    }

    public function validateForm()
    {
        $this->getValidationRules();
        if (!$this->rules) {
            return false;
        } //если правил нет, то не проверяем

        include_once(MODX_BASE_PATH . 'assets/snippets/FormLister/lib/Validator.php');
        $validator = new \FormLister\Validator();
        //применяем правила
        foreach ($this->rules as $field => $rules) {
            $_field = $this->getField($field);
            $params = array($_field);
            foreach ($rules as $rule => $description) {
                if (is_array($description)) {
                    $params = array_merge($params,$description['params']);
                    $message = isset($description['message']) ? $description['message'] : 'Заполнено неверно.';
                } else {
                    $message = $description;
                }
                if (is_scalar($rule) && method_exists($validator, $rule)) {
                    $result = call_user_func_array(array($validator, $rule), $params);
                } elseif ((is_object($rule) && ($rule instanceof Closure)) || is_callable($rule)) {
                    $result = call_user_func_array($rule, $params);
                }
                if (!$result) {
                    $this->addError(
                        $field,
                        $rule,
                        $message
                    );
                    break;
                }
            }
        }
        return $this->isValid;
    }

    public function getFormData()
    {
        return $this->formData;
    }

    public function getFormErrors()
    {
        return $this->formData['errors'];
    }

    public function getFormMessages()
    {
        return $this->formData['messages'];
    }

    public function getFormFields()
    {
        return $this->formData['fields'];
    }

    public function getFormStatus()
    {
        return $this->formData['status'];
    }

    public function setFormStatus($status)
    {
        $this->formData['status'] = (bool)$status;
    }

    public function getField($field)
    {
        return isset($this->formData['fields'][$field]) ? $this->formData['fields'][$field] : '';
    }

    public function setField($field, $value)
    {
        $this->formData['fields'][$field] = $value;
    }

    public function addError($field, $type, $message)
    {
        $this->formData['errors'][$field][$type] = $message;
        $this->isValid = false;
    }

    public function addMessage($message = '')
    {
        if ($message) {
            $this->formData['messages'][] = $message;
        }
    }

    public function fieldsToPlaceholders($fields = array(), $prefix = '', $suffix = '', $split = false)
    {
        $out = array();
        if ($fields) {
            foreach ($fields as $field => $value) {
                $field = array($prefix, $field, $suffix);
                $field = implode('.', array_filter($field));
                if ($split && is_array($value)) {
                    $arraySplitter = $this->getCFGDef($field.'Splitter',$this->getCFGDef('arraySplitter','; '));
                    $value = implode($arraySplitter, $value);
                }
                $out[$field] = \APIhelpers::e($value);
            }
        }
        return $out;
    }

    public function errorsToPlaceholders()
    {
        $plh = array();
        foreach ($this->getFormErrors() as $field => $error) {
            foreach ($error as $type => $message) {
                $classType = ($type == 'required') ? 'required' : 'error';
                $plh[$field . '.error'] = $this->parseChunk($this->getCFGDef('errorTpl',
                    '@CODE:<div class="error">[+message+]</div>'), array('message' => $message));
                $plh[$field . '.' . $classType . '.class'] = $this->getCFGDef($field . '.' . $classType . '.class',
                    $this->getCFGDef($classType . '.class', $classType));
            }
        }
        return $plh;
    }

    //TODO
    public function controlsToPlaceholders()
    {
        $plh = array();
        $formControls = explode(',',$this->getCFGDef('formControls'));
        foreach ($formControls as $field) {
            $value = $this->getField($field);
            if (empty($value)) {
                continue;
            } elseif (is_array($value)) {
                foreach ($value as $_value) {
                    $plh["s.{$field}.{$_value}"] = 'selected';
                    $plh["c.{$field}.{$_value}"] = 'checked';
                }
            } else {
                $plh["s.{$field}.{$value}"] = 'selected';
                $plh["c.{$field}.{$value}"] = 'checked';
            }
        }
        return $plh;
    }

    /**
     * Загрузка массива с правилами валидации
     */
    public function getValidationRules()
    {
        $rules = $this->getCFGDef('rules', '');
        $rules = \jsonHelper::jsonDecode($rules, array('assoc' => true));
        $this->rules = $rules;
        if (!is_null($this->captcha)) {
            $this->addCaptchaRule();
        }
    }

    public function renderMessages()
    {
        $formMessages = $this->getFormMessages();
        $formErrors = $this->getFormErrors();

        $requiredMessages = $filterMessages = array();
        if ($formErrors) {
            foreach ($formErrors as $field => $error) {
                $type = key($error);
                if ($type == 'required') {
                    $requiredMessages[] = $error[$type];
                } else {
                    $filterMessages[] = $error[$type];
                }
            }
        }

        $out = $this->parseChunk($this->getCFGDef('messagesTpl', '@CODE:<div class="form-messages">[+messages+]</div>'),
            array(
                'messages' => $this->renderMessagesGroup($formMessages, $this->getCFGDef('messagesOuterTpl', ''),
                    $this->getCFGDef('messagesSplitter', '<br>')),
                'required' => $this->renderMessagesGroup($requiredMessages,
                    $this->getCFGDef('messagesRequiredOuterTpl', ''),
                    $this->getCFGDef('messagesRequiredSplitter', '<br>')),
                'filters'  => $this->renderMessagesGroup($filterMessages,
                    $this->getCFGDef('messagesFiltersOuterTpl', ''),
                    $this->getCFGDef('messagesFiltersSplitter', '<br>')),
            ));

        return $out;
    }

    public function renderMessagesGroup($messages, $wrapper, $splitter)
    {
        $out = '';
        if (is_array($messages) && !empty($messages)) {
            $out = implode($splitter, $messages);
            $wrapperChunk = $this->getCFGDef($wrapper, '@CODE: [+messages+]');
            $out = $this->parseChunk($wrapperChunk, array('messages' => $out));
        }
        return $out;
    }

    public function renderReport()
    {
        $out = '';
        $tpl = $this->getCFGDef('reportTpl', '');
        $plh = $this->fieldsToPlaceholders($this->getFormFields(), '', '',
            $this->getCFGDef('arraySplitter', '; ')); //поля формы для подстановки в шаблон
        $plh = array_merge($this->addPlaceholders(), $plh);
        if (empty($tpl)) {
            foreach ($plh as $key => $value) {
                $out .= "$key: $value\n";
            }
        } else {
            $out = $this->parseChunk($tpl, $plh);
        }
        return $out;
    }


    //из eform
    /**
     * @param $mail - объект почтового класса
     * @param $type - тип адреса
     * @param $addr - адрес
     */
    public function addAddressToMailer(&$mail, $type, $addr)
    {
        if (empty($addr)) {
            return;
        }
        $a = array_filter(array_map('trim', explode(',', $addr)));
        foreach ($a as $address) {
            switch ($type) {
                case 'to':
                    $mail->AddAddress($address);
                    break;
                case 'cc':
                    $mail->AddCC($address);
                    break;
                case 'bcc':
                    $mail->AddBCC($address);
                    break;
                case 'replyTo':
                    $mail->AddReplyTo($address);
            }
        }
    }

    public function sendForm()
    {
        //если отправлять некуда или незачем, то делаем вид, что отправили
        if (!$this->getCFGDef('to') || $this->getCFGDef('noemail')) {
            $this->setFormStatus(true);
            return true;
        }

        $isHtml = $this->getCFGDef('isHtml', 1);
        $report = $this->renderReport();

        //TODO: херня какая-то
        $report = !$isHtml ? html_entity_decode($report) : nl2br(htmlspecialchars_decode($report, ENT_QUOTES));

        $this->modx->loadExtension('MODxMailer');
        $mail = &$this->modx->mail;
        $mail->IsHTML($isHtml);
        $mail->From = $this->getCFGDef('from', $this->modx->config['emailsender']);
        $mail->FromName = $this->getCFGDef('fromname', $this->modx->config['site_name']);
        $mail->Subject = $this->getCFGDef('subjectTpl') ?
            $this->parseChunk($this->getCFGDef('subjectTpl'),$this->fieldsToPlaceholders($this->getFormFields(), '', '', $this->getCFGDef('arraySplitter', '; '))) :
            $this->getCFGDef('subject');
        $mail->Body = $report;
        $this->addAddressToMailer($mail, "replyTo", $this->getCFGDef('replyTo'));
        $this->addAddressToMailer($mail, "to", $this->getCFGDef('to'));
        $this->addAddressToMailer($mail, "cc", $this->getCFGDef('cc'));
        $this->addAddressToMailer($mail, "bcc", $this->getCFGDef('bcc'));

        //AttachFilesToMailer($modx->mail,$attachments);

        $result = $mail->send();
        if (!$result) {
            $this->addMessage("Произошла ошибка при отправке формы ({$mail->ErrorInfo})");
            //$modx->mail->ErrorInfo; - добавить потом в сообщения отладки
        } else {
            $mail->ClearAllRecipients();
            $mail->ClearAttachments();

        }
        return $result;
    }

    public function checkSubmitProtection()
    {
        $result = false;
        if ($protectSubmit = $this->getCFGDef('protectSubmit', 1)) {
            $hash = $this->getFormHash();
            if (isset($_SESSION[$this->formid . '_hash']) && $_SESSION[$this->formid . '_hash'] == $hash && $hash != '') {
                $result = true;
                $this->addMessage('Данные успешно отправлены. Нет нужды отправлять данные несколько раз.');
            }
        }
        return $result;
    }

    public function checkSubmitLimit()
    {
        $submitLimit = $this->getCFGDef('submitLimit', 60);
        $result = false;
        if ($submitLimit > 0) {
            if (time() < $submitLimit + $_SESSION[$this->formid . '_limit']) {
                $result = true;
                $this->addMessage('Вы уже отправляли эту форму, попробуйте еще раз через ' . round($submitLimit / 60,
                        0) . ' мин.');
            } else {
                unset($_SESSION[$this->formid . '_limit'], $_SESSION[$this->formid . '_hash']);
            } //time expired
        }
        return $result;
    }

    public function setSubmitProtection()
    {
        if ($this->getCFGDef('submitLimit', 1)) {
            $_SESSION[$this->formid . '_hash'] = $this->getFormHash();
        } //hash is set earlier
        if ($this->getCFGDef('submitLimit', 60) > 0) {
            $_SESSION[$this->formid . '_limit'] = time();
        }
    }

    public function getFormHash()
    {
        $hash = '';
        $protectSubmit = $this->getCFGDef('protectSubmit', 1);
        if (!is_numeric($protectSubmit)) { //supplied field names
            $protectSubmit = explode(',', $protectSubmit);
            foreach ($protectSubmit as $field) {
                $hash .= $this->getField($field);
            }
        } else //all required fields
        {
            foreach ($this->rules as $field => $rules) {
                foreach ($rules as $rule => $description) {
                    if ($rule == 'required') {
                        $hash .= $this->getField($field);
                    }
                }
            }
        }
        if ($hash) {
            $hash = md5($hash);
        }
        return $hash;
    }

    //TODO
    public function addPlaceholders($placeholders = array())
    {
        $out = $placeholders;
        $out['captcha'] = $this->addCaptchaPlaceholder();
        return $out;
    }

    public function parseChunk($name, $data, $parseDocumentSource = false)
    {
        $out = null;
        $out = \DLTemplate::getInstance($this->modx)->parseChunk($name, $data, $parseDocumentSource);
        return $out;
    }

    public function initCaptcha()
    {
        if ($captcha = $this->getCFGDef('captcha')) {
            $wrapper = MODX_BASE_PATH . "assets/snippets/FormLister/lib/captcha/{$captcha}/wrapper.php";
            if ($this->fs->checkFile($wrapper)) {
                include_once($wrapper);
                $_captcha = captcha::init($this->modx, $this->getConfig());
                if ($_captcha !== false) {
                    $this->captcha = $_captcha;
                }
            }
        }
    }

    public function addCaptchaRule()
    {
        $this->rules[$this->getCFGDef('captchaField', 'vericode')] = array(
            "required" => $this->getCFGDef('captchaRequiredMessage', 'Введите проверочный код'),
            "equals"   => array(
                "params"  => array($this->captcha->getCaptcha()),
                "message" => $this->getCFGDef('captchaErrorMessage', 'Неверный проверочный код')
            )
        );
    }

    public function addCaptchaPlaceholder()
    {
        $out = '';
        if (!is_null($this->captcha)) {
            $out = $this->captcha->getPlaceholder();
        }
        return $out;
    }

    /**
     * Обработка формы, определяется контроллерами
     *
     * @return mixed
     */
    abstract public function process();
}