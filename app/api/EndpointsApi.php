<?php
class EndpointsApi extends ApiController{

	public function default(){
		// $this->requireAuth();
		$obj = $this->input;

		//variables Definition
		$command = "null";

		//Log
		$logManager = new LogManager();
		$apiLogManager = new LogManager('../logs//api/'. date("Y-m-d").'.log');

		//Token Checks
		if ($obj['token'] == null || !isset($obj['token'])) {
			$this->response([
				'state' => 'unsuccess',
				'errorMSG' => "Missing Value Token in JSON payload",
			], 401);
		}

		//Vstupní Checky
		if (!DeviceManager::registeret($obj['token'])) {
			//Notification data setup
			$notificationMng = new NotificationManager;
			$notificationData = [
				'title' => 'Info',
				'body' => 'New device Detected Found',
				'icon' => BASEDIR . '/app/templates/images/icon-192x192.png',
			];

			//Subdevice Registration
			$deviceId = DeviceManager::create($obj['token'], $obj['token']);
			foreach ($obj['values'] as $key => $value) {
				if (!SubDeviceManager::getSubDeviceByMaster($deviceId, $key)) {
					SubDeviceManager::create($deviceId, $key, UNITS[$key]);
				}
			}

			//Notification for newly added Device
			if ($notificationData != []) {
				$subscribers = $notificationMng::getSubscription();
				foreach ($subscribers as $key => $subscriber) {
					$logManager->write("[NOTIFICATION] SENDING TO" . $subscriber['id'] . " ", LogRecordType::INFO);
					$notificationMng::sendSimpleNotification(SERVERKEY, $subscriber['token'], $notificationData);
				}
			}

			$logManager->write("[API] Registering Device", LogRecordType::INFO);
			$this->response([
				'state' => 'unsuccess',
				'errorMSG' => "Device not registeret",
			], 401);
		}

		if (!DeviceManager::approved($obj['token'])) {
			$this->response([
				'state' => 'unsuccess',
				'errorMSG' => "Unaproved Device",
			], 401);
		}

		//Diagnostic/Log Data Save
		if (isset($obj['settings'])){
			$data = ['mac' => $obj['settings']["network"]["mac"], 'ip_address' => $obj['settings']["network"]["ip"]];
			if (array_key_exists("firmware_hash", $obj['settings'])) {
				$data['firmware_hash'] = $obj['settings']["firmware_hash"];
			}
			DeviceManager::editByToken($obj['token'], $data);
			$this->response([
				'state' => 'succes',
				'command' => $command,
				]);
			}

			// Issuing command
			if ($command == "null"){
				$device = DeviceManager::getDeviceByToken($obj['token']);
				$deviceId = $device['device_id'];
				$deviceCommand = $device["command"];
				if ($deviceCommand != '' && $deviceCommand != null && $deviceCommand != "null")
				{
					$command = $deviceCommand;
					$data = [
						'command'=>'null'
					];
					DeviceManager::editByToken($obj['token'], $data);
					$logManager->write("[API] Device_ID " . $deviceId . " executing command " . $command, LogRecordType::INFO);
				}
			}

			if (isset($obj['values'])) {
				//zapis

				//ZAPIS
				$device = DeviceManager::getDeviceByToken($obj['token']);
				$deviceId = $device['device_id'];
				foreach ($obj['values'] as $key => $value) {
					if (!SubDeviceManager::getSubDeviceByMaster($deviceId, $key)) {
						SubDeviceManager::create($deviceId, $key, UNITS[$key]);
					}
					RecordManager::create($deviceId, $key, round($value['value'],3));
					$logManager->write("[API] Device_ID " . $deviceId . " writed value " . $key . ' ' . $value['value'], LogRecordType::INFO);

					//notification
					if ($key == 'door' || $key == 'water') {
						$notificationMng = new NotificationManager;
						$notificationData = [];

						switch ($key) {
							case 'door':
								$notificationData = [
									'title' => 'Info',
									'body' => 'Someone just open up '.$device['name'],
									'icon' => BASEDIR . '/app/templates/images/icon-192x192.png',
								];

							break;
							case 'water':
								$notificationData = [
									'title' => 'Alert',
									'body' => 'Wather leak detected by '.$device['name'],
									'icon' => BASEDIR . '/app/templates/images/icon-192x192.png',
								];
							break;
						}
						if (DEBUGMOD) $notificationData['body'] .= ' value='.$value['value'];
						if ($notificationData != []) {
							$subscribers = $notificationMng::getSubscription();
							foreach ($subscribers as $key => $subscriber) {
								$logManager->write("[NOTIFICATION] SENDING TO" . $subscriber['id'] . " ", LogRecordType::INFO);
								$notificationMng::sendSimpleNotification(SERVERKEY, $subscriber['token'], $notificationData);
							}
						}
					}
				}

				$hostname = strtolower($device['name']);
				$hostname = str_replace(' ', '_', $hostname);
				//upravit format na setings-> netvork etc
				$jsonAnswer = [
					'device' => [
						'hostname' => $hostname,
						'ipAddress' => $device['ip_address'],
						'subnet' => $device['subnet'],
						'gateway' => $device['gateway'],
					],
					'state' => 'succes',
					'command' => $command,
				];

				$subDevicesTypeList = SubDeviceManager::getSubDeviceSTypeForMater($deviceId);
				if (!in_array($subDevicesTypeList, ['on/off', 'door', 'water'])) {
					$jsonAnswer['device']['sleepTime'] = $device['sleep_time'];
				}

				$this->response($jsonAnswer);

			} else {
				//Vypis
				$device = DeviceManager::getDeviceByToken($obj['token']);
				$deviceId = $device['device_id'];

				if (count(SubDeviceManager::getAllSubDevices($deviceId)) == 0) {
					SubDeviceManager::create($deviceId, 'on/off', UNITS[$key]);
					//RecordManager::create($deviceId, 'on/off', 0);
				}

				$subDevicesData = SubDeviceManager::getAllSubDevices($deviceId);
				$subDeviceLastReordValue = [];

				foreach ($subDevicesData as $key => $subDeviceData) {
					$subDeviceId = $subDeviceData['subdevice_id'];
					$subDeviceLastReord = RecordManager::getLastRecord($subDeviceId);
					$subDeviceLastReordValue[] = [$subDeviceData['type'] => $subDeviceLastReord['value']];

					if ($subDeviceLastReord['execuded'] == 0){
						$logManager->write("[API] subDevice_ID ".$subDeviceId . " executed comand with value " . json_encode($subDeviceLastReordValue) ." executed " . $subDeviceLastReord['execuded'], LogRecordType::INFO);
						RecordManager::setExecuted($subDeviceLastReord['record_id']);
					}
				}


				$this->response(['device' => [
					'hostname' => $device['name'],
					'ipAddress' => $device['ip_address'],
					'subnet' => $device['subnet'],
					'gateway' => $device['gateway'],
				],
				'state' => 'succes',
				'value' => $subDeviceLastReordValue,
				'command' => $command]);
			}
			// this method returns response as json

		}
	}
