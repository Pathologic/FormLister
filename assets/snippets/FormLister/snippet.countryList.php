<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 25.06.2016
 * Time: 18:52
 */
$file = MODX_MANAGER_PATH."/includes/lang/country/{$FormLister->getCFGDef('countryLang','russian-UTF8')}_country.inc.php";
if (!file_exists($file) || !is_readable($file)) $file = MODX_MANAGER_PATH."/includes/lang/country/russian-UTF8_country.inc.php";
$out = '';
include($file);
foreach ($_country_lang as $key => $value) {
    $out .= '<option value="'.$key.'"'.($FormLister->getField($FormLister->getCFGDef('countryField','country')) == $key ? ' selected' : '').'>'.$value.'</option>';
};
$FormLister->setField('countryList.value',$out);