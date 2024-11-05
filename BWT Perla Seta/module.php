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
			$this->RegisterPropertyBoolean("MonthlyData", false);
			$this->RegisterPropertyBoolean("YearlyData", false);
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
					return false;
				} else {
					$DailyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Tag', $this->InstanceID);
					$this->log('Update - Tages Kategorie Id: ' . $DailyDataCategory);
					for ($i = 0; $i <= 23; $i++) {
						if ($i < 10) {
							$Hour = "0" . $i;
						} else {
							$Hour = $i;
						}
						$this->log("Daily key: " . $Hour .  "00_" . $Hour . "29_l");
						if ($VarId = @IPS_GetObjectIDByIdent($Hour . "00" . $Hour . "29", $DailyDataCategory)) {
							SetValue($VarId, $data[$Hour .  "00_" . $Hour . "29_l"]);
						}
						if ($VarId = @IPS_GetObjectIDByIdent($Hour . "30" . $Hour . "59", $DailyDataCategory)) {
							SetValue($VarId, $data[$Hour .  "30_" . $Hour . "59_l"]);
						}
					}
				}
			}
			if ($this->ReadPropertyBoolean("MonthlyData")) {
				// Get Health Data
				$data = $this->SendHTTPCommand('GetMonthlyData');
				if ($data == false) {
					IPS_SemaphoreLeave($semaphore);
					$this->log('Update - Keine Monats Ststistik Daten empfangen');
					$this->log('Update - Semaphore leaved');
					return false;
				} else {
					$MontlyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Monat', $this->InstanceID);
					$this->log('Update - Monats Kategorie Id: ' . $MontlyDataCategory);
					for ($i = 1; $i <= 31; $i++) {
						if ($i < 10) {
							$Day = "0" . $i;
						} else {
							$Day = $i;
						}
						$this->log("Montly key: " . "Day" . $Day . "_l");
						if ($VarId = @IPS_GetObjectIDByIdent("Day" . $Day, $MontlyDataCategory)) {
							SetValue($VarId, $data["Day" . $Day . "_l"]);
						}
					}
				}
			}
			if ($this->ReadPropertyBoolean("YearlyData")) {
				// Get Health Data
				$data = $this->SendHTTPCommand('GetYearlyData');
				if ($data == false) {
					IPS_SemaphoreLeave($semaphore);
					$this->log('Update - Keine Jahres Ststistik Daten empfangen');
					$this->log('Update - Semaphore leaved');
					return false;
				} else {
					$YearlyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Jahr', $this->InstanceID);
					$this->log('Update - Jahres Kategorie Id: ' . $YearlyDataCategory);
					for ($i = 1; $i <= 12; $i++) {
						if ($i < 10) {
							$Year = "0" . $i;
						} else {
							$Year = $i;
						}
						$this->log("Montly key: " . "Month" . $Year . "_l");
						if ($VarId = @IPS_GetObjectIDByIdent("Month" . $Year, $YearlyDataCategory)) {
							SetValue($VarId, $data["Month" . $Year . "_l"]);
						}
					}
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
				if (!$DailyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Tag', $this->InstanceID)) {
					$DailyDataCategory = IPS_CreateCategory();   // Kategorie anlegen
						IPS_SetName($DailyDataCategory, "Verbrauch Tag");   // Kategorie umbenennen
						IPS_SetParent($DailyDataCategory, $this->InstanceID); // Kategorie einsortieren unter der BWT Instanz
				}
				for ($i = 0; $i <= 23; $i++) {
					if ($i < 10) {
						$Hour = "0" . $i;
					} else {
						$Hour = $i;
					}
					if (!@IPS_GetObjectIDByIdent($Hour . "00" . $Hour . "29", $DailyDataCategory)) {
						IPS_SetParent($this->RegisterVariableInteger($Hour . "00" . $Hour . "29", $Hour . ":00-" . $Hour . ":29", "BWTPerla_Liter", 10 . $i), $DailyDataCategory); 
					}
					if (!@IPS_GetObjectIDByIdent($Hour . "30" . $Hour . "59", $DailyDataCategory)) {
						IPS_SetParent($this->RegisterVariableInteger($Hour . "30" . $Hour . "59", $Hour . ":30-" . $Hour . "-59", "BWTPerla_Liter", 10 . $i), $DailyDataCategory); 
					}
				}
			} else {
				if ($DailyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Tag', $this->InstanceID)) {
					// Löschen der Variabeln
					for ($i = 0; $i <= 23; $i++) {
						if ($i < 10) {
							$Hour = "0" . $i;
						} else {
							$Hour = $i;
						}
						if ($VarId = @IPS_GetObjectIDByIdent($Hour . "00" . $Hour . "29", $DailyDataCategory)) {
							IPS_DeleteVariable ($VarId); 
						}
						if ($VarId =@IPS_GetObjectIDByIdent($Hour . "30" . $Hour . "59", $DailyDataCategory)) {
							IPS_DeleteVariable ($VarId);
						}
					} 
					// Löschen der Katergory
					IPS_DeleteCategory ($DailyDataCategory); 
				}
			}
			if ($this->ReadPropertyBoolean("MonthlyData")) {
				if (!$MonthlyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Monat', $this->InstanceID)) {
					$MonthlyDataCategory = IPS_CreateCategory();   // Kategorie anlegen
						IPS_SetName($MonthlyDataCategory, "Verbrauch Monat");   // Kategorie umbenennen
						IPS_SetParent($MonthlyDataCategory, $this->InstanceID); // Kategorie einsortieren unter der BWT Instanz
						IPS_SetPosition($MonthlyDataCategory, 5); // Kategorie an Position 5 verschieben
				}
				for ($i = 1; $i <= 31; $i++) {
					if ($i < 10) {
						$Day = "0" . $i;
					} else {
						$Day = $i;
					}
					if (!@IPS_GetObjectIDByIdent("Day" . $Day, $MonthlyDataCategory)) {
						IPS_SetParent($this->RegisterVariableInteger("Day" . $Day, "Tag " . $Day, "BWTPerla_Liter", 20 . $i), $MonthlyDataCategory); 
					}
				}
			} else {
				if ($MonthlyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Monat', $this->InstanceID)) {
					// Löschen der Variabeln
					for ($i = 1; $i <= 31; $i++) {
						if ($i < 10) {
							$Day = "0" . $i;
						} else {
							$Day = $i;
						}
						if ($VarId = @IPS_GetObjectIDByIdent("Day" . $Day, $MonthlyDataCategory)) {
							IPS_DeleteVariable ($VarId); 
						}
					} 
					// Löschen der Katergory
					IPS_DeleteCategory ($MonthlyDataCategory); 
				}
			}
			if ($this->ReadPropertyBoolean("YearlyData")) {
				if (!$YearlyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Jahr', $this->InstanceID)) {
					$YearlyDataCategory = IPS_CreateCategory();   // Kategorie anlegen
						IPS_SetName($YearlyDataCategory, "Verbrauch Jahr");   // Kategorie umbenennen
						IPS_SetParent($YearlyDataCategory, $this->InstanceID); // Kategorie einsortieren unter der BWT Instanz
						IPS_SetPosition($YearlyDataCategory, 5); // Kategorie an Position 5 verschieben
				}
				for ($i = 1; $i <= 12; $i++) {
					if ($i < 10) {
						$Year = "0" . $i;
					} else {
						$Year = $i;
					}
					if (!@IPS_GetObjectIDByIdent("Day" . $Year, $YearlyDataCategory)) {
						IPS_SetParent($this->RegisterVariableInteger("Month" . $Year, "Monat " . $Year, "BWTPerla_Liter", 20 . $i), $YearlyDataCategory); 
					}
				}
			} else {
				if ($YearlyDataCategory = @IPS_GetCategoryIDByName('Verbrauch Jahr', $this->InstanceID)) {
					// Löschen der Variabeln
					for ($i = 1; $i <= 12; $i++) {
						if ($i < 10) {
							$Year = "0" . $i;
						} else {
							$Year = $i;
						}
						if ($VarId = @IPS_GetObjectIDByIdent("Month" . $Year, $YearlyDataCategory)) {
							IPS_DeleteVariable ($VarId); 
						}
					} 
					// Löschen der Katergory
					IPS_DeleteCategory ($YearlyDataCategory); 
				}
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
				IPS_SetVariableProfileText('BWTPerla_Liter', '', ' l');
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