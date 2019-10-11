<?php
$files = scandir('app/class/');
$files = array_diff($files, array('.', '..'));
foreach($files as $file) {
	include_once 'app/class/'.  $file;
}

class Automation extends Template
{
	function __construct()
	{
		global $userManager;
		global $langMng;

		if (!$userManager->isLogin()){
			header('Location: ' . BASEDIR . 'login');
		}

		$automations = [];
		$automationsData = AutomationManager::getAll();
		foreach ($automationsData as $automationKey => $automationData) {
			$doSomething = [];
			foreach (json_decode($automationData['do_something']) as $deviceId => $subDeviceState) {
				$subDeviceMasterDeviceData = DeviceManager::getDeviceById($deviceId);
				$doSomething[$deviceId] = [
					'name' => $subDeviceMasterDeviceData['name'],
					'state' => $subDeviceState,
				];
			}
			$automations[$automationData['automation_id']] = [
				'name' => $automationData['name'],
				'onDays' => json_decode($automationData['on_days']),
				'ifSomething' => $automationData['if_something'],
				'doSomething' => $doSomething,
				'active' => $automationData['active'],
			];
		}

		$approvedSubDevices = [];
		$allDevicesData = DeviceManager::getAllDevices();
		foreach ($allDevicesData as $deviceKey => $deviceValue) {
			if (!$deviceValue['approved']) continue;
			$allSubDevicesData = SubDeviceManager::getAllSubDevices($deviceValue['device_id']);
			foreach ($allSubDevicesData as $subDeviceKey => $subDeviceValue) {
				$approvedSubDevices[$subDeviceValue['subdevice_id']] = [
					'name' => $allDevicesData[$deviceKey]['name'],
					'type' => $subDeviceValue['type'],
					'masterDevice' => $subDeviceValue['device_id'],
				];
			}
		}

		$template = new Template('automation');
		$template->prepare('baseDir', BASEDIR);
		$template->prepare('title', 'Automation');
		$template->prepare('langMng', $langMng);
		$template->prepare('automations', $automations);
		$template->prepare('subDevices', $approvedSubDevices);

		$template->render();
	}
}
