<?php

namespace systemsinventory\Factory;

use systemsinventory\Resource\SystemDevice as Resource;
use systemsinventory\Resource\PC as PCResource;
use systemsinventory\Resource\Camera as CameraResource;
use systemsinventory\Resource\DigitalSign as DigitalSignResource;
use systemsinventory\Resource\IPAD as IPADResource;
use systemsinventory\Resource\Printer as PrinterResource;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Ted Eberhard
 */
class SystemDevice extends \phpws2\ResourceFactory {

    public static function form(\Canopy\Request $request, $active_tab, $data) {
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/device_types.php");
        $vars = array();
        $req_vars = $request->getRequestVars();
        $vars['title'] = '';
        $system_id = NULL;
        $command = $data['command'];
        $action = NULL;
        if (!empty($data['action']))
            $action = $data['action'];

        if ($command == "editProfiles") {
            $message = "Profile Saved!";
            $template_name = 'Edit_Profile.html';
        } else {
            $message = "System Saved!";
            $template_name = 'Add_System.html';
        }

        javascript('jquery');
        \phpws2\Form::requiredScript();

        if (!in_array($active_tab, array('systems-pc', 'ipad', 'printer', 'camera', 'digital-sign', 'time-clock'))) {
            $active_tab = 'systems-pc';
        }

        $js_string = <<<EOF
	  <script type='text/javascript'>var active_tab = '$active_tab';</script> 
EOF;
        \Layout::addJSHeader($js_string);
        $script = PHPWS_SOURCE_HTTP . 'mod/systemsinventory/javascript/systems.js';
        \Layout::addJSHeader("<script type='text/javascript' src='$script'></script>");


        if ($action == 'success') {
            $vars['message'] = $message;
            $vars['display'] = 'display: block;';
        } else {
            $vars['message'] = '';
            $vars['display'] = 'display: none;';
        }

        $system_locations = SystemDevice::getSystemLocations();
        $location_options = '<option value="1">Select Location</opton>';
        foreach ($system_locations as $key => $val) {
            $location_options .= '<option value="' . $val['id'] . '">' . $val['display_name'] . '</option>';
        }
        $vars['locations'] = $location_options;
        $system_dep = SystemDevice::getSystemDepartments();
        $dep_optons = '<option value="1">Select Department</opton>';
        foreach ($system_dep as $val) {
            $dep_optons .= '<option value="' . $val['id'] . '">' . $val['display_name'] . '</option>';
        }
        $vars['departments'] = $dep_optons;
        $system_profiles = SystemDevice::getSystemProfiles();
        $profile_optons = $printer_profile_options = '<option value="1">Select Profile</opton>';
        if (!empty($system_profiles)) {
            foreach ($system_profiles as $val) {
                if ($val['device_type_id'] == PC) {
                    $profile_optons .= '<option value="' . $val['id'] . '">' . $val['profile_name'] . '</option>';
                } else {
                    $printer_profile_options .= '<option value="' . $val['id'] . '">' . $val['profile_name'] . '</option>';
                }
            }
        }
        $vars['profiles'] = $profile_optons;
        $vars['printer_profiles'] = $printer_profile_options;
        $vars['form_action'] = "./systemsinventory/system/" . $command;
        $template = new \phpws2\Template($vars);
        $template->setModuleTemplate('systemsinventory', $template_name);
        return $template->get();
    }

    public function postDevice(\Canopy\Request $request) {
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/device_types.php");
        $system_device = new Resource;
        $device_type = PC;
        $vars = $request->getRequestVars();

        if (isset($vars['device_type'])) {
            $device_type = $vars['device_type'];
        }

        if (isset($vars['server'])) {
            $device_type = SERVER;
        }

        if (!empty($vars['device_id']))
            $system_device->setId($vars['device_id']);
        $system_device->setDeviceType($device_type);
        $system_device->setPhysicalID(filter_input(INPUT_POST, 'physical_id'));
        if (!empty($vars['first_name']))
            $system_device->setName(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING), filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING));
        if (!empty($vars['username']))
            $system_device->setUserName(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
        if (!empty($vars['phone']))
            $system_device->setPhone(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
        $system_device->setLocation(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_NUMBER_INT));
        if (!empty($vars['room_number']))
            $system_device->setRoomNumber(filter_input(INPUT_POST, 'room_number', FILTER_SANITIZE_STRING));
        $system_device->setDepartment(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_NUMBER_INT));
        if (!empty($vars['model']))
            $system_device->setModel(filter_input(INPUT_POST, 'model', FILTER_SANITIZE_STRING));
        if (!empty($vars['hd']))
            $system_device->setHD(filter_input(INPUT_POST, 'hd', FILTER_SANITIZE_STRING));
        if (!empty($vars['processor']))
            $system_device->setProcessor(filter_input(INPUT_POST, 'processor', FILTER_SANITIZE_STRING));
        if (!empty($vars['ram']))
            $system_device->setRAM(filter_input(INPUT_POST, 'ram', FILTER_SANITIZE_STRING));
        if (!empty($vars['mac']))
            $system_device->setMac(filter_input(INPUT_POST, 'mac', FILTER_SANITIZE_STRING));
        if (!empty($vars['mac2']))
            $system_device->setMac2(filter_input(INPUT_POST, 'mac2', FILTER_SANITIZE_STRING));
        if (!empty($vars['primary_ip']))
            $system_device->setPrimaryIP(filter_input(INPUT_POST, 'primary_ip', FILTER_SANITIZE_STRING));
        if (!empty($vars['secondary_ip']))
            $system_device->setSecondaryIP(filter_input(INPUT_POST, 'secondary_ip', FILTER_SANITIZE_STRING));
        if (!empty($vars['manufacturer']))
            $system_device->setManufacturer(filter_input(INPUT_POST, 'manufacturer', FILTER_SANITIZE_STRING));
        $system_device->setVlan(filter_input(INPUT_POST, 'vlan', FILTER_SANITIZE_NUMBER_INT));
        $system_device->setPurchaseDate(filter_input(INPUT_POST, 'purchase_date', FILTER_SANITIZE_STRING));
        if (!empty($vars['profile_name'])) {
            $system_device->setProfile(TRUE);
            $system_device->setProfileName(filter_input(INPUT_POST, 'profile_name', FILTER_SANITIZE_STRING));
        }
        $system_device->setNotes(filter_input(INPUT_POST, 'system_notes', FILTER_SANITIZE_STRING));

        self::saveResource($system_device);
        return $system_device->getId();
    }

    public static function initSystem($vars, $system_id) {

        return $vars;
    }

    public static function getSystemDetails($system_id, $row_index) {
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/device_types.php");
        $device_details = array();
        if (empty($system_id)) {
            throw new Exception("System ID invalid.");
        }
        // get the common device attributes
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM systems_device WHERE id='$system_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetch(\PDO::FETCH_ASSOC);
        $device_type_id = $result['device_type_id'];
        $device_details = $result;
        // get the device specific attributes
        $table = SystemDevice::getSystemType($device_type_id);
        if (!empty($table)) {
            $device_table = $db->addTable($table);
            $device_table->addFieldConditional('device_id', $system_id);
            $device_result = $db->select();
            $device_result = $device_result['0'];
            // set the specific device id so we can use it to save the device specific info later.
            $specific_device_id = $device_result['id'];
            unset($device_result['id']);
            $device_result['specific-device-id'] = $specific_device_id;
            //$device_attr = SystemDevice::getDeviceAttributes($device_type_id);
            $device_details = array_merge($device_details, $device_result);
        }
        $device_details['device-type-id'] = $device_type_id;
        $purchase_date = $device_details['purchase_date'];
        $device_details["purchase_date"] = date('Y-m-d', $purchase_date);
        $system_locations = SystemDevice::getSystemLocations();
        $location_options = '<option value="1">Select Location</opton>';
        foreach ($system_locations as $key => $val) {
            $location_options .= '<option value="' . $val['id'] . '">' . $val['display_name'] . '</option>';
        }
        $device_details['locations'] = $location_options;
        $system_dep = SystemDevice::getSystemDepartments();
        $dep_optons = '<option value="1">Select Department</opton>';
        foreach ($system_dep as $val) {
            $dep_optons .= '<option value="' . $val['id'] . '">' . $val['display_name'] . '</option>';
        }
        $device_details['departments'] = $dep_optons;
        $device_details['testarray'] = array("test1" => "test1", "test2" => "test2", "test3" => "test3");
        $device_details['row_index'] = $row_index;

        return $device_details;
    }

    public static function getProfile($profile_id) {
        if (empty($profile_id)) {
            throw new Exception("System profile id empty.");
        }
        $db = \phpws2\Database::getDB();
        $system_table = $db->addTable("systems_device");
        $system_table->addFieldConditional('id', $profile_id);
        $result = $db->select();
        $profile_result = $result['0'];
        $device_type_id = $profile_result['device_type_id'];
        $profile_result['device-type-id'] = $device_type_id;
        $device_id = $profile_result['id'];
        $table = SystemDevice::getSystemType($device_type_id);
        if (!empty($table)) {
            $device_table = $db->addTable($table);
            $device_table->addFieldConditional('device_id', $device_id);
            $result = $db->select();
            $result = $result['0'];
            $specific_device_id = $result['id'];
            unset($result['id']);
            $profile_result = array_merge($profile_result, $result);
            $profile_result['specific-device-id'] = $specific_device_id;
        }
        return $profile_result;
    }

    public static function searchPhysicalID($physical_id) {
        $db = \phpws2\Database::getDB();
        $system_table = $db->addTable("systems_device");
        $system_table->addFieldConditional('physical_id', $physical_id);
        $search_result = $db->select();
        $result = array('exists' => false);
        if($search_result)
            $search_result = $search_result['0'];
        if (!empty($search_result['id']))
            $result['exists'] = true;
        return $result;
    }

    public static function deleteDevice($device_id, $specific_device_id, $device_type_id) {
        $systems_device = new Resource;
        $systems_device->setId($device_id);
        if (!parent::loadByID($systems_device)) {
            throw new \Exception('Cannot load resource. System id not found:' . $device_id);
        }

        switch ($device_type_id) {
            case '1':
            case '2':
                $specific_device = new PCResource;
                break;
            case '3':
                $specific_device = new IPADResource;
                break;
            case '4':
                $specific_device = new PrinterResource;
                break;
            case '5':
                $specific_device = new CameraResource;
                break;
            case '6':
                $specific_device = new DigitalSignResource;
                break;
        }

        $specific_device->setId($specific_device_id);
        if (!parent::loadByID($specific_device)) {
            throw new \Exception('Cannot load specific resource. System id not found:' . $specific_device_id);
        }
        if (!SystemDevice::deleteResource($specific_device)) {
            throw new \Exception('Cannot delete specific resource. Query failed');
        }
        if (!SystemDevice::deleteResource($systems_device)) {
            throw new \Exception('Cannot delete resource. Query failed');
        }
    }

    public static function markDeviceInventoried($device_id){
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/log_types.php");
        $timestamp = time();
        $log_type = INVENTORY_AUDIT;
        $username = \Current_User::getUsername();
        $db = \Database::getDB();
        $query = "INSERT INTO systems_log (username, device_id, log_type, timestamp) VALUES('$username', '$device_id', '$log_type', '$timestamp')";
        $result = $db->query($query);
        if (empty($result))
            return 0; //should be exception
        return array("success"=>"1","timestamp"=>date("F j, Y, g:i a", $timestamp),"username"=>$username);
    }
    
    public static function getDeviceAudits($device_id){
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/log_types.php");
        $current_time = time();
        $one_year = 31536000;        
        $db = \Database::getDB();
        $tbl = $db->addTable('systems_log');
        $tbl->addField('username');
        $tbl->addField('timestamp');
        $condition0 = new \Database\Conditional($db, 'device_id', $device_id, '=');
        $condition1 = new \Database\Conditional($db, 'log_type', INVENTORY_AUDIT, '=');
        $conditional = new \Database\Conditional($db, $condition0, $condition1, 'AND');
        $db->addConditional($conditional);
        $tbl->addOrderBy("timestamp", "DESC");
        $result = $db->select();

        if (empty($result)){
            return;
        }else{
            if(($current_time - $result[0]['timestamp']) > $one_year){
                $overdue = 1;
            }else{
                $overdue = 0;
            }
                
            foreach($result as $key=>$value){
                $result[$key]["timestamp"] = date("F j, Y, g:i a", $value['timestamp']);
            }            
            $result['audit_overdue'] = $overdue;
        }
        
        return $result;
    }
    
    public static function getUserByUsername($username) {
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/defines.php");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_URL, FACULTY_API_URL . "/$username");
        $faculty_result = curl_exec($curl);
        $faculty_result = json_decode($faculty_result, true);
        curl_setopt($curl, CURLOPT_URL, STUDENT_API_URL . "/$username");
        $student_result = curl_exec($curl);
        $student_result = json_decode($student_result, true);
        $result = NULL;
        curl_close($curl);
        if (!empty($faculty_result))
            return $faculty_result;
        else
            return $student_result;
    }

    public static function searchUserByUsername($username) {
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/defines.php");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, FACULTY_API_URL . "?username=$username");
        $faculty_result = curl_exec($curl);
        $faculty_result = json_decode($faculty_result, true);
        curl_setopt($curl, CURLOPT_URL, STUDENT_API_URL . "?username=$username");
        $student_result = curl_exec($curl);
        $student_result = json_decode($student_result, true);
        $result = NULL;
        if (!empty($faculty_result))
            $result = $faculty_result;
        if (!empty($student_result)) {
            if (!empty($result))
                $result = array_merge($result, $student_result);
            else
                $result = $student_result;
        }
        curl_close($curl);
        return $result;
    }

    public static function getDeviceAttributes($type_id) {
        $systems_pc = array("device_id" => NULL, "os" => "OS", "primary_monitor" => "Primary Monitor", "secondary_monitor" => "Secondary Monitor", "video_card" => "Video Card", "server_type" => NULL, "battery_backup" => NULL, "redundant_backup" => NULL, "touch_screen" => "Touch Screen", "smart_room" => "Smart Room", "dual_monitor" => "Dual Monitor", "system_usage" => NULL, "rotation" => "Rotation", "stand" => "Stand", "check_in" => "Check In");
        $systems_server = array("device_id" => NULL, "os" => "OS", "primary_monitor" => "Primary Monitor", "secondary_monitor" => "Secondary Monitor", "video_card" => "Video Card", "server_type" => NULL, "battery_backup" => NULL, "redundant_backup" => NULL, "touch_screen" => "Touch Screen", "smart_room" => "Smart Room", "dual_monitor" => "Dual Monitor", "system_usage" => NULL, "rotation" => "Rotation", "stand" => "Stand", "check_in" => "Check In");
        $systems_ipad = array("device_id" => NULL, "generation" => "Generation", "apple_id" => "Apple ID");
        $systems_printer = array("device_id" => NULL, "toner_cartridge" => "Toner Cartridge", "color" => "Color", "network" => "Network", "duplex" => "Duplex");

        switch ($type_id) {
            case '1':
                $attr = $systems_pc;
                break;
            case '2':
                $attr = $systems_server;
                break;
            case '3':
                $attr = $systems_ipad;
                break;
            case '4':
                $attr = $systems_printer;
                break;
            case '5':
                $attr = $systems_camera;
                break;
            case '6':
                $attr = $digital_sign;
                break;
            case '7':
                $attr = $systems_timeclock;
                break;
            default:
                $attr = $systems_pc;
        }
        return $attr;
    }

    public static function getSystemType($type_id) {
        switch ($type_id) {
            case '1':
            case '2':
                $table = 'systems_pc';
                break;
            case '3':
                $table = 'systems_ipad';
                break;
            case '4':
                $table = 'systems_printer';
                break;
            case '5':
                $table = 'systems_camera';
                break;
            case '6':
                $table = 'systems_digital_sign';
                break;
            case '7':
                $table = NULL;
                break;
            default:
                $table = 'systems_pc';
        }
        return $table;
    }

    public static function getLocationByID($location_id) {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('systems_location');
        $tbl->addField('description');
        $tbl->addFieldConditional('id', $location_id, '=');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result[0]['description'];
    }

    public static function getDepartmentByID($department_id) {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('systems_department');
        $tbl->addField('description');
        $tbl->addFieldConditional('id', $department_id, '=');
        $result = $db->select();
        if (empty($result))
            return 'Not Found'; //should be exception

        return $result[0]['description'];
    }

    public static function getSystemLocations() {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('systems_location');
        $tbl->addField('id');
        $tbl->addField('display_name');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result;
    }

    public static function getSystemTypes() {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('systems_device_type');
        $tbl->addField('id');
        $tbl->addField('description');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result;
    }

    public static function getSystemDepartments() {
        $user_id = \Current_User::getId();
        $permission_db = \phpws2\Database::getDB();
        $permissions_tbl = $permission_db->addTable('systems_permission');
        $permissions_tbl->addField('departments');
        $permissions_tbl->addField('user_id');
        $permissions_tbl->addFieldConditional('user_id', $user_id);
        $permission_result = $permission_db->select();
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('systems_department');
        $tbl->addField('id');
        $tbl->addField('display_name');
        $tbl->addFieldConditional('active', '1');
        $tbl->addFieldConditional('id', '1','!=');
        $tbl->addOrderBy('display_name');
        if (!empty($permission_result)) {           
            $dep = $permission_result[0]['departments'];
            $deps = explode(':', $dep);
            $cond = NULL;
            foreach($deps as $val){
                $tmp_cond = new \phpws2\Database\Conditional($db, 'id', $val, '=');
                if (empty($cond))
                    $cond = $tmp_cond;
                else
                    $cond = new \phpws2\Database\Conditional($db, $cond, $tmp_cond, 'OR');
            }
            $db->addConditional($cond);
        }
        $result = $db->select();

        if (empty($result))
            return 0; //should be exception
        return $result;
    }

    public static function getSystemProfiles() {
        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('systems_device');
        $tbl->addFieldConditional('profile', 1);
        $tbl->addField('id');
        $tbl->addField('profile_name');
        $tbl->addField('device_type_id');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result;
    }

    public static function display() {

        //$contact_info = self::load();
        //$values = self::getValues($contact_info);

        $template = new \phpws2\Template($values);
        $template->setModuleTemplate('systemsinventory', 'view.html');
        $content = $template->get();
        return $content;
    }

}
