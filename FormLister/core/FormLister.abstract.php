<?php namespace FormLister;

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
include_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
require_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/DLTemplate.class.php");
include_once(MODX_BASE_PATH . "assets/snippets/FormLister/lib/Config.php");

/**
 * Class FormLister
 * @package FormLister
 */
abstract class Core
{
    /**
     * @var array
     * Массив $_REQUEST
     */
    protected $_rq = array();

    protected $modx = null;

    protected $fs = null;

    /**
     * Идентификатор формы
     * @var mixed|string
     */
    protected $formid = '';

    public $config = null;

    /**
     * Шаблон для вывода по правилам DocLister
     * @var string
     */
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

    protected $validator = null;

    /**
     * Массив с правилами валидации полей
     * @var array
     */
    protected $rules = array();

    /**
     * Если данные из формы отправлены, то true
     * @var bool
     */
    protected $isSubmitted = false;

    /**
     * Массив с именами полей, которые можно отправлять в форме
     * По умолчанию все поля разрешены
     * @var array
     */
    public $allowedFields = array();

    /**
     * Массив с именами полей, которые запрещено отправлять в форме
     * @var array
     */
    public $forbiddenFields = array();

    public function __construct($modx, $cfg = array())
    {
        $this->modx = $modx;
        $this->config = new \Helpers\Config($cfg);
        $this->fs = \Helpers\FS::getInstance();
        if (isset($cfg['config'])) {
            $this->config->loadConfig($cfg['config']);
        }
        $this->formid = $this->getCFGDef('formid');
    }

    /**
     * Установка значений в formData
     * Установка шаблона формы
     * Загрузка капчи
     */
    public function initForm() {
        $this->allowedFields = $this->getCFGDef('allowedFields') ? explode(',',$this->getCFGDef('allowedFields')) : array();
        if ($this->allowedFields) $this->allowedFields[] = 'formid';
        $this->disallowedFields = $this->getCFGDef('forbiddenFields') ? explode(',',$this->getCFGDef('forbiddenFields')) : array();
        if (!$this->isSubmitted) $this->setExternalFields($this->getCFGDef('defaultsSources','array'));
        if ($this->setRequestParams(array_merge($_GET, $_POST))) {
            $this->setFields($this->_rq);
            if ($this->getCFGDef('keepDefaults')) $this->setExternalFields($this->getCFGDef('defaultsSources','array')); //восстановить значения по умолчанию
        }
        $this->renderTpl = $this->formid ? $this->getCFGDef('formTpl') : '@CODE:'; //Шаблон по умолчанию
        $this->initCaptcha();
        $this->runPrepare();
    }

    /**
     * Загружает в formData данные не из формы
     * @param string $sources список источников
     * @param string $arrayParam название параметра с данными
     */
    public function setExternalFields($sources = 'array', $arrayParam = 'defaults') {
        $sources = explode(',',$sources);
        $fields = array();
        foreach ($sources as $source) {
            switch ($source) {
                case 'array':
                    if ($arrayParam) {
                        $fields = array_merge($fields,$this->config->loadArray($this->getCFGDef($arrayParam)));
                        $prefix = '';
                    }
                    break;
                case 'session':
                    $_source = explode(':',$source);
                    $fields = isset($_source[1]) && isset($_SESSION[$_source[1]]) ?
                        array_merge($fields,$_SESSION[$_source[1]]) :
                        array_merge($fields, $_SESSION);
                    $prefix = 'session';
                    break;
                case 'plh':
                    $_source = explode(':',$source);
                    $fields = isset($_source[1]) && isset($this->modx->placeholders[$_source[1]]) ?
                        array_merge($fields,$this->modx->placeholders[$_source[1]]) :
                        array_merge($fields, $this->modx->placeholders);
                    $prefix = 'plh';
                    break;
                case 'config':
                    $fields = array_merge($fields,$this->modx->config);
                    $prefix = 'config';
                    break;
                case 'cookie':
                    $_source = explode(':',$source);
                    $fields = isset($_source[1]) && isset($_COOKIE[$_source[1]]) ?
                        array_merge($fields,$_COOKIE[$_source[1]]) :
                        array_merge($fields, $_COOKIE);
                    $prefix = 'cookie';
                    break;
                default:
                    $_source = explode(':',$source);
                    $classname = $_source[0];
                    if (class_exists($classname) && isset($_source[1])) {
                        $obj = new $classname($this->modx);
                        if ($data = $obj->edit($_source[1])) {
                            $fields = array_merge($fields,$data->toArray());
                            $prefix = $classname;
                        }
                    }
            }
        }
        $this->setFields($fields,$this->getCFGDef('extPrefix') ? $prefix : '');
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
     * Получение информации из конфига
     *
     * @param string $name имя параметра в конфиге
     * @param mixed $def значение по умолчанию, если в конфиге нет искомого параметра
     * @return mixed значение из конфига
     */
    public function getCFGDef($name, $def = null)
    {
        return $this->config->getCFGDef($name, $def);
    }

    /*
     * Сценарий работы
     * Если форма отправлена, то проверяем данные
     * Если проверка успешна, то обрабатываем данные
     * Выводим шаблон
     */
    public function render()
    {
        if ($this->isSubmitted) {
            $this->validateForm();
            if ($this->isValid()) {
                $this->process();
                if ($this->getCFGDef('saveObject'))
                    $this->saveObject();
            }
        }
        return $this->renderForm();
    }

    /**
     * Готовит данные для вывода в шаблоне
     * @param bool $convertArraysToStrings
     * @return array
     */
    public function prerenderForm($convertArraysToStrings = false) {
        $plh = array_merge(
            $this->fieldsToPlaceholders($this->getFormData('fields'), 'value', $this->getFormData('status') || $convertArraysToStrings),
            $this->controlsToPlaceholders(),
            $this->errorsToPlaceholders(),
            array(
                'form.messages' => $this->renderMessages(),
                'captcha'=>$this->getField('captcha')
            )
        );
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
        $form = $this->parseChunk($this->renderTpl, $this->prerenderForm());
        /*
         * Если api = 0, то возвращается шаблон
         * Если api = 1, то возвращаются данные формы
         * Если api = 2, то возвращаются данные формы и шаблон
         */
        if (!$api) {
            $out = $form;
        } else {
            $out = $this->getFormData();
            if ($api == 2) $out['output'] = $form;
            $out = json_encode($out);
        }
        return $out;
    }

    /**
     * Загружает данные в formData
     * @param array $fields массив полей
     * @param string $prefix добавляет префикс к имени поля
     */
    public function setFields($fields = array(),$prefix = '')
    {
        foreach ($fields as $key => $value) {
            //список рарешенных полей существует и поле в него входит; или списка нет, тогда пофиг
            $allowed = !empty($this->allowedFields) ? in_array($key, $this->allowedFields) : true;
            //поле входит в список запрещенных полей
            $forbidden = in_array($key,$this->forbiddenFields);
            if (($allowed && !$forbidden) && !empty($value)) {
                if ($prefix) $key = implode('.',array($prefix,$key));
                $this->setField($key, $value);
            }
        }
    }

    /**
     * Загружает класс-валидатор и создает его экземпляр
     * @return Validator|null
     */
    public function initValidator() {
        $validator = $this->getCFGDef('validator','\FormLister\Validator');
        if (!class_exists($validator)) {
            include_once(MODX_BASE_PATH . 'assets/snippets/FormLister/lib/Validator.php');
        }
        $this->validator = new $validator();
        return $this->validator;
    }

    /**
     * Возвращает результат проверки полей
     * @return bool
     */
    public function validateForm()
    {
        $validator = $this->initValidator();
        $this->getValidationRules();
        if (!$this->rules || is_null($validator)) {
            return true;
        } //если правил нет, то не проверяем

        //применяем правила
        foreach ($this->rules as $field => $rules) {
            $_field = $this->getField($field);
            $params = array($_field);
            foreach ($rules as $rule => $description) {
                if (is_array($description)) {
                    $params = array_merge($params,is_array($description['params']) ? $description['params'] : array($description['params']));
                    $message = isset($description['message']) ? $description['message'] : 'Заполнено неверно.';
                } else {
                    $message = $description;
                }
                if (is_scalar($rule) && ($rule != 'custom') && method_exists($validator, $rule)) {
                    $result = call_user_func_array(array($validator, $rule), $params);
                } else {
                    if (isset($description['rule'])) $_rule = $description['rule'];
                    if ((is_object($_rule) && ($_rule instanceof Closure)) || is_callable($_rule)) {
                        $result = call_user_func_array($_rule, $params);
                    }
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
        return $this->isValid();
    }

    /**
     * Возвращает массив formData или его часть
     * @param string $section
     * @return array
     */
    public function getFormData($section = '')
    {
        if ($section && isset($this->formData[$section])) {
            $out = $this->formData[$section];
        } else {
            $out = $this->formData;
        }
        return $out;
    }

    /**
     * Устанавливает статус формы, если true, то форма успешно обработана
     * @param $status
     */
    public function setFormStatus($status)
    {
        $this->formData['status'] = (bool)$status;
    }

    /**
     * Возвращает значение поля из formData
     * @param $field
     * @return string
     */
    public function getField($field)
    {
        return isset($this->formData['fields'][$field]) ? $this->formData['fields'][$field] : '';
    }

    /**
     * Сохраняет значение поля в formData
     * @param $field имя поля
     * @param $value значение поля
     */
    public function setField($field, $value)
    {
        $this->formData['fields'][$field] = $value;
    }

    /**
     * Добавляет в formData информацию об ошибке
     * @param $field имя поля
     * @param $type тип ошибки
     * @param $message сообщение об ошибке
     */
    public function addError($field, $type, $message)
    {
        $this->formData['errors'][$field][$type] = $message;
    }

    /**
     * Добавляет сообщение в formData
     * @param string $message
     */
    public function addMessage($message = '')
    {
        if ($message) {
            $this->formData['messages'][] = $message;
        }
    }

    /**
     * Готовит данные для вывода в шаблон
     * @param array $fields массив с данными
     * @param string $suffix добавляет суффикс к имени поля
     * @param bool $split преобразование массивов в строки
     * @return array
     */
    public function fieldsToPlaceholders($fields = array(), $suffix = '', $split = false)
    {
        $plh = $fields;
        if (is_array($fields) && !empty($fields)) {
            foreach ($fields as $field => $value) {
                $field = array($field, $suffix);
                $field = implode('.', array_filter($field));
                if ($split && is_array($value)) {
                    $arraySplitter = $this->getCFGDef($field.'Splitter',$this->getCFGDef('arraySplitter','; '));
                    $value = implode($arraySplitter, $value);
                }
                $plh[$field] = \APIhelpers::e($value);
            }
        }
        return $plh;
    }

    /**
     * Готовит сообщения об ошибках для вывода в шаблон
     * @return array
     */
    public function errorsToPlaceholders()
    {
        $plh = array();
        foreach ($this->getFormData('errors') as $field => $error) {
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

    /**
     * Обработка чекбоксов, селектов, радио-кнопок перед выводом в шаблон
     * @return array
     */
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
     * Загрузка правил валидации
     */
    public function getValidationRules()
    {
        $rules = $this->getCFGDef('rules', '');
        $rules = $this->config->loadArray($rules);
        if ($rules) $this->rules = array_merge($this->rules,$rules);
    }

    /**
     * Готовит сообщения из formData для вывода в шаблон
     * @return null|string
     */
    public function renderMessages()
    {
        $out = '';
        $formMessages = $this->getFormData('messages');
        $formErrors = $this->getFormData('errors');

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
        $wrapper = $this->getCFGDef('messagesTpl', '@CODE:<div class="form-messages">[+messages+]</div>');
        if (!empty($formMessages) || !empty($formErrors)) {
            $out = $this->parseChunk($wrapper,
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
        }
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

    public function parseChunk($name, $data, $parseDocumentSource = false)
    {
        $out = null;
        $out = \DLTemplate::getInstance($this->modx)->parseChunk($name, $data, $parseDocumentSource);
        return $out;
    }

    /**
     * Загружает класс капчи
     */
    public function initCaptcha()
    {
        if ($captcha = $this->getCFGDef('captcha')) {
            $wrapper = MODX_BASE_PATH . "assets/snippets/FormLister/lib/captcha/{$captcha}/wrapper.php";
            if ($this->fs->checkFile($wrapper)) {
                include_once($wrapper);
                $wrapper = $captcha.'Wrapper';
                $captcha = new $wrapper ($this);
                $this->rules[$this->getCFGDef('captchaField', 'vericode')] = $captcha->getRule();
                $this->setField('captcha',$captcha->getPlaceholder());
            }
        }
    }

    public function getMODX() {
        return $this->modx;
    }

    public function getFormId() {
        return $this->formid;
    }

    public function isValid() {
        return !count($this->getFormData('errors'));
    }

    public function saveObject() {
        $this->modx->setPlaceholder($this->getCFGDef('saveObject'),$this);
    }

    public function runPrepare() {
        if (($prepare = $this->getCFGDef('prepare')) != '') {
            if(is_scalar($prepare)){
                $names = explode(",", $prepare);
                foreach($names as $item){
                    $this->callPrepare($item);
                }
            }else{
                $this->callPrepare($prepare);
            }
        }
    }

    public function callPrepare($name) {
        if (empty($name)) return;
        if((is_object($name) && ($name instanceof Closure)) || is_callable($name)){
            call_user_func($name, $this);
        }else{
            $params = array(
                'modx' => $this->modx,
                'FormLister' => $this
            );
            $this->modx->runSnippet($name, $params);
        }
    }

    /**
     * Обработка формы, определяется контроллерами
     *
     * @return mixed
     */
    abstract public function process();
}