<?php

use DBA\CrackerBinary;
use DBA\CrackerBinaryType;
use DBA\OrderFilter;
use DBA\QueryFilter;
use DBA\Factory;

require_once(dirname(__FILE__) . "/inc/load.php");

if (!Login::getInstance()->isLoggedin()) {
  header("Location: index.php?err=4" . time() . "&fw=" . urlencode($_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']));
  die();
}

AccessControl::getInstance()->checkPermission(DViewControl::CRACKERS_VIEW_PERM);

$TEMPLATE = new Template("crackers/index");
$MENU->setActive("crackers_list");

//catch actions here...
if (isset($_POST['action']) && CSRF::check($_POST['csrf'])) {
  $crackerHandler = new CrackerHandler();
  $crackerHandler->handle($_POST['action']);
  if (UI::getNumMessages() == 0) {
    Util::refresh();
  }
}

if (isset($_GET['new']) && isset($_GET['id']) && AccessControl::getInstance()->hasPermission(DAccessControl::CRACKER_BINARY_ACCESS)) {
  $binaryType = Factory::getCrackerBinaryTypeFactory()->get($_GET['id']);
  if ($binaryType !== null) {
    UI::add('binaryType', $binaryType);
    $TEMPLATE = new Template("crackers/newVersion");
    UI::add('pageTitle', "Add new Cracker Binary Version");
  }
}
else if (isset($_GET['new']) && AccessControl::getInstance()->hasPermission(DAccessControl::CRACKER_BINARY_ACCESS)) {
  $TEMPLATE = new Template("crackers/new");
  $MENU->setActive("crackers_new");
  UI::add('pageTitle', "Add Cracker Binary");
}
else if (isset($_GET['edit']) && AccessControl::getInstance()->hasPermission(DAccessControl::CRACKER_BINARY_ACCESS)) {
  $binary = Factory::getCrackerBinaryFactory()->get($_GET['id']);
  if ($binary !== null) {
    UI::add('binary', $binary);
    $TEMPLATE = new Template("crackers/editVersion");
    $MENU->setActive("crackers_edit");
    UI::add('binaryType', Factory::getCrackerBinaryTypeFactory()->get($binary->getCrackerBinaryTypeId()));
    UI::add('pageTitle', "Edit Cracker Binary Version for " . UI::get('binaryType')->getTypeName());
  }
}
else if (isset($_GET['id'])) {
  $binaryType = Factory::getCrackerBinaryTypeFactory()->get($_GET['id']);
  if ($binaryType !== null) {
    UI::add('binaryType', $binaryType);
    $TEMPLATE = new Template("crackers/detail");
    $qF = new QueryFilter(CrackerBinary::CRACKER_BINARY_TYPE_ID, $binaryType->getId(), "=");
    UI::add('binaries', Factory::getCrackerBinaryFactory()->filter([Factory::FILTER => $qF]));
    UI::add('pageTitle', "Cracker Binary details for " . $binaryType->getTypeName());
  }
}
else {
  $oF = new OrderFilter(CrackerBinaryType::TYPE_NAME, "ASC");
  UI::add('binaryTypes', Factory::getCrackerBinaryTypeFactory()->filter([Factory::ORDER => $oF]));
  $binariesVersions = new DataSet();
  foreach (UI::get('binaryTypes') as $binaryType) {
    $qF = new QueryFilter(CrackerBinary::CRACKER_BINARY_TYPE_ID, $binaryType->getId(), "=");
    $binaries = Factory::getCrackerBinaryFactory()->filter([Factory::FILTER => $qF]);
    $arr = array();
    usort($binaries, ["Util", "versionComparisonBinary"]);
    foreach ($binaries as $binary) {
      if (!isset($arr[$binary->getVersion()])) {
        $arr[$binary->getVersion()] = $binary->getVersion();
      }
    }
    $binariesVersions->addValue($binaryType->getId(), implode("<br>", $arr));
  }
  UI::add('versions', $binariesVersions);
  UI::add('pageTitle', "Cracker Binaries");
}

echo $TEMPLATE->render(UI::getObjects());




