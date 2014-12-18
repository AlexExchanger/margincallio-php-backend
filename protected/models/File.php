<?php

class File extends CActiveRecord {

    public $fileItem;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'file';
    }

    public function rules() {
        return array(
            array('fileName', 'length', 'allowEmpty' => false, 'min' => 1, 'max' => 255),
            array('fileSize', 'numerical', 'allowEmpty' => false, 'min' => 0, 'max' => 5 * 1024 * 1024, 'integerOnly' => true),
            array('fileItem', 'file', 'types'=>'jpg, gif, png'),
        );
    }

    public function deleteDocument(){
        $documentPath=Yii::getPathOfAlias('webroot.files').DIRECTORY_SEPARATOR.
            $this->fileItem;
        if(is_file($documentPath))
            unlink($documentPath);
    }
    
    public static function getDocumentsByUserId($userId) {
        return File::model()->findAllByAttributes(array('createdBy' => $userId, 'entityType' => 'user'), array('order' => 'id ASC'));
    }

    public static function getByUid($uid) {
        if (!$uid) {
            return null;
        }
        return File::model()->findByAttributes(['uid' => $uid]);
    }

    public static function assign($entity, File $file, $userId) {
        if (!is_null($file->createdBy)) {
            throw new ModelException('File already assigned');
        }

        $file->createdBy = $userId;

        $entityType = self::getEntityType($entity);
        if (!$entityType) {
            throw new ModelException('Invalid entityType');
        }

        if ($entityType == 'news') {
            //только 1 файл на новость
            $criteria = new CDbCriteria();
            $criteria->compare('entityType', 'news');
            $criteria->compare('entityId', $entity->id);
            self::model()->updateAll(['entityId' => null], $criteria);
        }

        $file->entityType = $entityType;
        $file->entityId = $entity->id;

        $file->update(['createdBy', 'entityType', 'entityId']);
        return true;
    }

    public static function bindTo(array $entities) {
        $entityTypes = [
            'news' => [],
            'ticket' => [],
        ];

        foreach ($entities as $entity) {
            $entityType = self::getEntityType($entity);
            $entityTypes[$entityType][$entity->id] = [];
        }

        foreach ($entityTypes as $type => $ids) {
            if ($ids) {
                $files = self::model()->findAllByAttributes(['entityType' => $type, 'entityId' => array_keys($ids)]);
                foreach ($files as $file) {
                    $entityTypes[$type][$file->entityId][] = $file;
                }
            }
        }

        foreach ($entities as $entity) {
            $entity->files = ArrayHelper::getFromArray($entityTypes[self::getEntityType($entity)], $entity->id, []);
        }
    }

    private static function getEntityType($entity) {
        if ($entity instanceof News) {
            return 'news';
        } elseif ($entity instanceof TicketMessage) {
            return 'ticket';
        } elseif ($entity instanceof User) {
            return 'user';
        }
        return null;
    }

    public static function getLink(File $file) {
        $array = str_split($file->uid, 2);
        $path = "$array[0]/$array[1]/$array[2]/$file->uid." . pathinfo($file->fileName, PATHINFO_EXTENSION);

        $domain = Yii::app()->params['services']['file']['domain'];
        $secret = Yii::app()->params['services']['file']['nginxHash'];
        $time = TIME + 3600 * 1; //сколько ссылка будет рабочей
        $key = str_replace("=", "", strtr(base64_encode(md5($secret . $path . $time, TRUE)), "+/", "-_"));
        $encoded_url = "http://$domain/s/$key/$time/$path";
        return $encoded_url;
    }

    public static function create($userId, $fileName, $fileSize, $mimeType) {
        $file = new File();
        $file->uid = Guid::generate();
        $file->fileName = $fileName;
        $file->fileSize = $fileSize;
        $file->mimeType = $mimeType;
        $file->entityType = null;
        $file->entityId = null;
        $file->createdAt = TIME;
        $file->createdBy = $userId;

        if (!$file->save()) {
            throw new ModelException('File was not saved', $file->getErrors());
        }
        return $file;
    }
    
    public static function getUserDoc($userId) {
        $result = self::model()->findAllByAttributes(array(
            'createdBy' => $userId
        ));
        
        $data = array();
        foreach($result as $value) {
            $data[] = array(
                'url' => '/files/'.$value->uid,
                'uid' => $value->uid,
                'type' => $value->entityType
            );
        }
        
        return $data;
    }

}
