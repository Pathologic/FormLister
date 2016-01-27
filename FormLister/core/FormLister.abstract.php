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
     * @var array
     */
    protected $_plh = array(
        'fields' => array(),
        'errors' => array(),
        'messages' => array()
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

    public function __construct($modx, $cfg = array()) {
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
            $this->getValidationRules();
            $this->validateForm();
            if ($this->isValid) $this->process(); //здесь подменяем шаблон и отправляем форму
        }
        return $this->renderForm();
    }

    public function renderForm($api = 0) {
        $tpl = $this->renderTpl;
        $plh = $this->fieldsToPlaceholders($this->_plh['fields']); //поля формы для подстановки в шаблон
        foreach ($this->_plh['errors'] as $type => $error) {
            $classType = ($type == 'required') ? 'required' : 'error';
            foreach ($error as $field => $message) {
                $plh[$field.'.error'] = $this->parseChunk($this->getCFGDef('errorTpl','@CODE:<div class="error">[+message+]</div>'),array('message'=>$message));
                $plh[$field.'.'.$classType.'.class'] = $this->getCFGDef($field.'.'.$classType.'.class',$this->getCFGDef($classType.'.class',$classType));
            }
        }
        $plh['form.messages'] = $this->renderMessages();
        $form = $this->parseChunk($tpl,$plh);

        return $api ? json_encode($this->_plh) : $form;
    }

    public function setFields() {
         if ($this->isSubmitted) {
            foreach ($this->_rq as $key => $value) {
                $this->setField($key,$value);
            }
        }
    }

    public function validateForm() {
        if (!$this->rules) return; //если правил нет, то не проверяем


        include_once(MODX_BASE_PATH.'assets/snippets/FormLister/lib/PHPixie/validate/vendor/autoload.php');
        $validate = new \PHPixie\Validate();
        $validator = $validate->validator();
        $document = $validator->rule()->addDocument();

        //применяем правила
        foreach ($this->rules as $field => $rules) {
            $_field = $document->valueField($field);
            if ($rules['required']) {
                $_field->required();
            }
        }

        $document->allowExtraFields();
        $result = $validator->validate($this->_rq);
        $this->isValid = $result->isValid();
        if (!$this->isValid) {
            foreach ($result->invalidFields() as $fieldResult) {
                foreach ($fieldResult->errors() as $error) {
                    if ($error->type() == 'empty') {
                        $this->_plh['errors']['required'][$fieldResult->path()] = $this->rules[$fieldResult->path()]['required'];
                    };
                }
            }
        }
        return $this->isValid;
    }

    public function getField($field) {
        if (isset($this->_plh['fields'][$field]))
            return $this->_plh['fields'][$field];
    }

    public function setField($field, $value) {
        $this->_plh['fields'][$field] = $value;
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
        $out = '';
        $messages = $this->_plh['messages'];
        $errors = array();
        if (isset($this->_plh['errors']['required'])) {
            $errors = array_merge($errors,array_values($this->_plh['errors']['required']));
        }
        if (isset($this->_plh['errors']['filters'])) {
            $errors = array_merge($errors,array_values($this->_plh['errors']['filters']));
        }
        $splitter = $this->getCFGDef('formMessagesSplitter','<br>');
        $messages = implode($splitter,$messages);
        $errors = implode($splitter,$errors);
        $out = $this->parseChunk($this->getCFGDef('messagesTpl','@CODE:<div class="form-messages">[+messages+]</div>'),array(
            'messages'=>$messages,
            'errors'=>$errors
        ));
        return $out;
    }

    public function addMessage($message = '') {
        if ($message) $this->_plh['errors']['messages'][] = $message;
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
}