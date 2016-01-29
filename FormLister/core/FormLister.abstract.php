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
     * @var array параметры для отправки почты
     */
    protected $_mailCfg = array();

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
        $this->getMailCfg(); //параметры для отправки почты в отдельный массив
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
     * Сценарий
     */
    public function render() {
        if ($this->isSubmitted) {
            $this->validateForm();
            if ($this->isValid) $this->process(); //здесь подменяем шаблон и отправляем форму
        }
        return $this->renderForm();
    }

    public function renderForm($api = 0) {
        $tpl = $this->renderTpl;
        $plh = $this->fieldsToPlaceholders($this->getFormFields()); //поля формы для подстановки в шаблон
        foreach ($this->getFormErrors() as $field => $error) {
            foreach ($error as $type => $message) {
                $classType = ($type == 'required') ? 'required' : 'error';
                $plh[$field.'.error'] = $this->parseChunk($this->getCFGDef('errorTpl','@CODE:<div class="error">[+message+]</div>'),array('message'=>$message));
                $plh[$field.'.'.$classType.'.class'] = $this->getCFGDef($field.'.'.$classType.'.class',$this->getCFGDef($classType.'.class',$classType));
            }
        }
        $plh['form.messages'] = $this->renderMessages();
        $form = $this->parseChunk($tpl,$plh);

        return $api ? json_encode($this->formData) : $form;
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

    public function fieldsToPlaceholders($fields = array()) {
        $out = array();
        if ($fields) {
            foreach ($fields as $field => $value) {
                $out[$field.'.value'] = \APIhelpers::e($value);
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

    public function sendForm() {
        $this->addMessage('Произошла ошибка при отправке формы');
        return false;
    }

    public function getMailCfg() {

    }

    public function parseChunk($name, $data, $parseDocumentSource = false)
    {
        $out = null;
        $out = \DLTemplate::getInstance($this->modx)->parseChunk($name, $data, $parseDocumentSource);
        return $out;
    }

    public function process() {

    }
}