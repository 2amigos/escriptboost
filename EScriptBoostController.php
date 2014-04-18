<?php
/**
 * This Controller allows you automatically minify all content rendered by the views.
 * All application controller classes should extend from this class to enable the
 * functionality or processOutput() method below should be ported to your code.
 * Note that this will not enable minification of CSS and JavaScript published through
 * the assetmanager or registered with clientscript. You will need to use EAssetManager
 * and EClientScriptBoost components to minify CSS and scripts registered via Yii functions.
 */
class EScriptBoostController extends CController
{
	public function processOutput($output)
	{
		// check if needed classes exist in order not to break the whole application if
		// they are not yet available
		if
		(
			class_exists('EScriptBoost')
			&& class_exists('CssMin')
			&& class_exists('JSMin')
		)
		{
			// set up handlers to minify CSS and JavaScript in <style> and <script> tags
			$minifyHTMLOptions = array();
			$minifyHTMLOptions['cssMinifier'] = array('CssMin','minify');
			$minifyHTMLOptions['jsMinifier'] = array('JSMin','minify');
			
			$compressedHtmlOutput = EScriptBoost::minifyHTML($output, $minifyHTMLOptions);
			if (!empty($compressedHtmlOutput))
				$output = $compressedHtmlOutput;
			else
				Yii::log("EScriptBoost::minifyHTML() on ".strlen($output)." byte long output returned empty. Returning original output.", CLogger::LEVEL_WARNING, __METHOD__);
		}
		
		return parent::processOutput($output);
	}
}
