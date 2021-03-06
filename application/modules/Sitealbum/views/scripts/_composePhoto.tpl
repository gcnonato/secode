<?php
/**
 * SocialEngine
 *
 * @category   Application_Extensions
 * @package    Sitealbum
 * @copyright  Copyright 2006-2010 Webligo Developments
 * @license    http://www.socialengine.com/license/
 * @version    $Id: _composePhoto.tpl 10109 2013-10-31 01:53:50Z andres $
 * @author     Sami
 */
?>
<?php
$this->headLink()->appendStylesheet($this->layout()->staticBaseUrl . 'application/modules/Sitealbum/externals/styles/style_sitealbum.css');

 $this->headScript()
    ->appendFile($this->layout()->staticBaseUrl . 'application/modules/Sitealbum/externals/scripts/composer_photo.js');
 
  $this->headScript()
    ->appendFile($this->layout()->staticBaseUrl . 'externals/fancyupload/Swiff.Uploader.js')
    ->appendFile($this->layout()->staticBaseUrl . 'externals/fancyupload/Fx.ProgressBar.js')
    ->appendFile($this->layout()->staticBaseUrl . 'externals/fancyupload/FancyUpload2.js');
  $this->headLink()
    ->appendStylesheet($this->layout()->staticBaseUrl . 'externals/fancyupload/fancyupload.css');
  $this->headTranslate(array(
    'Overall Progress ({total})', 'File Progress', 'Uploading "{name}"',
    'Upload: {bytesLoaded} with {rate}, {timeRemaining} remaining.', '{name}',
    'Remove', 'Click to remove this entry.', 'Upload failed',
    '{name} already added.',
    '{name} ({size}) is too small, the minimal file size is {fileSizeMin}.',
    '{name} ({size}) is too big, the maximal file size is {fileSizeMax}.',
    '{name} could not be added, amount of {fileListMax} files exceeded.',
    '{name} ({size}) is too big, overall filesize of {fileListSizeMax} exceeded.',
    'Server returned HTTP-Status <code>#{code}</code>',
    'Security error occurred ({text})',
    'Error caused a send or load operation to fail ({text})',
    'Add Photos'
  ));
?>

<script type="text/javascript">
    var sitealbumInstalled = true;
  en4.core.runonce.add(function() {
        var type = 'wall';
        if (composeInstance.options.type) type = composeInstance.options.type;
        composeInstance.addPlugin(new Composer.Plugin.Photo({
          title : '<?php echo $this->string()->escapeJavascript($this->translate('Add Photo')) ?>',
          albumTitle : '<?php echo $this->string()->escapeJavascript($this->translate('Create Photo Album')) ?>',
          lang : {
            'Add Photo' : '<?php echo $this->string()->escapeJavascript($this->translate('Add Photo')) ?>',
            'Select File' : '<?php echo $this->string()->escapeJavascript($this->translate('Select File')) ?>',
            'cancel' : '<?php echo $this->string()->escapeJavascript($this->translate('cancel')) ?>',
            'Loading...' : '<?php echo $this->string()->escapeJavascript($this->translate('Loading...')) ?>',
            'Unable to upload photo. Please click cancel and try again': '<?php echo $this->string()->escapeJavascript($this->translate('Unable to upload photo. Please click cancel and try again')) ?>'
          },
          requestOptions : {
            'url'  : en4.core.baseUrl + 'sitealbum/album/compose-upload/type/'+type,
            'flashEnable' : true
          },
          fancyUploadOptions : {
            'url'  : en4.core.baseUrl + 'sitealbum/album/compose-upload/format/json/type/'+type,
            'path' : en4.core.basePath + 'externals/fancyupload/Swiff.Uploader.swf'
          }
        }));
     
  });
</script>