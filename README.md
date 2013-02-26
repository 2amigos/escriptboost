escriptboost
============

***Minify the javascript/css code of your application's assets and pages***

##Introduction
Probably a lot of you would wonder why, having so many good extensions related to minifying/compressing/packing your javascript and css files around, here comes this guy offering us another solution.  

I did check out all the extensions in our repository, just to name some of them:

 * [clientscriptpacker](http://www.yiiframework.com/extension/clientscriptpacker)
 * [dynamicres](http://www.yiiframework.com/extension/dynamicres/)
 * [minscript](http://www.yiiframework.com/extension/minscript/)  

All of them are great, but none were filling the requirements we had. I did not have any issue compressing all our files as our team, will use the YUI compressor jar file to create our compressed javascript versions and then use the wonderful mapping features of CClientScript. The issue was with the assets of external, or own developed, extensions and the javascript code that, even Yii own widgets, were writing to the `POS_BEGIN`, `POS_END`, `POS_HEAD`, `POS_LOAD`, `POS_READY` positions. Thats exactly what this library is doing: allowing Yii coders to minify those scripts.


##Requirements 

You need to enable any caching mechanism in Yii in order to use it. For example:

~~~
'cache' => array(
    'class' => 'system.caching.CApcCache',
),
~~~


##Library

The library comes with three flavors: 

 * EScriptBoost Component  
 * EClientScriptBoost Extension
 * EAssetManagerBoost Extension  

###EScriptBoost Component

This is a very easy to use component to compress your HTML, Javascript or CSS code at your will. The minifiers used are:  

 * For CSS- [CssMin](http://code.google.com/p/cssmin/ "CssMin") and [CssMinify](http://code.google.com/p/minify/ "CssMinify") (with CssCompressor and CssUriRewriter classes that you can also use independently)  
 * For JS- [JsMin](http://code.google.com/p/jsmin-php/ "JsMin"), [JsMinPlus](http://crisp.tweakblogs.net/blog/cat/716 "JsMinPlus") and [JavaScriptPacker](http://dean.edwards.name/packer/usage/ "JavaScriptPacker") 

 * For HTML- [HTMLMin](http://code.google.com/p/minify/source/browse/tags/beta_2.0.2/lib/Minify/HTML.php)

***Usage***

~~~
// this is a very simple example :)
// we use cache as we do not want to
// compress/minify all the time our
// script 
$js = Yii::app()->cache->get('scriptID');

if(!$js)
{
     $cacheDuration = 30;
     $js = <<<EOD
     // my long and uncompressed code here
EOD;
     // $js = EScriptBoost::packJs($js);
     // $js = EScriptBoost::minifyJs($js, EScriptBoost::JS_MIN_PLUS);
     $js = EScriptBoost::minifyJs($js, EScriptBoost::JS_MIN);

     // see Cache guide for more options | dependencies
     Yii::app()->cache->set('scriptID', $cacheDuration); 
}
Yii::app()->clientScript->registerScript('scriptID', $js);
~~~

That was troublesome right? No worries, if you don't really care about using JS_MIN, or JS_MIN_PLUS, you can use its helper function **registerScript**, it will handle all of the above automatically: 

~~~
$js = <<<EOD
    // my long and uncompressed code here
EOD;
EScriptBoost::registerScript('scriptID', $js); 
~~~

###EClientScriptBoost Extension

**EScriptBoost** was good for the javascript code written by me but what about the ones written by Yii widgets? **EClientScriptBoost** was developed to solve that:

***Usage***  

On your main.php config file:

~~~
'import' => array(
// ... other configuration settings on main.php
// ... importing the folder where scriptboost is
    'application.extensions.scriptboost.*',
// ... more configuration settings 
	),
// ... other configuration settings on main.php
'components' => array(
     'clientScript' => array(
// ... assuming you have previously imported the folder 
//     where EClientScriptBoost is
         'class'=>'EClientScriptBoost',
         'cacheDuration'=>30,
// ... more configuration settings 
~~~

Done! now, every time you or other component on your application registers some script, it will be minified and cached as you specify on your cache settings. Easy right? 

###EAssetManagerBoost Extension

But there was one more challenge to solve. Some extensions, widgets, etc, do publish a whole bunch of files in our assets that are not minified. How to solve that? This is where **EAssetManagerBoost** comes handy. 

This extension does not only minify about to be registered javascript/css files, but also makes sure that the files do not match any of its $minifiedExtensionFlags. This way we can avoid to minify/compress files that do not require it. 

***Usage***  

Make sure you have deleted your previous assets folder contents.

~~~
'import' => array(
// ... other configuration settings on main.php
// ... importing the folder where scriptboost is
    'application.extensions.scriptboost.*',
// ... more configuration settings 
	),
// ... other configuration settings on main.php
'components' => array(
    'assetManager' => array(
// ... assuming you have previously imported the folder 
      'class' => 'EAssetManagerBoost',
      'minifiedExtensionFlags'=>array('min.js','minified.js','packed.js')
        ),
// ... more configuration settings 
~~~

***Important Note***
There is a small drawback to use EAssetManagerBoost and is that the first time your application is requested, it will take a bit of time as it will go throughout all your asset files to be published and minify them.

###Minifying CSS and/or HTML
EScriptBoost comes with a set of handy helper functions, among those minifyCSS and minifyHTML. They are very easy to use, for example with minifyCSS if you look at the code of EAssetManagerBoost lines 172-173, you will see a use of it:

~~~
[php]
@file_put_contents($dstFile, EScriptBoost::minifyCss(@file_get_contents($src)));
~~~

As you can see, you need to pass the CSS contents of a file or a script, in order to be minified. To minifyHTML is the same, you can get the contents returned by renderPartial to be minified or even do it with the whole view. How to be applied will depend on the requirements of the application and it needs to be carefully studied as that could slow down your application without the proper caching technique.

Please, check the reference links in order to get information about how to configure the minifiers included in this extension.


##ChangeLog 
 * Version 1.0.1 Included HTML Compression
 * Version 1.0.0 Initial public release

##Resources
 * [Project page](http://www.ramirezcobos.com/)
 * [Minify Google Code Project](http://code.google.com/p/minify/)  
 * [JsMinPlus page](http://crisp.tweakblogs.net/blog/cat/716 "JsMinPlus") 
 * [CssMin](http://code.google.com/p/cssmin/ "CssMin")  
 * [Yii Forum Post](http://www.yiiframework.com/forum/index.php?/topic/26550-extension-escriptboost/page__pid__127736#entry127736)