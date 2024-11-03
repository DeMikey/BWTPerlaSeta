<?php

declare(strict_types=1);
	class BWTPerlaSeta extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RegisterPropertyString("IPAddress", '0.0.0.0');
			$this->RegisterPropertyString("Username", '');
			$this->RegisterPropertyString("Password", '');
			$this->RegisterPropertyBoolean("HTTPUpdateTimer", false);
			$this->RegisterPropertyInteger("UpdateTimer", 10);
			$this->RegisterPropertyBoolean("DailyData", false);
			$this->RegisterPropertyBoolean("DebugLog", false);

			// Timer
			$this->RegisterTimer("BWTPerla_UpdateTimer", 0, 'BWTPerla_Update($_IPS[\'TARGET\']);');
			}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();

			// Generate Profiles & Variables
	        $this->registerProfiles();
    	    $this->registerVariables();

        	// Set Timer
        	if ($this->ReadPropertyBoolean("HTTPUpdateTimer") and $this->ReadPropertyInteger("UpdateTimer") >= 10) {
//            	$this->SetTimerInterval("BWTPerla_UpdateTimer", $this->ReadPropertyInteger("UpdateTimer")*1000);
            	$this->SetTimerInterval("BWTPerla_UpdateTimer", 1800000);
        	} else {
            	$this->SetTimerInterval("BWTPerla_UpdateTimer", 0);
        	}
		}

		#================================================================================================
		public function Update() {
		#================================================================================================
		
			$semaphore = 'BWTPerla'.$this->InstanceID.'_Update';
			$this->log('Update - Try to enter Semaphore');
			if (IPS_SemaphoreEnter($semaphore, 0) == false) {
				$this->log('Update - Semaphore not avaible');
				return false;
			};
			$this->log('Update - Semaphore entered');
	
			// HTTP status request
			$data = $this->SendHTTPCommand('GetCurrentData');
			$this->log('Empfangene Daten:');
			$this->log(print_r($data, true));
			$this->log(print_r(array_keys($data),true));
			if ($data == false) {
				IPS_SemaphoreLeave($semaphore);
				$this->log('Update - Keine Daten empfangen');
				$this->log('Update - Semaphore leaved');
				return false;
			} else {
				// set values to variables
	
				//--- Identification
				$this->SetValue("ActiveErrorIDs", $data['ActiveErrorIDs']);
				$this->SetValue("BlendedWaterSinceSetup_l", $data['BlendedWaterSinceSetup_l']);
	
				$this->SetValue("CapacityColumn1_ml_dH", $data['CapacityColumn1_ml_dH']);
				$this->SetValue("CapacityColumn2_ml_dH", $data['CapacityColumn2_ml_dH']);
				$this->SetValue("CurrentFlowrate_l_h", $data['CurrentFlowrate_l_h']);
				$this->SetValue("DosingSinceSetup_ml", $data['DosingSinceSetup_ml']);
				$this->SetValue("FirmwareVersion", $data['FirmwareVersion']);

				$this->SetValue("HardnessIN_CaCO3", $data['HardnessIN_CaCO3']);
				$this->SetValue("HardnessIN_dH", $data['HardnessIN_dH']);
				$this->SetValue("HardnessIN_fH", $data['HardnessIN_fH']);
				$this->SetValue("HardnessIN_mmol_l", $data['HardnessIN_mmol_l']);
				$this->SetValue("HardnessOUT_CaCO3", $data['HardnessOUT_CaCO3']);
				$this->SetValue("HardnessOUT_dH", $data['HardnessOUT_dH']);
				$this->SetValue("HardnessOUT_fH", $data['HardnessOUT_fH']);
				$this->SetValue("HardnessOUT_mmol_l", $data['HardnessOUT_mmol_l']);
				$this->SetValue("HolidayModeStartTime", $data['HolidayModeStartTime']);
				$this->SetValue("LastRegenerationColumn1", $data['LastRegenerationColumn1']);
				$this->SetValue("LastRegenerationColumn2", $data['LastRegenerationColumn2']);
				$this->SetValue("LastServiceCustomer", $data['LastServiceCustomer']);
				$this->SetValue("LastServiceTechnican", $data['LastServiceTechnican']);
				$this->SetValue("OutOfService", $data['OutOfService']);
				$this->SetValue("RegenerationCounterColumn1", $data['RegenerationCounterColumn1']);
				$this->SetValue("RegenerationCounterColumn2", $data['RegenerationCounterColumn2']);
				$this->SetValue("RegenerationCountSinceSetup", $data['RegenerationCountSinceSetup']);
				$this->SetValue("RegenerativLevel", $data['RegenerativLevel']);
				$this->SetValue("RegenerativRemainingDays", $data['RegenerativRemainingDays']);
				$this->SetValue("RegenerativSinceSetup_g", $data['RegenerativSinceSetup_g']);
				$this->SetValue("ShowError", $data['ShowError']);
				$this->SetValue("WaterTreatedCurrentDay_l", $data['WaterTreatedCurrentDay_l']);
				$this->SetValue("WaterTreatedCurrentMonth_l", $data['WaterTreatedCurrentMonth_l']);
				$this->SetValue("WaterTreatedCurrentYear_l", $data['WaterTreatedCurrentYear_l']);
			}
			if ($this->ReadPropertyBoolean("DailyData")) {
				// Get Health Data
				$data = $this->SendHTTPCommand('GetDailyData');
				if ($data == false) {
					IPS_SemaphoreLeave($semaphore);
					$this->log('Update - Keine Tages Ststistik Daten empfangen');
					$this->log('Update - Semaphore leaved');
				} else {
					$StatistikDayCat = @IPS_GetCategoryIDByName('Verbrauch Tag', $this->InstanceID);
					$this->SetValue("00000029", $data['0000_0029_l']);
//				$this->SetValue("mowerVoltageInternal", $data['health']['voltages']['int3v3']/1000);
//				$this->SetValue("mowerVoltageExternal", $data['health']['voltages']['ext3v3']);
//				$this->SetValue("mowerVoltageBattery", $data['health']['voltages']['batt']/10
				}
			}
			// Set Timer
//			if ($this->ReadPropertyBoolean("HTTPUpdateTimer") and $this->ReadPropertyInteger("UpdateTimer") >= 10) {
//				$this->SetTimerInterval("BWTPerla_UpdateTimer", $this->ReadPropertyInteger("UpdateTimer")*1000);
//			} else {
//				$this->SetTimerInterval("BWTPerla_UpdateTimer", 0);
//			}
	
			IPS_SemaphoreLeave($semaphore);
			$this->log('Update - Semaphore leaved');
		}
	

		#================================================================================================
    	protected function SendHTTPCommand($command) {
        #================================================================================================
        	$IPAddress = trim($this->ReadPropertyString("IPAddress"));
        	$Username = trim($this->ReadPropertyString("Username"));
        	$Password = trim($this->ReadPropertyString("Password"));
        	$this->log('SendHTTPCommand - Begin');
        	// check if IP is ocnfigured and valid
        	if ($IPAddress == "0.0.0.0") {
            	$this->SetStatus(200); // no configuration done
            	return false;
        	} elseif (filter_var($IPAddress, FILTER_VALIDATE_IP) == false) {
            	$this->SetStatus(201); // no valid IP configured
            	return false;
        	}
        	$this->log('Http Request send');
        	switch ($command) {
            	case 'GetDailyData':
                	$URL = 'http://' . $IPAddress . ':8080/api/GetDailyData';
                	break;
				case 'GetMonthlyData':
					$URL = 'http://' . $IPAddress . ':8080/api/GetMonthlyData';
					break;
				case 'GetYearlyData':
					$URL = 'http://' . $IPAddress . ':8080/api/GetYearlyData';
					break;
				default:
                	$URL = 'http://' . $IPAddress . ':8080/api//GetCurrentData';
                	break;
        	}
			try {
				$ch = curl_init();
				curl_setopt_array($ch, [
					CURLOPT_URL => $URL,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
					CURLOPT_USERPWD => $Username . ':' . $Password,
					CURLOPT_TIMEOUT => 30
				]);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				//  $json = curl_exec($ch);
				if (! $json = curl_exec($ch)) {
					$this->log((curl_error($ch)));
					curl_close($ch);
					return false;
				}
				curl_close($ch);
				$this->log('Http Request finished');
			} catch (Exception $e) {
				curl_close($ch);
				$this->log('Http Request on error');
				$this->SetStatus(203); // no valid IP configured
				return false;
			};
			if (strlen($json) > 3) {
				$this->SetStatus(102); // BWT Perl found
			} else {
				$this->SetStatus(202); // No Device at IP
			}
			$this->log("Response: ".$json);
        	$this->log('SendHTTPCommand - End');
			return json_decode(utf8_encode($json), true, 1000, JSON_INVALID_UTF8_IGNORE);
    	}

    	#================================================================================================
		protected function log( string $text ) {
    	#================================================================================================
		if ( $this->ReadPropertyBoolean("DebugLog") ) {
            	$this->SendDebug( "BWT Perla", $text, 0 );
        	};
    	}


    	#================================================================================================
		protected function registerVariables() {
    	#================================================================================================

			//--- Basic Data ---------------------------------------------------------
			$this->RegisterVariableString( "ActiveErrorIDs", "ActiveErrorIDs", "", 0);
			$this->RegisterVariableInteger("BlendedWaterSinceSetup_l", "BlendedWaterSinceSetup_l", "", 1 );
	
			// Interactive --------------------------------------------------------------
	
			$this->RegisterVariableInteger("CapacityColumn1_ml_dH", "ModCapacityColumn1_ml_dHus", "", 20);
			$this->RegisterVariableInteger("CapacityColumn2_ml_dH", "CapacityColumn2_ml_dH", "", 21 );
			$this->RegisterVariableInteger("CurrentFlowrate_l_h", "CurrentFlowrate_l_h", "", 21 );
			$this->RegisterVariableInteger("DosingSinceSetup_ml", "DosingSinceSetup_ml", "", 21 );
			$this->RegisterVariableString( "FirmwareVersion", "FirmwareVersion", "", 0);
			$this->RegisterVariableInteger("HardnessIN_CaCO3", "Eingangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HardnessIN_dH", "Eingangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HardnessIN_fH", "Eingangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HardnessIN_mmol_l", "Eingangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HardnessOUT_CaCO3", "Ausgangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HardnessOUT_dH", "Ausgangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HardnessOUT_fH", "Ausgangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HardnessOUT_mmol_l", "Ausgangswasserhärte", "", 21 );
			$this->RegisterVariableInteger("HolidayModeStartTime", "Urlaubsmodus", "", 21 );
			$this->RegisterVariableString( "LastRegenerationColumn1", "Regeneration von Säule 1", "", 0);
			$this->RegisterVariableString( "LastRegenerationColumn2", "Regeneration von Säule 2", "", 0);
			$this->RegisterVariableString( "LastServiceCustomer", "Letzte routinemässige Wartung", "", 0);
			$this->RegisterVariableString( "LastServiceTechnican", "Letzte Expertwartung", "", 0);
			$this->RegisterVariableInteger("OutOfService", "OutOfService", "", 21 );
			$this->RegisterVariableInteger("RegenerationCounterColumn1", "RegenerationCounterColumn1", "", 21 );
			$this->RegisterVariableInteger("RegenerationCounterColumn2", "RegenerationCounterColumn2", "", 21 );
			$this->RegisterVariableInteger("RegenerationCountSinceSetup", "RegenerationCountSinceSetup", "", 21 );
			$this->RegisterVariableInteger("RegenerativLevel", "RegenerativLevel", "", 21 );
			$this->RegisterVariableInteger("RegenerativRemainingDays", "RegenerativRemainingDays", "", 21 );
			$this->RegisterVariableInteger("RegenerativSinceSetup_g", "RegenerativSinceSetup_g", "", 21 );
			$this->RegisterVariableInteger("ShowError", "ShowError", "", 21 );
			$this->RegisterVariableInteger("WaterTreatedCurrentDay_l", "WaterTreatedCurrentDay_l", "", 21 );
			$this->RegisterVariableInteger("WaterTreatedCurrentMonth_l", "WaterTreatedCurrentMonth_l", "", 21 );
			$this->RegisterVariableInteger("WaterTreatedCurrentYear_l", "WaterTreatedCurrentYear_l", "", 21 );
	
			//---- Statistik

			if ($this->ReadPropertyBoolean("DailyData")) {
				if (!$StatistikDayCat = @IPS_GetCategoryIDByName('Verbrauch Tag', $this->InstanceID)) {
					$StatistikDayCat = IPS_CreateCategory();   // Kategorie anlegen
						IPS_SetName($StatistikDayCat, "Verbrauch Tag");   // Kategorie umbenennen
						IPS_SetParent($StatistikDayCat, $this->InstanceID); // Kategorie einsortieren unter der BWT Instanz
				}
				for ($i = 0; $i <= 10; $i++) {
					if ($i < 10) {
						$Hour = '0' . $i;
					} else {
						$Hour = $i;
					}
					if (!@IPS_GetObjectIDByIdent($i . "00" . $i . "29", $StatistikDayCat)) {
						IPS_SetParent($this->RegisterVariableInteger($i . "00" . $i . "29", $i . ":00-" . $i . ":29", "BWTPerla_Liter", 10 . $i), $StatistikDayCat); 
					}
					if (!@IPS_GetObjectIDByIdent($i . "30" . $i . "59", $StatistikDayCat)) {
						IPS_SetParent($this->RegisterVariableInteger($i . "30" . $i . "59", $i . ":30-" . $i . "-59", "BWTPerla_Liter", 10 . $i), $StatistikDayCat); 
					}
				}
/*				if (!@IPS_GetObjectIDByIdent("01:00-01:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("01:00-01-29", "01:00-01-29", "", 103), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("01:30-01:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("01:30-01-59", "01:30-01-59", "", 104), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("02:00-02:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("02:00-02-29", "02:00-02-29", "", 105), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("02:30-02:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("02:30-02-59", "02:30-02-59", "", 106), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("03:00-03:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("03:00-03-29", "03:00-03-29", "", 107), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("03:30-03:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("03:30-03-59", "03:30-03-59", "", 108), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("04:00-04:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("04:00-04-29", "04:00-04-29", "", 109), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("04:30-04:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("04:30-04-59", "04:30-04-59", "", 110), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("05:00-05:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("05:00-05-29", "05:00-05-29", "", 111), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("05:30-05:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("05:30-05-59", "05:30-05-59", "", 112), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("06:00-06:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("06:00-06-29", "06:00-06-29", "", 113), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("06:30-06:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("06:30-06-59", "06:30-06-59", "", 114), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("07:00-07:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("07:00-07-29", "07:00-07-29", "", 115), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("07:30-07:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("07:30-07-59", "07:30-07-59", "", 116), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("08:00-08:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("08:00-08-29", "08:00-08-29", "", 117), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("08:30-08:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("08:30-08-59", "08:30-08-59", "", 118), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("09:00-09:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("09:00-09-29", "09:00-09-29", "", 119), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("09:30-09:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("09:30-09-59", "09:30-09-59", "", 120), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("10:00-10:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("10:00-10-29", "10:00-10-29", "", 121), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("10:30-10:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("10:30-10-59", "10:30-10-59", "", 122), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("11:00-11:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("11:00-11-29", "11:00-11-29", "", 123), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("11:30-11:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("11:30-11-59", "11:30-11-59", "", 124), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("12:00-12:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("12:00-12-29", "12:00-12-29", "", 125), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("12:30-12:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("12:30-12-59", "12:30-12-59", "", 126), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("13:00-13:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("13:00-13-29", "13:00-13-29", "", 127), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("13:30-13:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("13:30-13-59", "13:30-13-59", "", 128), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("14:00-14:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("14:00-14-29", "14:00-14-29", "", 129), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("14:30-14:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("14:30-14-59", "14:30-14-59", "", 130), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("15:00-15:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("15:00-15-29", "15:00-15-29", "", 131), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("15:30-15:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("15:30-15-59", "15:30-15-59", "", 132), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("16:00-16:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("16:00-16-29", "16:00-16-29", "", 133), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("16:30-16:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("16:30-16-59", "16:30-16-59", "", 134), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("17:00-17:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("17:00-17-29", "17:00-17-29", "", 135), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("17:30-17:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("17:30-17-59", "17:30-17-59", "", 136), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("18:00-18:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("18:00-18-29", "18:00-18-29", "", 137), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("18:30-18:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("18:30-18-59", "18:30-18-59", "", 138), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("19:00-19:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("19:00-19-29", "19:00-19-29", "", 139), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("19:30-19:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("29:30-19-59", "19:30-19-59", "", 140), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("20:00-20:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("20:00-20-29", "20:00-20-29", "", 141), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("20:30-20:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("20:30-20-59", "20:30-20-59", "", 142), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("21:00-21:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("21:00-21-29", "21:00-21-29", "", 143), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("21:30-21:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("21:30-21-59", "21:30-21-59", "", 144), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("22:00-22:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("22:00-22-29", "22:00-22-29", "", 145), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("22:30-22:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("22:30-22-59", "22:30-22-59", "", 146), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("23:00-23:29", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("23:00-23-29", "23:00-23-29", "", 147), $StatistikDayCat); 
				}
				if (!@IPS_GetObjectIDByIdent("23:30-23:59", $StatistikDayCat)) {
					IPS_SetParent($this->RegisterVariableInteger("23:30-23-59", "23:30-23-59", "", 148), $StatistikDayCat); 
				}
*/
			}
		}

    	#================================================================================================
		protected function registerProfiles() {
    	#================================================================================================
			// Generate Variable Profiles
			if (!IPS_VariableProfileExists('BWTPerla_ErrorCode')) {
				IPS_CreateVariableProfile('BWTPerla_ErrorCode', 1);
				IPS_SetVariableProfileIcon('BWTPerla_ErrorCode', '');
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 0, "Status wird ermittelt", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 1, "geparkt", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 2, "mäht", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 3, "sucht die Ladestation", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 4, "lädt", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 5, "sucht", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 7, "Fehlerstatus", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 8, "Schleifensignal verloren", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 16, "abgeschaltet", "", 0xFFFFFF);
				IPS_SetVariableProfileAssociation("BWTPerla_ErrorCode", 17, "schläft", "", 0xFFFFFF);
			}
		
			if (!IPS_VariableProfileExists('BWTPerla_Liter')) {
				IPS_CreateVariableProfile('BWTPerla_Liter', 1);
				IPS_SetVariableProfileText('BWTPerla_Liter', '', 'l');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Milliliter_Deutsche_Haertungsgrad')) {
				IPS_CreateVariableProfile('BWTPerla_Milliliter_Deutsche_Haertungsgrad', 1);
				IPS_SetVariableProfileText('BWTPerla_Milliliter_Deutsche_Haertungsgrad', '', 'ml*°dH');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Liter_pro_Stunde')) {
				IPS_CreateVariableProfile('BWTPerla_Liter_pro_Stunde', 1);
				IPS_SetVariableProfileText('BWTPerla_Liter_pro_Stunde', '', 'l/h');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Milliliter')) {
				IPS_CreateVariableProfile('BWTPerla_Milliliter', 1);
				IPS_SetVariableProfileText('BWTPerla_Milliliter', '', 'ml');
			}
	
			if (!IPS_VariableProfileExists('BWTPerla_ppm_CaCO3')) {
				IPS_CreateVariableProfile('BWTPerla_ppm_CaCO3', 1);
				IPS_SetVariableProfileText('BWTPerla_ppm_CaCO3', '', 'ppm CaCO3');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Deutsche_Haertungsgrad')) {
				IPS_CreateVariableProfile('BWTPerla_Deutsche_Haertungsgrad', 1);
				IPS_SetVariableProfileText('BWTPerla_Deutsche_Haertungsgrad', '', '°dH');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Franzoesische_Haertungsgrad')) {
				IPS_CreateVariableProfile('BWTPerla_Franzoesische_Haertungsgrad', 1);
				IPS_SetVariableProfileText('BWTPerla_Franzoesische_Haertungsgrad', '', '°fH');
			}

			if (!IPS_VariableProfileExists('BWTPerla_MilliMol_pro_Liter')) {
				IPS_CreateVariableProfile('BWTPerla_MilliMol_pro_Liter', 1);
				IPS_SetVariableProfileText('BWTPerla_MilliMol_pro_Liter', '', 'mmol/l');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Tage')) {
				IPS_CreateVariableProfile('BWTPerla_Tage', 1);
				IPS_SetVariableProfileText('BWTPerla_Tage', '', 'Tage');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Prozent')) {
				IPS_CreateVariableProfile('BWTPerla_Prozent', 1);
				IPS_SetVariableProfileText('BWTPerla_Prozent', '', '%');
			}

			if (!IPS_VariableProfileExists('BWTPerla_Gramm')) {
				IPS_CreateVariableProfile('BWTPerla_Gramm', 1);
				IPS_SetVariableProfileText('BWTPerla_Gramm', '', 'g');
			}
		}		

	}