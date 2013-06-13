<?php
/**
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 *
 * @copyright   Copyright (C) 2012 Asikart. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      Generated by AKHelper - http://asikart.com
 */


// No direct access
defined('_JEXEC') or die;

/**
 * elFinder Connector & Displayer.
 *
 * @package     Windwalker.Framework
 * @subpackage  Helpers
 */
class AKHelperElfinder
{
    /**
     * display
     */
    public static function display($com_option = null, $option = array())
    {
        // Init some API objects
        // ================================================================================
        $date   = JFactory::getDate( 'now' , JFactory::getConfig()->get('offset') ) ;
        $doc    = JFactory::getDocument() ;
        $uri    = JFactory::getURI() ;
        $user   = JFactory::getUser() ;
        $app    = JFactory::getApplication() ;
        $lang   = JFactory::getLanguage();
        $lang_code = $lang->getTag();
        $lang_code = str_replace('-', '_', $lang_code) ;    
        
        // Include elFinder and JS
        // ================================================================================
        JHtml::_('behavior.framework', true);
        
        if( JVERSION >= 3){
                
            // jQuery
            JHtml::_('jquery.framework', true);
            JHtml::_('bootstrap.framework', true);
        
        }else{
            $doc->addStyleSheet('components/com_remoteimage/includes/bootstrap/css/bootstrap.min.css');
            
            // jQuery
            AKHelper::_('include.addJS', 'jquery/jquery.js', 'ww') ;
            $doc->addScriptDeclaration('jQuery.noConflict();');
        }
        
        $assets_url = AKHelper::_('path.getWWUrl').'/assets' ;
        
        // elFinder includes
        $doc->addStylesheet( $assets_url.'/js/jquery-ui/css/smoothness/jquery-ui-1.8.24.custom.css' );
        $doc->addStylesheet( $assets_url.'/js/elfinder/css/elfinder.min.css' );
        $doc->addStylesheet( $assets_url.'/js/elfinder/css/theme.css' );
        
        $doc->addscript( $assets_url.'/js/jquery-ui/js/jquery-ui.min.js' );
        $doc->addscript( $assets_url.'/js/elfinder/js/elfinder.min.js' );
        JHtml::script( $assets_url.'/js/elfinder/js/i18n/elfinder.'.$lang_code.'.js' );
        AKHelper::_('include.core');
        
        
        // Get Request
        $com_option = $com_option ? $com_option : JRequest::getVar('option') ;
        $finder_id  = JRequest::getVar('finder_id') ;
		$modal      = ( JRequest::getVar('tmpl') == 'component' ) ? true : false ;
        $root       = JRequest::getVar('root', '/') ;
        $start_path = JRequest::getVar('start_path', '/') ;
        $site_root  = JURI::root(true).'/' ;
        
        $onlymimes  = JArrayHelper::getValue($option, 'onlymimes', JRequest::getVar('onlymimes', null));
        $onlymimes  = is_array($onlymimes) ? implode(',', $onlymimes) : $onlymimes;
        $onlymimes  = $onlymimes ? "'".str_replace(",", "','", $onlymimes)."'" : '';
        
        
        // Set Script
        $getFileCallback = !$modal ? '' : "
            ,
            getFileCallback : function(file){
                if (window.parent) window.parent.AKFinderSelect( '{$finder_id}',AKFinderSelected, window.elFinder, '{$site_root}');
            }"; 
        
        
        $script = <<<SCRIPT
		var AKFinderSelected ;
		
		// Init elFinder
        jQuery(document).ready(function($) {
            elFinder = $('#elfinder').elfinder({
                url : 'index.php?option={$com_option}&task=elFinderConnector&root={$root}&start_path={$start_path}' ,
                width : '100%' ,
                onlyMimes : [$onlymimes],
                lang : '{$lang_code}',
                handlers : {
                    select : function(event, elfinderInstance) {
                        var selected = event.data.selected;

                        if (selected.length) {
                            AKFinderSelected = [];
                            jQuery.each(selected, function(i, e){
                                    AKFinderSelected[i] = elfinderInstance.file(e);
                            });
                        }

                    }
                }
                
                {$getFileCallback}
                
            }).elfinder('instance');
        }); 
SCRIPT;

        $doc->addScriptDeclaration($script);
        
        echo '<div class="row-fluid">
                <div id="elfinder" class="span12 rm-finder"></div>
            </div>' ;
    }
    
    /**
     * connector
     */
    public static function connector($com_option = null, $option = array())
    {
        error_reporting( JArrayHelper::getValue($option, 'error_reporting', 0) ); // Set E_ALL for debuging
		
		$elfinder_path = AKPATH_ASSETS.'/js/elfinder/php/' ;
		
		include_once $elfinder_path.'elFinderConnector.class.php';
		include_once $elfinder_path.'elFinder.class.php';
		include_once $elfinder_path.'elFinderVolumeDriver.class.php';

		
		/**
		 * Simple function to demonstrate how to control file access using "accessControl" callback.
		 * This method will disable accessing files/folders starting from '.' (dot)
		 *
		 * @param  string  $attr  attribute name (read|write|locked|hidden)
		 * @param  string  $path  file path relative to volume root directory started with directory separator
		 * @return bool|null
		 **/
		function access($attr, $path, $data, $volume) {
            $r = array();
			$r[] = strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
				? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
				:  null;                                    // else elFinder decide it itself
		}
		
        
        // Get Some Request
		$com_option = $com_option ? $com_option : JRequest::getVar('option') ;
		$root       = JRequest::getVar('root', '/') ;
        $start_path = JRequest::getVar('start_path', '/') ;
        
		$opts = array(
			// 'debug' => true,
			'roots' => array(
				array(
					'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
					'path'          => JPath::clean(JPATH_ROOT.'/'.$root, '/'),         // path to files (REQUIRED)
                    'startPath'     => JPath::clean(JPATH_ROOT.'/'.$root.'/'.$start_path. '/') ,
					'URL'           => JPath::clean(JURI::root(true).'/'.$root.'/'.$start_path, '/'), // URL to files (REQUIRED)
                    'tmbPath'       => JPath::clean(JPATH_CACHE.'/AKFinderThumb'),
                    'tmbURL'        => JURI::root() . 'cache/AKFinderThumb',
                    'tmp'			=> JPath::clean(JPATH_CACHE.'/AKFinderTemp'),
					'accessControl' => 'access',             // disable and hide dot starting files (OPTIONAL)
                    //'uploadDeny'    =>  array('text/x-php')
                    'uploadAllow' => array('image'),
                    'dotFiles'     => false,  
				)
			)
		);
        
        $opts = array_merge( $opts, $option );
		
        foreach( $opts['roots'] as $driver ):
            include_once $elfinder_path.'elFinderVolume'.$driver['driver'].'.class.php';
        endforeach;
        
		// run elFinder
		$connector = new elFinderConnector(new elFinder($opts));
		$connector->run();
    }
}