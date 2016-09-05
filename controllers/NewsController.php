<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/1/2016
 * Time: 2:57 PM
 */

namespace humhub\modules\news\controllers;

use humhub\modules\content\components\ContentContainerController;
use humhub\modules\content\models\Content;
use humhub\modules\file\models\File;
use humhub\modules\news\models\EditForm;


use humhub\modules\news\models\News;
use humhub\modules\news\models\NewsLayouts;

use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Url;
use yii\web\UploadedFile;
use  humhub\modules\news\models\UploadForm;


class NewsController extends ContentContainerController
{

    public function actions()
    {
        return array(
            'stream' => array(
                'class' => \humhub\modules\news\components\StreamAction::className(),
                'mode' => \humhub\modules\news\components\StreamAction::MODE_NORMAL,
                'contentContainer' => $this->contentContainer
            ),
        ); // TODO: Change the autogenerated stub
    }

    public function actionCreate()
    {


        if (!$this->contentContainer->permissionManager->can(new \humhub\modules\news\permissions\CreateNews())) {
            throw new HttpException(400, 'Access denied!');
        }

        $fileList = Yii::$app->request->post('fileList');
        $newsModel = new News();
        if ($fileList == "") {
            //news story without images
        } else {
            //with images
            $fileItems = explode(",", $fileList);
//            echo $fileItems[0]."|".$fileItems[1];
            $imageGuid = $fileItems[1];
            $newsModel->imgfile = $imageGuid;

        }


        $text = Yii::$app->request->post('text');
        $newsModel->title = Yii::$app->request->post('title');
        $newsModel->text = $text;
        $newsModel->created_at = date('Y-m-d h:i:s ', time());


        $authorList = Yii::$app->request->post('changeAuthor');
        if ($authorList == "") {
            $newsModel->created_by = Yii::$app->user->id;
//            return "inga";
        } else {
            $authors = explode(",", $authorList);
            $assgnedAuthor = $authors[0];
            $authorId = User::findOne(['guid' => $assgnedAuthor]);
            $newsModel->created_by = $authorId->id;
            $newsModel->content->created_by = $authorId->id;
            //this code was deprecated in humhub version 1.1
//            $newsModel->content->user_id = $authorId->id;
            $newsModel->content->created_by  = $authorId->id;

        }


        $parsedLayoutId = Yii::$app->request->post('lay');
        if ($parsedLayoutId != "") {
            $newsLayout = NewsLayouts::findOne([
                'id' => $parsedLayoutId
            ]);
        } else {
            $newsLayout = NewsLayouts::findOne(['name' => 'default']);
        }


        $newsModel->layout_id = $newsLayout->id;


//        print_r($_FILES);
//        die();


//        return var_dump($_FILES['imageFile']);
//        return var_dump($_FILES);


//           $newsModel->imageFile = UploadedFile::getInstance($newsModel,'bannerfilestwo');
//        $newsModel->imageFile->saveAs( 'uploads/'.$newsModel->imageFile->basename.'.'.$newsModel->imageFile->extension );


//            $newsModel->imageFile = 'uploads/'.$newsModel->file->basename.'.'.$newsModel->file->extension;

//        $newsModel->imageFile->saveAs( 'uploads/'.$newsModel->imageFile->baseName . '.' . $newsModel->imageFile->extension);

//        $newsModel->imageFile->saveAs( 'uploads/'.$newsModel->imageFile->baseName . '.' . $newsModel->imageFile->extension);
//        $newsModel->image=UploadedFile::getInstance($newsModel, 'file');
//        $imageName=rand();

        return \humhub\modules\news\widgets\WallCreateForm::create($newsModel, $this->contentContainer);


    }

    public function actionUpload()
    {

        $model = new UploadForm();
//        $model = new News();
        if (\Yii::$app->request->isPost) {

            $newsModel = new News();

            $text = Yii::$app->request->post('text');
            $model->title = Yii::$app->request->post('title');
            $model->text = $text;
            $model->created_at = date('Y-m-d h:i:s ', time());
            $model->created_by = Yii::$app->user->id;

            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
//            $model->imageFile->saveAs( 'uploads/'. rand() . '.' . $model->imageFile->extension);


//            $model->imageFile=UploadedFile::getInstance($model, 'imageFile');
            if ($model->upload()) {
                $model->imageFile->saveAs('/uploads/' . rand() . '.' . $model->imageFile->extension);
//          -  $model->imageFile->saveAs( 'uploads/'.$model->imageFile->baseName . '.' . $model->imageFile->extension);
                return;
            }
            return \humhub\modules\news\widgets\WallCreateForm::create($model, $this->contentContainer);

        }

        return $this->render('testupload', ['model' => $model]);

    }

    public function actionShow()
    {

        $newsForm = new EditForm();
        $model = new News();
        $layouts = NewsLayouts::find()
            ->all();
        $userId = Yii::$app->user->id;;



        return $this->render('show', array(
            'model' => $model,
            'contentContainer' => $this->contentContainer,
            'layouts' => $layouts
        ));
    }

    public function actionView()
    {
        $idnews = (int)Yii::$app->request->getQueryParam('id');
        $news = News::findOne(['id' => $idnews]);
        if ($news) {
            $user = User::findOne(['id' => $news->created_by]);

            return $this->render('view', ['news' => $news, 'user' => $user, 'space' => $this->contentContainer]);
        } else {
            $this->redirect(Url::toRoute('/news/news/show'));
        }


    }

    public function actionDemo()
    {
        return $this->render('test', ['cont' => $this->contentContainer]);
    }

    public function actionTestupload()
    {
        \Yii::$app->response->format = 'json';


        /*  print_r($_FILES);
          die();*/

        $model = new \humhub\models\forms\UploadProfileImage();
        $json = array();

        $files = \yii\web\UploadedFile::getInstancesByName('bannerfilestwo');
        $file = $files[0];
        $model->image = $file;
        $model->image->saveAs(rand(1000, 10000) . '.' . $file->extension);
//        $model->image->saveAs('/uploads/'.$file->baseName.'.'.$file->extension);
    }

    public function actionEdit()
    {

        $id = Yii::$app->request->get('id');
        $model = News::findOne(['id' => $id]);
//        return $model->id;
        $edited = false;
        $layouts = NewsLayouts::find()
            ->all();
//        $model->title=Yii::$app->request->post('title');
//        $model->text=Yii::$app->request->post('text');
        if ($model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = 'json';
            $result = [];
            if ($model->validate()) {
                $authorInputId="news_input_author".$id;
                $authorList = Yii::$app->request->post($authorInputId);
                $authorChanged=false;
                if(!($authorList=="")){
                    $authors = explode(",", $authorList);
                    $assgnedAuthor = $authors[0];
                    $authorId=User::findOne(['guid'=>$assgnedAuthor]);
//                    $model->content->user_id=$authorId->id;
                    $model->content->created_by =$authorId->id;
                    $authorChanged=true;
                }
                $parsedLayoutId=Yii::$app->request->post('news_input_layout');
                if($parsedLayoutId != ""){
                    $newsLayout = NewsLayouts::findOne([
                        'id' => $parsedLayoutId
                    ]);
                    if(is_null($newsLayout)){
                        $newsLayout=NewsLayouts::findOne(['name'=>'default']);
                        $model->layout_id=$newsLayout->id;
                    }else{
                        $model->layout_id=$newsLayout->id;
                    }
                }

                $editguid=Yii::$app->request->post('editguid');
//                if(!($editguid == "" || $editguid == null)){
                if($editguid != "" ){

                    $model->imgfile=$editguid;
                }


//                $model->imageFile=UploadedFile::getInstance($model,"imagefile" );
//                $model->imageFile->saveAs($model->imageFile->baseName.$model->imageFile->extension);
                if($model->save()){
                    $model = News::findOne(['id' => $id]);
                    $result['success'] = true;
                    $result['output'] = $this->renderAjaxContent($model->getWallOut(['justEdited' => false]));
//                    $result['output'] = $this->renderAjaxContent($model->getWallOut(['justEdited' => true]));
                   /* if($authorChanged){
                        $result['authorchanged']=true;
                    }*/
                }
            } else {
                $result['errors'] = $model->getErrors();
            }
            return $result;
        }
        return $this->renderAjax('edit', ['news' => $model,
            'edited' => $edited,
            'layouts'=>$layouts,
            'contentContainer' => $this->contentContainer,]);

        /*$id = Yii::$app->request->get('id');
        $model = News::findOne(['id' => $id]);
//        return $model->id;
        $edited = false;
        $layouts = NewsLayouts::find()
            ->all();

        if ($model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = 'json';
            $result = [];
            if ($model->validate()) {
                $authorList = Yii::$app->request->post('news_input_author');
                $authorChanged=false;
                if(!($authorList=="")){
                    $authors = explode(",", $authorList);
                    $assgnedAuthor = $authors[0];
                    $authorId=User::findOne(['guid'=>$assgnedAuthor]);
                    $model->content->user_id=$authorId->id;
                    $authorChanged=true;
                }
                $parsedLayoutId=Yii::$app->request->post('news_input_layout');
                if($parsedLayoutId != ""){
                    $newsLayout = NewsLayouts::findOne([
                        'id' => $parsedLayoutId
                    ]);
                    if(is_null($newsLayout)){
                        $newsLayout=NewsLayouts::findOne(['name'=>'default']);
                        $model->layout_id=$newsLayout->id;
                    }else{
                        $model->layout_id=$newsLayout->id;
                    }
                }
//                $model->imageFile=UploadedFile::getInstance($model,"imagefile" );
//                $model->imageFile->saveAs($model->imageFile->baseName.$model->imageFile->extension);
                $model->title=Yii::$app->request->post('title');
                $model->text=Yii::$app->request->post('text');
                if($model->save()){
                    $model = News::findOne(['id' => $id]);
                    $result['success'] = true;
                    $result['output'] = $this->renderAjaxContent($model->getWallOut(['justEdited' => true]));
                    if($authorChanged){
                        $result['authorchanged']=true;
                    }
                }
            } else {
                $result['errors'] = $model->getErrors();
            }
            return $result;
        }
        return $this->renderAjax('edit', ['news' => $model,
            'edited' => $edited,
            'layouts'=>$layouts,
            'contentContainer' => $this->contentContainer,]);*/
    }

    public function actionReload()
    {
        $id = Yii::$app->request->get('id');
        $model = News::findOne(['id' => $id]);
//        return $model->id;
        return $this->renderAjaxContent($model->getWallOut(['justEdited' => true]));
    }

  /*  public function actionChangelayout()
    {

        $layId = Yii::$app->request->post('lay');
        $userId = Yii::$app->user->id;
        Yii::$app->response->format = 'json';
        $result = [];
        $layoutFound = NewsLayouts::findOne(['id' => $layId]);
//        if(is_null($layoutFound)){
        if (!(is_null($layoutFound))) {
            $result['found'] = true;
            $userNewsLayoutFound = UsersNewsLayout::findOne(['userid' => $userId]);
            if (is_null($userNewsLayoutFound)) {

                $userNewsLayout = new UsersNewsLayout();

                $userNewsLayout->userid = $userId;
                $userNewsLayout->layoutid = $layoutFound->id;
                $userNewsLayout->changed_at = date('Y-m-d h:i:s ', time());

                $userNewsLayout->save();

            } else {
                $userNewsLayoutFound->layoutid = $layoutFound->id;
                $userNewsLayoutFound->changed_at = date('Y-m-d h:i:s ', time());
                $userNewsLayoutFound->update();
                $result['status'] = 'updated';

            }
        } else {
            $result['found'] = false;
        }


        return $result;
    }*/

    public function actionList()
    {
//        $query=News::find()->con
    }

    public function actionTest()
    {
        $request = Yii::$app->request;
        $model = new News();
        if ($model->load($request->post()) && $model->validate()) {
            $newsModel = new News();
            $newsModel->title = Yii::$app->request->post('title');
            $newsModel->text = Yii::$app->request->post('text');;
            $newsModel->created_at = date('Y-m-d h:i:s ', time());
            $newsModel->created_by = Yii::$app->user->id;

            $newsModel->file = UploadedFile::getInstance($newsModel, 'file');
            $imageName = rand();
//        $newsModel->file->saveAs('@app/uploads/news/'.$imageName.$newsModel->imgfile->extension);
            $newsModel->file->saveAs($imageName . $newsModel->file->extension);
            $newsModel->imgfile = '/uploads/news/' . $imageName . $newsModel->file->extension;
            $newsModel->save();
        }
        return $this->render('test', ['model' => $model]);
    }

    public function actionTestcreate()
    {

    }

    public function actionRemoveimage()
    {
        $id = Yii::$app->request->get('id');
        $image = Yii::$app->request->get('image');
        $file =new File();
         if($file->canDelete()){

             $news=News::findOne([
                'id'=>$id
             ]);
             $news->imgfile="";
             $news->update();
             $imageFile=File::findOne([
                 'guid'=>$image,
             ]);
             $imageFile->delete();
         }
    }
    public function actionEditimage(){


//        $imageGUID="44";
//        echo $imageGUID;
    }


}