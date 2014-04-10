<?php
/**
 * EClientScriptBoost class
 * 
 * Is an extended version of CClientScript to compress registered scripts
 * 
 * @author Antonio Ramirez Cobos
 * @link www.ramirezcobos.com
 *
 * 
 * @copyright 
 * 
 * Copyright (c) 2010 Antonio Ramirez Cobos
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software 
 * and associated documentation files (the "Software"), to deal in the Software without restriction, 
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, 
 * subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies or substantial 
 * portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
 * LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
 * NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE 
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
class EClientScriptBoost extends CClientScript
{
    public $cacheDuration = 0;
	
	/**
	 * @var boolean Allows to put the component into debug mode. Registered content will be checked
	 * against its cached version (if present) and a warning will be issued if the
	 * freshly compressed and cached content do not match. 
	 * If there is a mismatch, it means that wrong content is being served from cache. There could be two
	 * reasons for that:
	 * 1. Original script has been changed, but the cache holds the previous version. Flushing or
	 * clearing cache will resolve this.
	 * 2. There are views or components that are using same IDs in the registerScript() and registerCss()
	 * calls. The debug message includes the IDs with mismatch, which will help to identify the source.
	 * It is recommended to run your project for couple days or weeks with this debug mode on to catch
	 * such overlapping IDs.
	 * NOTE: When debug mode is off, cached content will silently served. If you have overlapping content
	 * IDs, then this will most likely result in all kinds of unxpected behaviour! Make sure to run
	 * EClientScriptBoost in debug mode after you introduce changes or add new scripts. Also, DO NOT include
	 * dynamic stuff like model IDs, links etc. in the scripts you register. It is a good practice to write
	 * the scripts to use variables defined by the view itself, for example in a <script></script> section.
	 */
	public $debug;
    
    /**
	 * @var array Ids for scripts that should not be cached.
     * For instance: when the cached script has a CSRF token.
     * The solution for application controlled scripts is to add the CSRF Value to the ID,
     * but for scripts in extensions or the framework, it is not recommended to change the framework
     * nor the extension, so they are added in this list.
     */
    public $skipList=array(
		'CActiveForm#',
		'CButtonColumn',
		'CGridView#',
		'CJuiDialog',
		'CJuiButton',
		'EditableField#',
		'JToggleColumn#',
		'TbBulkActions#',
		'TbEditableField#',
		'TbEditable#',
		'TbSelect2#',
		'Yii2Debug#',
		'Yii.CHtml.#',
    );
	
	/**
	 * @see CClientScript::registerScript()
	 */
	public function registerScript($id,$script,$position=null,array $htmlOptions=array())
	{
		return $this->registerContent('script', $id, $script, $position, $htmlOptions);
    }
	
	/**
	 * @see CClientScript::registerCss()
	 */
    public function registerCss($id, $css, $media = '')
    {
		return $this->registerContent('css', $id, $css, $media);
    }
	
	/**
	 * All minifying and caching logic for registerScript() and registerCss() is defined here
	 * for much of this logic is the same between the two methods.
	 * @param string $type either 'script' or 'css', determines which minify function
	 * is used on the content.
	 * @param string $id ID that uniquely identifies this piece of JavaScript code
	 * @param string $content content that should be compressed (either JS or CSS) and registered
	 * @param integer|string $registerParam CClientScript::POS_* value when called
	 * by {@link registerScript()}, CSS media string when called by {@link registerContent()}
	 * @param array $htmlOptions additional HTML attributes (only for scripts)
	 * Note: HTML attributes are not allowed for script positions "CClientScript::POS_LOAD" and "CClientScript::POS_READY".
	 * @return CClientScript the CClientScript object itself (to support method chaining, available since version 1.1.5)
	 * @throws CException
	 */
	protected function registerContent($type, $id, $content, $registerParam=null, array $htmlOptions=array())
	{
		if ($type != 'script' && $type != 'css')
			throw new CException(__CLASS__ . " supports only registerScript() and registerCss()");
		
		// if debug mode has not been specified by the Yii config part of clientScript
		// set the mode based on global YII_DEBUG
        $debug = $this->debug === null ? YII_DEBUG : $this->debug;
		
        // Check if this script is in the exceptions - if so, skip caching.
		// TODO: do we really need skipList for CSS?
		$skip = false;
        foreach($this->skipList as $s) {
            $skip|=strpos($id, $s) === 0;
            if($skip) break;
        }
		
		// Do not use cache for content with IDs listed in {@link $this->skipList}
		if ($skip)
            $compressed = EScriptBoost::minifyJs($content);
		else
			$compressed = Yii::app()->cache->get($id);
        
		// if in debug mode -- recompress the content and compare with the
		// cached version if present
		if ($debug && $compressed !== false)
		{
            // During debug check that the newly minified content is not different from the cached one.
            // If so, log the difference so that it can be fixed.
			if ($type == 'script')
				$recompressed = EScriptBoost::minifyJs($content);
			else if ($type == 'css')
				$recompressed = EScriptBoost::minifyCss($content);
				
            if ($recompressed !== $compressed)
			{
				Yii::log(
					"Recompressed {$type} with id '$id' does not match to the previously cached version.\n\n"
					. "Newly compressed version:\n\n"
					. "----------------------- < begin > -----------------------\n\n"
					. CVarDumper::dumpAsString($recompressed)
					. "\n\n----------------------- < end > -----------------------\n\n"
					. PHP_EOL
					. "Cached compressed version:\n\n"
					. "----------------------- < begin > -----------------------\n\n"
					. CVarDumper::dumpAsString($compressed)
					. "\n\n----------------------- < end > -----------------------\n\n"
					, CLogger::LEVEL_WARNING
				);
            }
        }
		elseif ($compressed === false)
        {
			if ($type == 'script')
				$compressed = EScriptBoost::minifyJs($content);
			else if ($type == 'css')
				$compressed = EScriptBoost::minifyCss($content);
			
            Yii::app()->cache->set($id, $compressed, $this->cacheDuration);
        }
		
		if ($type == 'script')
		{
			$position = empty($registerParam) ? null : $registerParam;
	        return parent::registerScript($id, $compressed, $registerParam, $htmlOptions);
		}
		else if ($type == 'css')
		{
			$media = empty($registerParam) ? '' : $registerParam;
			return parent::registerCss($id, $compressed, $media);
		}
	}
}
