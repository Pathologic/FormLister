<?php namespace FormLister;
/**
 * Контроллер для обычных форм с отправкой, типа обратной связи
 */
if (!defined('MODX_BASE_PATH')) {die();}
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/lib/Mailer.php');
class Form extends Core
{
    /**
     * Настройки для отправки почты
     * @var array
     */
    public $mailConfig = array();

    /**
     * Правила валидации файлов
     * @var array
     */
    protected $fileRules = array();

    /**
     * Массив с данными о файлах
     * @var array
     */
    protected $files = array();

    public function __construct(\DocumentParser $modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
        if ($files = $this->getCFGDef('attachments')) {
            $this->files = $this->filesToArray($_FILES,explode(',',$files));
        }
        $this->mailConfig = array(
            'isHtml' => $this->getCFGDef('isHtml',1),
            'to' => $this->getCFGDef('to'),
            'from' => $this->getCFGDef('from',$this->modx->config['emailsender']),
            'fromName' => $this->getCFGDef('fromName',$this->modx->config['site_name']),
            'subject' => $this->getCFGDef('subject'),
            'replyTo' => $this->getCFGDef('replyTo'),
            'cc' => $this->getCFGDef('cc'),
            'bcc' => $this->getCFGDef('bcc'),
            'noemail' => $this->getCFGDef('noemail',false)
        );
        $lang = $this->lexicon->loadLang('form');
        if ($lang) $this->log('Lexicon loaded',array('lexicon'=>$lang));
    }

    /**
     * Проверка повторной отправки формы
     * @return bool
     */
    public function checkSubmitProtection()
    {
        $result = false;
        if ($this->isSubmitted() && $this->getCFGDef('protectSubmit', 1)) {
            $hash = $this->getFormHash();
            if (isset($_SESSION[$this->formid . '_hash']) && $_SESSION[$this->formid . '_hash'] == $hash && $hash != '') {
                $result = true;
                $this->addMessage($this->lexicon->getMsg('form.protectSubmit'));
                $this->log('Submit protection enabled');
            }
        }
        return $result;
    }

    /**
     * Проверка повторной отправки в течение определенного времени, в секундах
     * @return bool
     */
    public function checkSubmitLimit()
    {
        $submitLimit = $this->getCFGDef('submitLimit', 60);
        $result = false;
        if ($this->isSubmitted() && $submitLimit > 0) {
            if (time() < $submitLimit + $_SESSION[$this->formid . '_limit']) {
                $result = true;
                $this->addMessage('[%form.submitLimit%] ' . round($submitLimit / 60, 0) . ' [%form.minutes%].');
                $this->log('Submit limit enabled');
            } else {
                unset($_SESSION[$this->formid . '_limit']);
            } //time expired
        }
        return $result;
    }

    public function setSubmitProtection()
    {
        if ($this->getCFGDef('protectSubmit', 1)) {
            $_SESSION[$this->formid . '_hash'] = $this->getFormHash();
        } //hash is set earlier
        if ($this->getCFGDef('submitLimit', 60) > 0) {
            $_SESSION[$this->formid . '_limit'] = time();
        }
    }

    public function getFormHash()
    {
        $hash = array();
        $protectSubmit = $this->getCFGDef('protectSubmit', 1);
        if (!is_numeric($protectSubmit)) { //supplied field names
            $protectSubmit = explode(',', $protectSubmit);
            foreach ($protectSubmit as $field) {
                $hash[] = $this->getField(trim($field));
            }
        } else //all required fields
        {
            foreach ($this->rules as $field => $rules) {
                foreach ($rules as $rule => $description) {
                    if ($rule == 'required') {
                        $hash[] = $this->getField($field);
                    }
                }
            }
        }
        if ($hash) {
            $hash = md5(json_encode($hash));
        }
        return $hash;
    }

    /**
     * @param array $_files
     * @return array
     */
    public function filesToArray(array $_files, array $allowed, $flag = true) {
        $files = array();
        foreach($_files as $name=>$file){
            if (!in_array($name, $allowed) && !is_int($name)) continue;
            if($flag) $sub_name = $file['name'];
            else    $sub_name = $name;
            if(is_array($sub_name)){
                foreach(array_keys($sub_name) as $key){
                    $files[$name][$key] = array(
                        'name'     => $file['name'][$key],
                        'type'     => $file['type'][$key],
                        'tmp_name' => $file['tmp_name'][$key],
                        'error'    => $file['error'][$key],
                        'size'     => $file['size'][$key],
                    );
                    $files[$name] = $this->filesToArray($files[$name], $allowed, false);
                }
            }else{
                $files[$name] = $file;
            }
        }
        return $files;
    }

    public function validateForm() {
        parent::validateForm();
        $validator = $this->getCFGDef('fileValidator','\FormLister\FileValidator');
        if (!class_exists($validator)) {
            include_once(MODX_BASE_PATH . 'assets/snippets/FormLister/lib/FileValidator.php');
        }
        $validator = new $validator();
        $fields = $this->files;
        $rules = $this->getValidationRules('fileRules');
        $this->fileRules = array_merge($this->fileRules,$rules);
        $this->log('Prepare to validate files',array('fields'=>$fields,'rules'=>$this->fileRules));
        $result = $this->validate($validator, $this->fileRules, $fields);
        if ($result !== true) {
            foreach ($result as $item) {
                $this->addError($item[0],$item[1],$item[2]);
            }
            $this->log('File validation errors',$this->getFormData('errors'));
        }
        return $this->isValid();
    }

    /**
     * Формирует текст письма для отправки
     * Если основной шаблон письма не задан, то формирует список полей формы
     * @param string $tplParam имя параметра с шаблоном письма
     * @return null|string
     */
    public function renderReport($tplParam = 'reportTpl')
    {
        $tpl = $this->getCFGDef($tplParam);
        if (empty($tpl) && $tplParam == 'reportTpl') {
            $tpl = '@CODE:';
            foreach($this->getFormData('fields') as $key => $value) {
                $tpl .= "[+{$key}+]: [+{$key}.value+]".PHP_EOL;
            }
        }
        $out = $this->parseChunk($tpl, $this->prerenderForm(true));
        return $out;
    }

    /**
     * Получает тему письма из шаблона или строки
     * @return mixed|null|string
     */
    public function renderSubject() {
        $subject = $this->getCFGDef('subjectTpl');
        if (!empty($subject)) {
            $subject = $this->parseChunk($subject,$this->prerenderForm(true));
        } else {
            $subject = $this->getCFGDef('subject');
        }
        return $subject;
    }

    public function getAttachments() {
        $attachments = array();
        foreach ($this->files as $files) {
            if (is_null($files[0])) $files = array($files);
            foreach ($files as $file) {
                $attachments[] = array('filepath'=>$file['tmp_name'],'filename'=>$file['name']);
            }
        }
        return $attachments;
    }

    /**
     * Оправляет письмо
     * @return mixed
     */
    public function sendReport() {
        $mailer = new \Helpers\Mailer($this->modx,array_merge(
            $this->mailConfig,
            array('subject'=>$this->renderSubject())
        ));
        $attachments = $this->getAttachments();
        if ($attachments) {
            $mailer->attachFiles($attachments);
            $field = array();
            foreach ($attachments as $file) $field[] = $file['filename'];
            $this->setField('attachments',$field);
        }
        $report = $this->renderReport();
        $out = $mailer->send($report);
        $this->log('Mail report',array('report'=>$report,'mailer_config'=>$mailer->config,'result'=>$out));
        return $out;
    }

    /**
     * Оправляет копию письма на указанный адрес
     * @return mixed
     */
    public function sendAutosender() {
        $to = $this->getCFGDef('autosender');
        if (!empty($to)) {
            $mailer = new \Helpers\Mailer($this->modx,array_merge(
                $this->mailConfig,
                array(
                    'subject'=>$this->renderSubject(),
                    'to' => $to,
                    'fromName' => $this->getCFGDef('autosenderFromName',$this->modx->config['site_name'])
                )
            ));
            $report = $this->renderReport('automessageTpl');
            $out = $mailer->send($report);
            $this->log('Mail autosender report',array('report'=>$report,'mailer_config'=>$mailer->config,'result'=>$out));
            return $out;
        }
    }

    /**
     * Отправляет копию письма на адрес из поля email
     * @return mixed
     */
    public function sendCCSender() {
        $to = $this->getField($this->getCFGDef('ccSenderField','email'));
        if (!empty($to) && $this->getCFGDef('ccSender',0)) {
            $mailer = new \Helpers\Mailer($this->modx,array_merge(
                $this->mailConfig,
                array(
                    'subject'=>$this->renderSubject(),
                    'to' => $to,
                    'fromName' => $this->getCFGDef('ccSenderFromName',$this->modx->config['site_name'])
                )
            ));
            $report = $this->renderReport('ccSenderTpl');
            $out = $mailer->send($report);
            $this->log('Mail CC report',array('report'=>$report,'mailer_config'=>$mailer->config,'result'=>$out));
            return $out;
        }
    }

    public function render()
    {
        if ($this->isSubmitted() && $this->checkSubmitLimit()) {
            return $this->renderForm();
        }
        return parent::render();
    }

    public function process() {
        $this->setField('form.date',date($this->getCFGDef('dateFormat',$this->lexicon->getMsg('form.dateFormat'))));
        //если защита сработала, то ничего не отправляем
        if ($this->checkSubmitProtection()) return;
        if ($this->sendReport()) {
            $this->setSubmitProtection();
            $this->sendCCSender();
            $this->sendAutosender();
            $this->postProcess();
        } else {
            $this->addMessage($this->lexicon->getMsg('form.form_failed'));
        }
    }

    public function postProcess() {
        $this->setFormStatus(true);
        $this->redirect();
        $this->renderTpl = $this->getCFGDef('successTpl',$this->lexicon->getMsg('form.default_successTpl'));
    }
}