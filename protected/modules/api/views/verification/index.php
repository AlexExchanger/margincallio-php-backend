<?php if($loaded): ?>
    File successfuly loaded!
<?php else: ?>
<?php $form=$this->beginWidget('CActiveForm',array(
    'htmlOptions'=>array('enctype'=>'multipart/form-data'),
)); ?>
    <?php /* поле для загрузки файла */ ?>
    <div class="field">
        <label for="file-first">File Item first</label>
        <input id="fileItem1" type="hidden" value="" name="fileItem1">
        <input name="fileItem1" id="yfileItem1" type="file">
        <label for="file-second">File Item second</label>
        <input id="fileItem2" type="hidden" value="" name="fileItem2">
        <input name="fileItem2" id="yfileItem2" type="file">
    </div>
    
    <?php /* кнопка отправки */ ?>
    <div class="button">
        <?php echo CHtml::submitButton('Сохранить'); ?>
    </div>
<?php $this->endWidget(); ?>
<?php endif; ?>