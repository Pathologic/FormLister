<?php namespace FormLister;
/**
 * Контроллер для создания записей
 */
if (!defined('MODX_BASE_PATH')) {die();}
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');

class Content extends Form
{
    protected $mode = 'create';
    protected $id = 0;
    /**
     * @var \autoTable $content
     */
    public $content = null;

    public function __construct($modx, $cfg = array())
    {
        parent::__construct($modx, $cfg);
        $lang = $this->lexicon->loadLang('content');
        if ($lang) $this->log('Lexicon loaded',array('lexicon'=>$lang));
        $classname = $this->getCFGDef('contentClass','modResource');
        if ($this->loadContentClass()) {
            $this->content = new $classname($modx);
        }
        $idField = $this->getCFGDef('idField','id');
        if (!$this->getCFGDef($idField) && isset($_REQUEST[$idField]) && is_scalar($_REQUEST[$idField]) && $_REQUEST[$idField] > 0) {
            $this->id = $_REQUEST[$idField];
            $this->mode = 'edit';
            $data = $this->content->edit($this->id)->toArray();
            $this->config->setConfig(array(
                'defaults'=>$data
            ));
            $this->mailConfig['noemail'] = 1;
        }
        $this->log('Content mode is '.$this->mode);
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
        $this->log('Load content class '.$classname,array('result'=>$out));
        return $out;
    }

    public function render() {
        $uid = $this->modx->getLoginUserID('web');
        $mode = $this->mode;
        if (!$uid && $this->getCFGDef('onlyUsers',1) && $mode == 'create') {
            $this->redirect('exitTo');
            $this->renderTpl=$this->getCFGDef('skipTpl',$this->lexicon->getMsg('create.default_skipTpl'));
            //TODO: проверка разрешенных групп
        }
        if (!$uid && $mode == 'edit') {
            $this->redirect('exitTo');
            $this->renderTpl=$this->getCFGDef('skipEditTpl',$this->lexicon->getMsg('edit.default_skipEditTpl'));
            //TODO: проверка разрешенных групп
        }
        if ($mode == 'edit') {
            $owner = $this->getCFGDef('ownerField');
            $cid = $this->content->getID();
            if ($cid) {
                if ($this->getCFGDef('onlyAuthors',1) && ($this->content->get($owner) && $this->content->get($owner) != $uid)) {
                    $this->redirect('badOwnerTo');
                    $this->renderTpl = $this->getCFGDef('badOwnerTpl',$this->lexicon->getMsg('edit.default_badOwnerTpl'));
                } else {
                    return parent::render();
                }
            } else {
                $this->redirect('badRecordTo');
                $this->renderTpl = $this->getCFGDef('badRecordTpl',$this->lexicon->getMsg('edit.default_badRecordTpl'));
            }
        }
        return parent::render();
    }

    public function process() {
        $fields = $this->getContentFields();
        $result = false;
        if ($fields  && !is_null($this->content)) {
            $clearCache = $this->getCFGDef('clearCache',false);
            switch ($this->mode) {
                case 'create':
                    $result = $this->content->create($fields)->save(true,$clearCache);
                    $this->log('Create record',array('data'=>$fields,'result'=>$result));
                    break;
                case 'edit':
                    $result = $this->content->fromArray($fields)->save(true,$clearCache);
                    $this->log('Update record',array('data'=>$fields,'result'=>$result));
                    break;
                default:
                    break;
            }
            //чтобы не получился косяк, когда плагины обновят поля
            $this->content->close();
            $this->setFields($this->content->edit($this->getField('id'))->toArray());
            $this->log('Update form data',array('data'=>$this->getFormData('fields')));
        }
        if (!$result) {
            $this->addMessage($this->lexicon->getMsg('edit.update_fail'));
        } else {
            parent::process();
        }
    }

    public function postProcess()
    {
        $this->setFormStatus(true);
        if ($this->mode == 'create') {
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('successTpl',$this->lexicon->getMsg('create.default_successTpl'));
        } else {
            $this->addMessage($this->lexicon->getMsg('edit.update_success'));
        }
    }

    /**
     * @return array
     */
    public function getContentFields() {
        $fields = array();
        $contentFields = $this->config->loadArray($this->getCFGDef('contentFields'));
        foreach ($contentFields as $field => $formField) {
            $formField = $this->getField($formField);
            if (!empty($formField) || $this->getCFGDef('allowEmptyFields',1)) $fields[$field] = $formField;
        }
        return $fields;
    }

    public function getMode() {
        return $this->mode;
    }
}