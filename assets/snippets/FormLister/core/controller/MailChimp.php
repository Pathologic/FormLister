<?php namespace FormLister;

if (!defined('MODX_BASE_PATH')) {die();}
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/FormLister.abstract.php');
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/lib/MailChimp/MailChimp.php');
class MailChimp extends Core
{
    public function __construct(\DocumentParser $modx, array $cfg)
    {
        parent::__construct($modx, $cfg);
        $this->lexicon->loadLang('mailchimp');
    }

    public function process() {
        $errorMessage = $this->lexicon->getMsg('mc.subscription_failed');
        if (!$this->getCFGDef('apiKey')) {
            $this->addMessage($errorMessage);
            return false;
        }
        $MailChimp = new \DrewM\MailChimp\MailChimp($this->getCFGDef('apiKey'));
        $list_id = $this->getCFGDef('listId');
        if (!$list_id) {
            $this->addMessage($errorMessage);
            return false;
        }
        
        $MailChimp->post("lists/$list_id/members", array(
                'email_address' => $this->getField('email'),
                'merge_fields' => array('NAME'=>$this->getField('name')),
                'status'        => 'pending',
        ));
        if(!$MailChimp->getLastError()) {
            $this->addMessage($errorMessage);
        } else {
            $this->setFormStatus(true);
            $this->renderTpl = $this->getCFGDef('successTpl',$this->lexicon->getMsg('mc.default_successTpl'));
            return true;
        }
    }
}