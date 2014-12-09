<?php if($loaded): ?>
    File successfuly loaded!
<?php else: ?>
<?php $form=$this->beginWidget('CActiveForm',array(
    'htmlOptions'=>array('enctype'=>'multipart/form-data'),
)); ?>
    <?php /* поле для загрузки файла */ ?>
    <div class="field">
        <?php if($model->fileItem): ?>
            <p><?php echo CHtml::encode($model->fileItem); ?></p>
        <?php endif; ?>
        <?php echo $form->labelEx($model,'fileItem'); ?>
        <?php echo $form->fileField($model,'fileItem'); ?>
        <?php echo $form->error($model,'fileItem'); ?>
    </div>
    
    <?php /* кнопка отправки */ ?>
    <div class="button">
        <?php echo CHtml::submitButton($model->isNewRecord ? 'Создать' : 'Сохранить'); ?>
    </div>
<?php $this->endWidget(); ?>
<?php endif; ?>