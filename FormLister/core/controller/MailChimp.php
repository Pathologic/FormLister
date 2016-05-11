<?php namespace FormLister;

include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/core/controller/Form.php');
include_once (MODX_BASE_PATH . 'assets/snippets/FormLister/lib/MailChimp/MailChimp.php');
class MailChimp extends Form
{
    public function process() {
        $errorMessage = 'Не удалось выполнить подписку.';
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
        $result = $MailChimp->post("lists/$list_id/members", array(
                'email_address' => $this->getField('email'),
                'merge_fields' => array('NAME'=>$this->getField('name')),
                'status'        => 'pending',
        ));
        if(!$MailChimp->getLastError()) {
            $this->addMessage($errorMessage);
        } else {
            $this->setFormStatus(true);
            $this->renderTpl = $this->getCFGDef('successTpl','@CODE:<p>Спасибо, что подписались на нашу рассылку.</p>');
            return true;
        }
    }
}