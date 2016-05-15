<?php namespace FormLister;
/**
 * Контроллер для обычных форм с отправкой, типа обратной связи
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/lib/Mailer.php');
class Form extends Core
{
    /**
     * Настройки для отправки почты
     * @var array
     */
    public $mailConfig = array();

    public function __construct(\DocumentParser $modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
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
        $this->lexicon->loadLang('form');
    }

    /**
     * Проверка повторной отправки формы
     * @return bool
     */
    public function checkSubmitProtection()
    {
        $result = false;
        if ($protectSubmit = $this->getCFGDef('protectSubmit', 1)) {
            $hash = $this->getFormHash();
            if (isset($_SESSION[$this->formid . '_hash']) && $_SESSION[$this->formid . '_hash'] == $hash && $hash != '') {
                $result = true;
                $this->addMessage($this->lexicon->getMsg('form.protectSubmit'));
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
        if ($submitLimit > 0) {
            if (time() < $submitLimit + $_SESSION[$this->formid . '_limit']) {
                $result = true;
                $this->addMessage('[%form.submitLimit%] ' . round($submitLimit / 60, 0) . ' [%form.minutes%].');
            } else {
                unset($_SESSION[$this->formid . '_limit'], $_SESSION[$this->formid . '_hash']);
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
     * Формирует текст письма для отправки
     * Если основной шаблон письма не задан, то формирует список полей формы
     * @param string $tplParam имя параметра с шаблоном письма
     * @return null|string
     */
    public function renderReport($tplParam = 'reportTpl')
    {
        $out = '';
        $tpl = $this->getCFGDef($tplParam);
        if (empty($tpl) && $tplParam == 'reportTpl') {
            $tpl = '@CODE:';
            foreach($this->getFormData('fields') as $key => $value) {
                $tpl .= "[+{$key}+]: [+{$key}.value+]".PHP_EOL;
            }
        } else {
            $out = $this->parseChunk($tpl, $this->prerenderForm(true));
        }
        return $out;
    }

    /**
     * Получает тему письма из шаблона или строки
     * @return mixed|null|string
     */
    public function renderSubject() {
        $subject = $this->getCFGDef('subjectTpl');
        if (!empty($subject)) {
            $subject = $this->parseChunk($subject,$this->getFormData('fields'));
        } else {
            $subject = $this->getCFGDef('subject');
        }
        return $subject;
    }

    public function getAttachments() {
        return array(); //TODO
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
        $out = $mailer->send($this->renderReport());
        //TODO debug
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
            $out = $mailer->send($this->renderReport('automessageTpl'));
            //TODO debug
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
            $out = $mailer->send($this->renderReport('ccSenderTpl'));
            //TODO debug
            return $out;
        }
    }

    public function process() {
        //если сработала защита, то не отправляем
        if($this->checkSubmitProtection() || $this->checkSubmitLimit()) return false;

        $this->setField('form.date',date($this->getCFGDef('dateFormat',$this->lexicon->getMsg('form.dateFormat'))));
        if ($this->sendReport()) {
            $this->setFormStatus(true);
            $this->setSubmitProtection();
            $this->sendCCSender();
            $this->sendAutosender();
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('successTpl',$this->lexicon->getMsg('form.default_successTpl'));
        }
    }
}