<?php

namespace portalium\menu\controllers\web;

use Yii;
use portalium\menu\Module;
use yii\filters\VerbFilter;
use portalium\web\Controller;
use portalium\menu\models\Menu;
use yii\web\NotFoundHttpException;
use portalium\menu\models\MenuItem;
use portalium\menu\models\MenuItemSearch;
use portalium\menu\models\MenuRoute;
/**
 * MenuItemController implements the CRUD actions for MenuItem model.
 */
class ItemController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all MenuItem models.
     *
     * @return string
     */
    public function actionIndex($id_menu = null)
    {
        if (!\Yii::$app->user->can('menuWebItemIndex') && !\Yii::$app->user->can('menuWebItemIndexOwn')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        return $this->redirect(['/menu/item/create', 'id_menu' => $id_menu]);
    }

    /**
     * Displays a single MenuItem model.
     * @param int $id_item Item ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        if (!\Yii::$app->user->can('menuWebItemView')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }
    
    /**
     * Creates a new MenuItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate($id_menu, $id_item = null)
    {
        if (!\Yii::$app->user->can('menuWebItemCreate')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = new MenuItem();
        $model->style = '{"icon":"0xf0f6","color":"rgb(234, 153, 153)","iconSize":"24"}';
        if ($this->request->isPost) {
            if ($id_item != null) {
                $model = MenuItem::findOne($id_item);
                if($model == null){
                    $model = new MenuItem();
                    $model->style = '{"icon":"0xf0f6","color":"rgb(234, 153, 153)","iconSize":"24"}';
                }
            }
            if ($model->load($this->request->post())) {
                $model->id_menu = $id_menu;
                if($model->save()){
                    return;
                }
                else
                    Yii::warning($model->getErrors());
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'id_menu' => $id_menu,
        ]);
    }

    /**
     * Updates an existing MenuItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id_item Item ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        if (!\Yii::$app->user->can('menuWebItemUpdate', ['model' => $this->findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = $this->findModel($id);
        
        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            return $this->redirect(['index', 'id_menu' => $model->id_menu]);
        }

        return $this->render('update', [
            'model' => $model,
            'id_menu' => $model->id_menu,
        ]);
    }

    /**
     * Deletes an existing MenuItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id_item Item ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        if (!\Yii::$app->user->can('menuWebItemDelete', ['model' => $this->findModel($id)])) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $model = $this->findModel($id);
        $this->findModel($id)->delete();
        if (Yii::$app->request->isAjax) {
            return $this->asJson(['status' => 'success']);
        } else {
            return $this->redirect(['index', 'id_menu' => $model->id_menu]);
        }

    }

    public function actionRouteType() {
        if (!\Yii::$app->user->can('menuWebItemRouteType')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $out = [];
        if($this->request->isPost){
            $request = $this->request->post('depdrop_parents');
            $menuType = $request[0];
            $moduleName = $request[1];
            if($menuType == null || $moduleName == null || $menuType == '' || $moduleName == ''){
                return $this->asJson(['output' => [], 'selected' => '']);
            }
            $module = Yii::$app->getModule($moduleName);
            $menuItems = $module->getMenuItems();
            
            foreach ($menuItems[0] as $key => $value) {
                if($value['menu'] == $menuType)
                    $out[] = ['id' => $value['type'], 'name' => $value['type']];
            }
            $out = array_unique($out, SORT_REGULAR);
            return json_encode(['output' => $out, 'selected' => '']);
        }
    }

    public function actionRoute() {
        if (!\Yii::$app->user->can('menuWebItemRoute')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $out = [];
        if($this->request->isPost){
            $request = $this->request->post('depdrop_parents');
            $menuType = $request[0];
            $moduleName = $request[1];
            $routeType = $request[2];
            if($menuType == null || $moduleName == null || $routeType == null || $menuType == '' || $moduleName == '' || $routeType == ''){
                return $this->asJson(['output' => [], 'selected' => '']);
            }
            $module = Yii::$app->getModule($moduleName);
            $menuItems = $module->getMenuItems();

            foreach ($menuItems[0] as $key => $item) {
                if($item['type'] == $routeType && $item['menu'] == $menuType){
                    switch ($routeType) {
                        case 'widget':
                            $out[] = ['id' => $item['label'], 'name' => $item['name']];
                            break;
                        case 'model':
                            $out[] = ['id' => $item['route'], 'name' => $item['class']];
                            break;
                        case 'action':
                            $out[] = ['id' => $item['route'], 'name' => $item['route']];
                            break;
                        case 'route':
                            $routes = $item['routes'];
                            foreach ($routes as $key => $route) {
                                $out[] = ['id' => $key, 'name' => $route];
                            }
                            break;
                    }
                }
            }
            return json_encode(['output' => $out, 'selected' => '']);
        }
    }

    public function actionModel() {
        if (!\Yii::$app->user->can('menuWebItemModel')) {
            throw new \yii\web\ForbiddenHttpException(Module::t('You are not allowed to access this page.'));
        }
        $out = [];
        if($this->request->isPost){
            $request = $this->request->post('depdrop_parents');
            $menuType = $request[0];
            $moduleName = $request[1];
            $routeType = $request[2];
            $route = $request[3];
            if($menuType == null || $moduleName == null || $routeType == null || $route == null || $menuType == '' || $moduleName == '' || $routeType == '' || $route == ''){
                return $this->asJson(['output' => [], 'selected' => '']);
            }
            if($menuType == '' || $moduleName == '' || $routeType == '' || $route == ''){
                return json_encode(['output' => $out, 'selected' => '']);
            }
            $modelName = '';
            $module = Yii::$app->getModule($moduleName);
            $menuItems = $module->getMenuItems();
            $field = [];
            
            foreach ($menuItems[0] as $key => $item) {
                if($item['type'] == $routeType && $item['route'] == $route){
                    $field = $item['field'];
                    $modelName = $item['class'];

                }
            }
            if ($modelName != '') {
                $data = $modelName::find()->select(['id' => $field['id'], 'name' => $field['name']])->asArray()->all();
            }
               else {
                    $data = [];
                }
            return json_encode(['output' => $data, 'selected' => '']);
        }
    }

    public function actionRouteList(){
        $out = [];
        if($this->request->isPost){
            $request = $this->request->post('depdrop_parents');
            $moduleName = $request[1];
            $routeType = $request[2];
            if($moduleName == null || $routeType == null || $moduleName == '' || $routeType == ''){
                return $this->asJson(['output' => [], 'selected' => '']);
            }
            $menuRoutes = MenuRoute::find()->where(['module' => $moduleName])->asArray()->all();

            $data = MenuRoute::find()->select(['id' => 'id_menu_route', 'name' => 'title'])->asArray()->all();

            return json_encode(['output' => $data, 'selected' => '']);
        }
    }

    /**
     * Finds the MenuItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id_item Item ID
     * @return MenuItem the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id_item)
    {
        if (($model = MenuItem::findOne(['id_item' => $id_item])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Module::t('The requested page does not exist.'));
    }
}
