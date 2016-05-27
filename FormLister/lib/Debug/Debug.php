<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 24.05.2016
 * Time: 12:59
 */
include_once ('Kint/Kint.class.php');
class Debug {
    protected $modx = null;
    private $log = array();
    private $timeStart = array();

    public function __construct(DocumentParser $modx){
        $this->modx = $modx;
        $this->timeStart = microtime(true);
    }

    public function log($message, $data) {
        $this->log[] = array('message' => $message, 'data' => @Kint::dump($data), 'time' => microtime(true) - $this->timeStart);
    }

    public function saveLog() {
        $out = '';
        foreach ($this->log as $entry) {

        }
        if ($out) $this->modx->logEvent(0, 1, $out, $this->modx->event)
    }
}