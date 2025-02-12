<?php
/**
 * Этот файл является частью модуля веб-приложения GearMagic.
 * 
 * @link https://gearmagic.ru
 * @copyright Copyright (c) 2015 Веб-студия GearMagic
 * @license https://gearmagic.ru/license/
 */

namespace Gm\Backend\Signin\Controller;

use Gm;
use Gm\Helper\Url;
use Gm\Panel\Http\Response;
use Gm\Mvc\Controller\Controller;

/**
 * Контроллер авторизации пользователя.
 * 
 * @author Anton Tivonenko <anton.tivonenko@gmail.com>
 * @package Gm\Backend\Signin\Controller
 * @since 1.0
 */
class IndexController extends Controller
{
    /**
     * {@inheritdoc}
     */
    protected string $defaultModel = 'Form';

    /**
     * {@inheritdoc}
     */
    public bool $enableCsrfValidation = true;

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        $behaviors = [
            'verb' => [
                'class'    => '\Gm\Filter\VerbFilter',
                'autoInit' => true,
                'actions'  => [
                    ''       => 'GET',
                    'verify' => ['POST', 'ajax' => 'GJAX']
                ]
            ]
        ];
        // настройки модуля
        $settings = $this->module->getSettings();
        // если в настройках установлен флаг "запись действий в журнал аудита"
        if ($settings->auditWrite) {
            $behaviors['audit'] = [
                'class'    => '\Gm\Panel\Behavior\AuditBehavior',
                'autoInit' => true,
                'allowed'  => 'verify',
                // изменение шаблона комментария в зависимости от успеха авторизации
                'commentCallback' => function () {
                    /** @var \Gm\Panel\Audit\Info $this */
                    if ($this->success) {
                        $comment = '{profile} at user account {user} use: {action} from module {module} at {date} from {ipaddress}';
                    } else {
                        $comment = 'Unsuccessful attempt to authorize the user in the module {module} under the account {user} with the password {password} in {date} the IP address {ipaddress} of the OS {os} in the browser {browser}';
                        $this->error = $this->error ? strip_tags($this->error) : null;
                    }
                    /** @var array $params параметры замены комментария */
                    $params = [
                        '@incut',
                        'profile'   => $this->unknown,
                        'user'      => $this->unknown,
                        'module'    => $this->moduleName ?: $this->unknown,
                        'date'      => $this->date ? Gm::$app->formatter->toDateTime($this->date) . ' (' . Gm::$app->formatter->timeZone->getName() . ')' : $this->unknown,
                        'ipaddress' => $this->ipaddress,
                        'browser'   => $this->getBrowserName(),
                        'os'        => $this->getOsName(),
                        'password'  => $this->unknown,
                    ];
                    /** @var \Gm\Backend\Signin\Model\Form $model */
                    if ($model = Gm::$app->controller->getLastModel()) {
                        $params['user']     = $model->username;
                        $params['password'] = $model->password;
                        if ($this->success) {
                            $this->userId   = $model->identity->id;
                            $this->userName = $model->identity->username;
                            /** @var \Gm\Panel\User\UserProfile $profile */
                            $profile = $model->identity->getProfile();
                            if ($profile) {
                                $this->userDetail  = $profile->name;
                                $params['profile'] = $profile->name ?: $this->unknown;
                            }
                        }
                    }
                    $comment = Gm::$app->module->t($comment, $params);
                    if ($model && $this->success) {
                        // если письмо отправлено на e-mail пользователю
                        if ($model->mailSentToUser) {
                            $comment .= '. ' . Gm::$app->module->t(
                                'Notification about the passage of authorization by the user {user} was sent to him by e-mail {email}',
                                [
                                    'user'  => $model->identity->username ?: $this->unknown,
                                    'email' => isset($profile) && $profile->email ? $profile->email : $this->unknown
                                ]
                            ) . '. ';
                        }
                        // если письмо отправлено на e-mail уведомителю
                        if ($model->mailSent) {
                            // настройки модуля
                            $settings =  Gm::$app->module->getSettings();
                            $comment .= Gm::$app->module->t(
                                'For control, information sent to email {email}',
                                [
                                    'email' => $settings->mail
                                ]
                            ) . '. ';
                        }
                    }
                    return $comment;
                }
            ];
        }
        return $behaviors;
    }

    /**
     * Действие "index" выводит страницы авторизации.
     * 
     * @return string
     */
    public function indexAction(): string
    {
        /** @var \Gm\Mvc\Application $app */
        $app = Gm::$app;

        // если запрос из панели управления, перенаправляем обратно, но уже с командой
        if ($app->request->isGjax()) {
            return $this->getResponse(Response::FORMAT_JSONG)
                ->meta
                    ->cmdRedirect(Url::to(Gm::alias('@route')));
        }

        // при обращении к странице авторизации, пользователя перенаправляет в панель управления
        if (Gm::hasUserIdentity(BACKEND_SIDE_INDEX)) {
            return $this->getResponse()->redirect(Gm::alias('@backend', '/workspace'), false);
        }

        // загаловок страницы
        $app->page
            ->setTitle($this->t('Login page'))
            ->script
                ->meta->csrfTokenTag();

        // для восстановления
        $url = ['signin/verify'];
        /** @var \Gm\Backend\Recovery\Helper\Helper $helper */
        $helper = Gm::$app->modules->getObject('Helper\Helper', 'gm.be.recovery');
        if ($helper !== null) {
            if ($recoveryToken = Gm::$app->request->get($helper->recoveryTokenName, null)) {
                $url['?'] = [$helper->recoveryTokenName => $recoveryToken];
            }
        }

        /** @var $version \Gm\Version\AppVersion */
        $version = $app->version;
        /** @var $edition  \Gm\Version\Edition */
        $edition = $app->version->getEdition();

        return $this->render('/form', [
            'appTitle'  => $app->language->isRu() ? $version->originalName : $version->name,
            'formTitle' => $app->language->isRu() ? $edition->originalName : $edition->name,
            'formUrl'   => Url::toBackend($url)
        ], [
            'useTheme'    => true, 
            'useLocalize' => true
        ]);
    }

    /**
     * Действие "verify" проверяет авторизацию пользователя.
     * 
     * @return Response
     */
    public function verifyAction(): Response
    {
        /** @var Response $response */
        $response = $this->getResponse(Response::FORMAT_JSONG);
        /** @var \Gm\Http\Request $request */
        $request = Gm::$app->request;

        // определение метода запроса
        if (!$request->isPost) {
            $response
                ->meta->error(Gm::t(BACKEND, 'Invalid query method'));
            return $response;
        }

        /** @var \Gm\Backend\Signin\Model\Form $form Модель данных формы */
        $form = $this->getModel($this->defaultModel);
        if ($form === false) {
            $response
                ->meta->error(Gm::t('app', 'Could not defined data model "{0}"', [$this->defaultModel]));    
            return $response;
        }

        // загрузка атрибутов в модель из запроса
        if (!$form->load($request->getPost())) {
            $response
                ->meta->error(Gm::t(BACKEND, 'No data to perform action'));
            return $response;
        }

        // валидация атрибутов модели
        if (!$form->validate()) {
            $response
                ->meta->error($this->t($form->getError()));
            return $response;
        }

        // проверка аккаунта пользователя
        if (($result = $form->signin()) !== true) {
            $response
                ->meta->error($this->t($result));
            return $response;
        }
        $response->meta->redirect = Url::toBackend('workspace');
        $response->meta->success($this->t('You have successfully logged in'));
        return $response;
    }
}
