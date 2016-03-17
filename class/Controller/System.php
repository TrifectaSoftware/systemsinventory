<?php

namespace systemsinventory\Controller;

use systemsinventory\Factory\PC as PCFactory;
use systemsinventory\Factory\IPAD as IPADFactory;
use systemsinventory\Factory\Printer as PrinterFactory;
use systemsinventory\Factory\Camera as CameraFactory;
use systemsinventory\Factory\DigitalSign as DigitalSignFactory;
use systemsinventory\Factory\SystemDevice as SDFactory;
use systemsinventory\Resource;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Ted Eberhard <eberhardtm at appstate dot edu>
 */
class System extends \Http\Controller {

    public function get(\Request $request) {
        $data = array();
        $data['command'] = $request->shiftCommand();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    protected function getHtmlView($data, \Request $request) {
        $command = 'add';
        if (!empty($data['command']))
            $command = $data['command'];
        
        if($command == 'edit')
            $content = SDFactory::editForm($request, $command);
        else
            $content = SDFactory::form($request, 'system-pc', $command);
        
        $view = new \View\HtmlView($content);
        return $view;
    }

    public function post(\Request $request) {
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/device_types.php");
        $sdfactory = new SDFactory;
        $vars = $request->getRequestVars();
        $isJSON = false;
        if(isset($vars['device_id']))
            $isJSON = true;        
        $device_type = PC;
        
        if(isset($vars['server'])){
            $device_type = SERVER;
        }elseif(isset($vars['device_type'])){
            $device_type = $vars['device_type'];
        }
        $device_id = $sdfactory->postDevice($request);
        
        $this->postSpecificDevice($request, $device_type, $device_id);
        
        $data['command'] = 'success';
        if($isJSON){
            $view = new \View\JsonView(array('success'=> TRUE));
        }else{
            $view = $this->getHtmlView($data, $request);
        }
        $response = new \Response($view);
        return $response;
    }

    public function postSpecificDevice(\Request $request, $device_type, $device_id){
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/device_types.php");

        switch ($device_type) {
            case SERVER:
            case PC:
                $pcfactory = new PCFactory;
                $pcfactory->postNewPC($request, $device_id);
                break;
            case IPAD:
                $ipadfactory = new IPADFactory;
                $ipadfactory->postNewIPAD($request, $device_id);
                break;
            case PRINTER:
                $printerfactory = new PrinterFactory;
                $printerfactory->postNewPrinter($request, $device_id);
                break;
            case CAMERA:
                $camerafactory = new CameraFactory;
                $camerafactory->postNewCamera($request, $device_id);
                break;
            case DIGITAL_SIGN:
                $digitalsignfactory = new DigitalSignFactory;
                $digitalsignfactory->postNewDigitalSign($request, $device_id);
                break;
            case TIME_CLOCK:
                break;
            default:
                break;
        }
    }
    
    public static function loadAdminBar() {
        $auth = \Current_User::getAuthorization();

        $nav_vars['is_deity'] = \Current_user::isDeity();
        $nav_vars['logout_uri'] = $auth->logout_link;
        $nav_vars['username'] = \Current_User::getDisplayName();
        $nav_bar = new \Template($nav_vars);
        $nav_bar->setModuleTemplate('systemsinventory', 'navbar.html');
        $content = $nav_bar->get();
        \Layout::plug($content, 'NAV_LINKS');
    }

    protected function getJsonView($data, \Request $request) {
        $vars = $request->getRequestVars();
        if(isset($vars['device_id']))
            $device_id = $vars['device_id'];
        if(isset($vars['row_index']))
            $row_index = $vars['row_index'];
        if(isset($vars['specific_device_id']))
            $specific_device_id = $vars['specific_device_id'];
         if(isset($vars['device_type_id']))
            $specific_device_id = $vars['device_type_id'];
        if(isset($vars['username']))
            $username = $vars['username'];
        if(!empty($vars['device_type_id']))
            $device_type_id = $vars['device_type_id'];
        $command = '';
        if(!empty($data['command']))
            $command = $data['command'];
        
        $system_details = '';
        switch ($command) {
            case 'getDetails':
                $result = SDFactory::getSystemDetails($device_id,$row_index);
                break;
            case 'searchUser':
                $result = SDFactory::searchUserByUsername($username);
                break;
            case 'getUser':
                $result = SDFactory::getUserByUsername($username);
                break;
            case 'delete':
                $result = SDFactory::deleteDevice($device_id, $specific_device_id, $device_type_id);
                break;
                
        }
        $view = new \View\JsonView($result);
        return $view;
    }
    
    

}
