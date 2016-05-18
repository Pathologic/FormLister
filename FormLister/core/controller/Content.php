<?php namespace FormLister;
/**
 * Контроллер для создания записей
 */
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');

class Content extends Form
{
    public $content = null;

    public function __construct($modx, $cfg = array())
    {
        parent::__construct($modx, $cfg);
        $this->lexicon->loadLang('content');
        $classname = $this->getCFGDef('contentClass','modResource');
        if ($this->loadContentClass()) {
            $this->content = new $classname($modx);
        }
    }

    public function loadContentClass() {
        $out = false;
        $classname = $this->getCFGDef('contentClass','modResource');
        if (!class_exists($classname)) {
            $classPath = $this->getCFGDef('classPath','assets/lib/MODxAPI/modResource.php');
            if ($this->fs->checkFile($classPath)) {
                include_once(MODX_BASE_PATH . $classPath);
                $out = class_exists($classname);
            }
        }  
        return $out;
    }
    
    public function render() {
        if (!$uid = $this->modx->getLoginUserID('web')) {
            $this->redirect('exitTo');
            $this->renderTpl=$this->getCFGDef('skipTpl','@CODE:Только зарегистрированные пользователи могут создавать записи.');
        }
        //TODO: проверка разрешенных групп
        return parent::render();
    }

    public function process() {
        if($this->checkSubmitProtection() || $this->checkSubmitLimit()) return false;
        $fields = $this->getContentFields();
        $result = false;
        if ($fields  && !is_null($this->content)) {
            $result = $this->content->create($fields)->save(true,true);
        }
        if (!$result) {
            $this->addMessage('Не удалось сохранить.');
        } else {
            parent::process();
        }
    }

    public function postProcess()
    {
        $this->setFormStatus(true);
        $this->redirect();
        $this->renderTpl = $this->getCFGDef('successTpl','Успешно сохранено');
    }

    /**
     * @return array
     */
    public function getContentFields() {
        $fields = array();
        $contentFields = $this->config->loadArray($this->getCFGDef('contentFields'));
        foreach ($contentFields as $field => $formField) {
            $formField = $this->getField($formField);
            if (!empty($formField)) $fields[$field] = $formField;
        }
        return $fields;
    }
}