<?php
namespace Cetera;

/**
 * Cetera CMS 3
 * 
 * Действия с пользователями
 *
 * @package CeteraCMS
 * @version $Id$
 * @copyright 2000-2010 Cetera labs (http://www.cetera.ru) 
 * @author Roman Romanov <nicodim@mail.ru> 
 **/

include('common_bo.php');

if (!$user->allowAdmin())  throw new Exception\CMS(Exception\CMS::NO_RIGHTS);

$res = array(
    'success' => false,
    'errors'  => array()
);

$action = $_REQUEST['action'];
$theme = $_REQUEST['theme'];

if ($action == 'delete') {
    Theme::find($theme)->delete();
} 
elseif ($action == 'install') {   
    ob_start();
    
    try {
		
		$extract_content = false;
		$content = false;
		if (isset($_REQUEST['content']) && $_REQUEST['content']) {
			$extract_content = true;
			$content = $_REQUEST['content'];
		}
            
        Theme::install($theme, function($text, $start) { 
            if ($start) echo '<b>';
            echo $text; 
            if ($start) echo '</b> ... '; else echo '<br>';
        }, $translator, $extract_content, $content);      
    
    } catch (\Exception $e) {
    
        header("HTTP/1.0 201");
        echo '<span class="error">Ошибка!<span class="error-desc">'.$e->getMessage().'</span></span>';
    
    }  
    
    ob_end_flush();
    die();
    
}
elseif ($action == 'upload') { 
	if (!$application->getVar('developer_key')) throw new \Exception($translator->_('Не указан ключ разработчика'));
	Theme::find($theme)->uploadToMarket($application->getVar('developer_key'));
	$res['success'] = true;
} 
elseif ($action == 'upload_content') { 
	if (!$application->getVar('developer_key')) throw new \Exception($translator->_('Не указан ключ разработчика'));
	Theme::find($theme)->uploadContentToMarket($application->getVar('developer_key'));
	$res['success'] = true;
}  

echo json_encode($res);