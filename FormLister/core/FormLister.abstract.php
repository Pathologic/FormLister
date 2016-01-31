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

    /**
     * Объект DocumentParser - основной класс MODX'а
     * @var DocumentParser
     * @access protected
     */
    protected $modx = null;

    protected $formid = '';
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
        'fields' => array(),
        'errors' => array(),
        'messages' => array(),
        'status' => false
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

    public function __construct(\DocumentParser $modx, $cfg = array()) {
        $this->modx = $modx;
        $this->setConfig($cfg);
        if (!isset($this->_cfg['formid'])) return false;
        $this->formid = $this->getCFGDef('formid');
        $this->setRequestParams(array_merge($_GET,$_POST));
        $this->setFields();
        $this->renderTpl = $this->getCFGDef('formTpl');
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
    public function render() {
        if ($this->isSubmitted) {
            $this->validateForm();
            if ($this->isValid) $this->process(); //здесь обрабатываем данные формы
        }
        return $this->renderForm();
    }

    /**
     * Вывод формы или шаблона
     *
     * @param int $api
     * @return null|string
     */
    public function renderForm($api = 0) {
        if ($api) return json_encode($this->getFormData());
        $formStatus = $this->getFormStatus();
        $tpl = $this->renderTpl;
        $plh = $formStatus ? $this->fieldsToPlaceholders($this->getFormFields(),'','') : $this->fieldsToPlaceholders($this->getFormFields(),'','value'); //поля формы для подстановки в шаблон
        $plh = array_merge($this->addPlaceholders(),$plh);

        foreach ($this->getFormErrors() as $field => $error) {
            foreach ($error as $type => $message) {
                $classType = ($type == 'required') ? 'required' : 'error';
                $plh[$field.'.error'] = $this->parseChunk($this->getCFGDef('errorTpl','@CODE:<div class="error">[+message+]</div>'),array('message'=>$message));
                $plh[$field.'.'.$classType.'.class'] = $this->getCFGDef($field.'.'.$classType.'.class',$this->getCFGDef($classType.'.class',$classType));
            }
        }

        $plh['form.messages'] = $this->renderMessages();

        $form = $this->parseChunk($tpl,$plh);
        if ($formStatus) $form = nl2br($form);
        return $form;
    }

    public function setFields() {
        if ($this->isSubmitted) {
            foreach ($this->_rq as $key => $value) {
                $this->setField($key,$value);
            }
        }
    }

    public function validateForm() {
        $this->getValidationRules();
        if (!$this->rules) return; //если правил нет, то не проверяем

        include_once(MODX_BASE_PATH.'assets/snippets/FormLister/lib/PHPixie/validate/vendor/autoload.php');
        $validate = new \PHPixie\Validate();
        $validator = $validate->validator();
        $document = $validator->rule()->addDocument();

        //применяем правила
        foreach ($this->rules as $field => $rules) {
            $_field = $document->valueField($field);
            $filters = array();
            foreach ($rules as $rule => $description) {
                if ($rule == 'required') {
                    $_field->required();
                } else {
                    if (isset($description['params']) && is_array($description['params'])) {
                        $filters[$rule] = $description['params'];
                    } else {
                        $filters[] = $rule;
                    }
                }
            }
            if ($filters) {
                $_field->addFilter()->filters($filters);
            }
        }

        $document->allowExtraFields();
        $result = $validator->validate($this->_rq);
        $this->isValid = $result->isValid();
        if (!$this->isValid) {
            $this->addMessage('Форма заполнена неверно.');
            foreach ($result->invalidFields() as $fieldResult) {
                foreach ($fieldResult->errors() as $error) {
                    if ($error->type() == 'empty') {
                        $this->addError(
                            $fieldResult->path(),
                            'required',
                            $this->rules[$fieldResult->path()]['required']
                        );
                    } else {
                        $rule = $this->rules[$fieldResult->path()][$error->filter()];
                        $message = isset($rule['message']) ? $rule['message'] : $rule;
                        $this->addError(
                            $fieldResult->path(),
                            $error->filter(),
                            $message
                        );
                    }
                }
            }
        }
        return $this->isValid;
    }

    public function getFormData() {
        return $this->formData;
    }

    public function getFormErrors() {
        return $this->formData['errors'];
    }

    public function getFormMessages() {
        return $this->formData['messages'];
    }

    public function getFormFields() {
        return $this->formData['fields'];
    }

    public function getFormStatus() {
        return $this->formData['status'];
    }

    public function setFormStatus($status) {
        $this->formData['status'] = (bool)$status;
    }

    public function getField($field) {
        return isset($this->formData['fields'][$field]) ? $this->formData['fields'][$field] : '';
    }

    public function setField($field, $value) {
        $this->formData['fields'][$field] = $value;
    }

    public function addError($field, $type, $message) {
        $this->formData['errors'][$field][$type] = $message;
    }

    public function addMessage($message = '') {
        if ($message) $this->formData['messages'][] = $message;
    }

    public function fieldsToPlaceholders($fields = array(), $prefix = '', $suffix = '') {
        $out = array();
        if ($fields) {
            foreach ($fields as $field => $value) {
                $field = array($prefix,$field,$suffix);
                $field = implode('.',array_filter($field));
                $out[$field] = \APIhelpers::e($value);
            }
        }
        return $out;
    }

    public function getValidationRules() {
        $rules = $this->getCFGDef('rules','');
        $rules = \jsonHelper::jsonDecode($rules,array('assoc'=>true));
        $this->rules = $rules;
    }

    public function renderMessages() {
        $splitter = $this->getCFGDef('formMessagesSplitter','<br>');
        $messages = implode($splitter,$this->getFormMessages());
        $errors = implode($splitter,$this->getFormErrors());
        $out = $this->parseChunk($this->getCFGDef('messagesTpl','@CODE:<div class="form-messages">[+messages+]</div>'),array(
            'messages'=>$messages,
            'errors'=>$errors
        ));
        return $out;
    }

    public function renderReport() {
        $out = '';
        $tpl = $this->getCFGDef('reportTpl','');
        $plh = $this->fieldsToPlaceholders($this->getFormFields()); //поля формы для подстановки в шаблон
        $plh = array_merge($this->addPlaceholders(),$plh);
        if (empty($tpl)) {
            foreach ($plh as $key => $value)
                $out .= "$key: $value\n";
        } else {
            $out = $this->parseChunk($tpl,$plh);
        }
        return $out;
    }


    //из eform
    /**
     * @param $mail - объект почтового класса
     * @param $type - тип адреса
     * @param $addr - адрес
     */
    public function addAddressToMailer(&$mail, $type, $addr) {
        if (empty($addr)) return;
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

    public function sendForm() {
        //если отправлять некуда или незачем, то делаем вид, что отправили
        if (!$this->getCFGDef('to') || $this->getCFGDef('noemail')) {
            $this->setFormStatus(true);
            return true;
        }

        $isHtml = $this->getCFGDef('isHtml',1);
        $report = $this->renderReport();

        //херня какая-то
        $report = !$isHtml ? html_entity_decode($report) : nl2br(htmlspecialchars_decode($report, ENT_QUOTES));

        $this->modx->loadExtension('MODxMailer');
        $mail = &$this->modx->mail;
        $mail->IsHTML($isHtml);
        $mail->From     = $this->getCFGDef('from',$this->modx->config['emailsender']);
        $mail->FromName = $this->getCFGDef('fromname',$this->modx->config['site_name']);
        $mail->Subject  = $this->getCFGDef('subject');
        $mail->Body     = $report;
        $this->addAddressToMailer($mail,"replyTo",$this->getCFGDef('replyTo'));
        $this->addAddressToMailer($mail,"to",$this->getCFGDef('to'));
        $this->addAddressToMailer($mail,"cc",$this->getCFGDef('cc'));
        $this->addAddressToMailer($mail,"bcc",$this->getCFGDef('bcc'));

        //AttachFilesToMailer($modx->mail,$attachments);

        $result = $mail->send();
        if(!$result) {
            $this->addMessage("Произошла ошибка при отправке формы ({$mail->ErrorInfo})");
            //$modx->mail->ErrorInfo; - добавить потом в сообщения отладки
        } else {
            $mail->ClearAllRecipients();
            $mail->ClearAttachments();

        }
        return $result;
    }

    public function checkSubmitProtection() {
        $result = false;
        if ($protectSubmit = $this->getCFGDef('protectSubmit',1)) {
            $hash = $this->getFormHash();
            if (isset($_SESSION[$this->formid.'_hash']) && $_SESSION[$this->formid.'_hash'] == $hash && $hash!='') {
                $result = true;
                $this->addMessage('Данные успешно отправлены. Нет нужды отправлять данные несколько раз.');
            }
        }
        return $result;
    }

    public function checkSubmitLimit() {
        $submitLimit = $this->getCFGDef('submitLimit',60);
        $result = false;
        if($submitLimit >0){
            if( time()<$submitLimit+$_SESSION[$this->formid.'_limit'] ){
                $result = true;
                $this->addMessage('Вы уже отправляли эту форму, попробуйте еще раз через '.round($submitLimit / 60,0). ' мин.');
            }
            else unset($_SESSION[$this->formid.'_limit'], $_SESSION[$this->formid.'_hash']); //time expired
        }
        return $result;
    }

    public function setSubmitProtection() {
        if($this->getCFGDef('submitLimit',1)) $_SESSION[$this->formid.'_hash'] = $this->getFormHash(); //hash is set earlier
        if($this->getCFGDef('submitLimit',60)>0) $_SESSION[$this->formid.'_limit'] = time();
    }

    public function getFormHash() {
        $hash = '';
        $protectSubmit = $this->getCFGDef('protectSubmit',1);
        if (!is_numeric($protectSubmit)) { //supplied field names
            $protectSubmit = explode(',', $protectSubmit);
            foreach ($protectSubmit as $field)
                $hash .= $this->getField($field);
        } else //all required fields
            foreach ($this->rules as $field => $rules)
                foreach ($rules as $rule => $description)
                    if ($rule == 'required') $hash .= $this->getField($field);
        if($hash) $hash = md5($hash);
        return $hash;
    }

    public function addPlaceholders ($placeholders = array()) {
        $out = $placeholders;
        $plhGroups = explode(',',$this->getCFGDef('addPlaceholders',''));
        foreach ($plhGroups as $group) {
            switch ($group) {
                case 'session':
                    $out = array_merge($out,$this->fieldsToPlaceholders($_SESSION,'session'));
                    break;
                case 'document':
                    include_once (MODX_BASE_PATH . 'assets/lib/MODxAPI/modResource.php');
                    $doc = new \modResource($this->modx);
                    $out = array_merge($out,$this->fieldsToPlaceholders($doc->edit($this->modx->documentIdentifier)->toArray(),'doc'));
                    break;
                case 'cookie':
                    $out = array_merge($out,$this->fieldsToPlaceholders($_COOKIE,'cookie'));
                    break;
                case 'placeholders':
                    $out = array_merge($out,$this->fieldsToPlaceholders($this->modx['placeholders'],'plh'));
                    break;
            }
        }
        return $out;
    }

    public function parseChunk($name, $data, $parseDocumentSource = false)
    {
        $out = null;
        $out = \DLTemplate::getInstance($this->modx)->parseChunk($name, $data, $parseDocumentSource);
        return $out;
    }

    /**
     * Обработка формы, определяется контроллерами
     *
     * @return mixed
     */
    abstract public function process();
}