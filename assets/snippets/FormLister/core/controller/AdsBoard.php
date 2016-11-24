<?php namespace FormLister;

/**
 * Контроллер для создания записей
 */
include_once(MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Content.php');

/**
 * Class Content
 * @package FormLister
 */
class AdsBoard extends Content
{
    protected $mode = 'create';
    protected $id = 0;
    protected $owner = 0;
    /**
     * @var \autoTable $content
     */
    public $content = null;
    public $user = null;

    /**
     * Content constructor.
     * @param \DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(\DocumentParser $modx, $cfg = array())
    {
        $cfg['model'] = '\\AdsBoard\\Content';
        $cfg['modelPath'] = 'assets/plugins/adsboard/core/model/Ads.php';
        $cfg['ownerField'] = 'ab_owner';
        parent::__construct($modx, $cfg);
    }

    /**
     *
     */
    public function process()
    {
        $fields = $this->getContentFields();
        $owner = $this->getCFGDef('ownerField', 'aid');
        $result = false;
        if (is_array($fields['upload'])) $fields['upload'] = json_encode($fields['upload']);
        if ($fields && !is_null($this->content)) {
            $clearCache = $this->getCFGDef('clearCache', false);
            switch ($this->mode) {
                case 'create':
                    if ($this->checkSubmitProtection() || $this->checkSubmitLimit()) {
                        return;
                    }
                    if ($this->owner) {
                        $fields[$owner] = $this->owner;
                    }
                    $result = $this->content->create($fields)->save(true, $clearCache);
                    $this->log('Create record', array('data' => $fields, 'result' => $result));
                    break;
                case 'edit':
                    $result = $this->content->fromArray($fields)->save(true, $clearCache);
                    $this->log('Update record', array('data' => $fields, 'result' => $result));
                    break;
                default:
                    break;
            }
            //чтобы не получился косяк, когда плагины обновят поля
            $this->content->close();
            $this->setFields($this->content->edit($this->id)->toArray());
            $images = $this->getField('images');
            foreach ($images as &$image) {
                $image['thumb'] = $this->modx->runSnippet('sgThumb',array(
                    'input'=>$image['file'],
                    'options'=>'thumbs'
                ));
            }
            $this->setField('images',$images);
            $this->log('Update form data', array('data' => $this->getFormData('fields')));
        }
        if (!$result) {
            $this->addMessage($this->lexicon->getMsg('edit.update_fail'));
        } else {
            if ($this->mode == 'create') {
                $url = '';
                $evtOut = $this->modx->invokeEvent('OnMakeDocUrl', array(
                    'id'   => $result,
                    'data' => $this->getFormData('fields')
                ));
                if (is_array($evtOut) && count($evtOut) > 0) {
                    $url = array_pop($evtOut);
                }
                if ($url) {
                    $this->setField('content.url', $url);
                }
            }
            Form::process();
        }
    }

    /**
     *
     */
    public function postProcess()
    {
        $this->setFormStatus(true);
        if ($this->mode == 'create') {
            $this->redirect();
            $this->renderTpl = $this->getCFGDef('successTpl', $this->lexicon->getMsg('create.default_successTpl'));
        } else {
            $this->addMessage($this->lexicon->getMsg('edit.update_success'));
        }
    }
}
