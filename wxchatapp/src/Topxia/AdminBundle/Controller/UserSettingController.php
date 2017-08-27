<?php

namespace Topxia\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\FileToolkit;
use Topxia\Component\OAuthClient\OAuthClientFactory;
use Topxia\Service\Util\LiveClientFactory;
use Topxia\Service\Util\CloudClientFactory;

class UserSettingController extends BaseController
{
    public function authAction(Request $request)
    {
        $auth = $this->getSettingService()->get('auth', array());
        $defaultSettings = $this->getSettingService()->get('default', array());
        $userDefaultSetting = $this->getSettingService()->get('user_default', array());
        $courseDefaultSetting = $this->getSettingService()->get('course_default', array());
        $path = $this->container->getParameter('kernel.root_dir') . '/../web/assets/img/default/';
        $userDefaultSet = $this->getUserDefaultSet();
        $defaultSetting = array_merge($userDefaultSet, $userDefaultSetting);

        $default = array(
            'register_mode' => 'closed',
            'email_enabled' => 'closed',
            'setting_time' => -1,
            'email_activation_title' => '',
            'email_activation_body' => '',
            'welcome_enabled' => 'closed',
            'welcome_sender' => '',
            'welcome_methods' => array(),
            'welcome_title' => '',
            'welcome_body' => '',
            'user_terms' => 'closed',
            'user_terms_body' => '',
            'registerFieldNameArray' => array(),
            'registerSort' => array(0 => "email", 1 => "nickname", 2 => "password"),
            'captcha_enabled' => 0,
            'register_protective' => 'none',
            'nickname_enabled' => 0,
            'avatar_alert' => 'none',
        );

        if (isset($auth['captcha_enabled']) && $auth['captcha_enabled']) {

            if (!isset($auth['register_protective'])) {

                $auth['register_protective'] = "low";
            }

        }

        $auth = array_merge($default, $auth);
        if ($request->getMethod() == 'POST') {
            $defaultSetting = $request->request->all();

            if (isset($defaultSetting['user_name'])) {
                $defaultSetting['user_name'] = $defaultSetting['user_name'];
            } else {
                $defaultSetting['user_name'] = '顾客';
            }
            $userDefaultSetting =  ArrayToolkit::parts($defaultSetting, array(
                'defaultAvatar','user_name'
            ));

            $setting['nickname_enabled'] = $defaultSetting['nickname_enabled'];
            $setting['avatar_alert'] = $defaultSetting['avatar_alert'];

            $default = $this->getSettingService()->get('default', array());
            $defaultSetting = array_merge($default,$defaultSettings,$courseDefaultSetting,$userDefaultSetting);

            $this->getSettingService()->set('user_default', $userDefaultSetting);
            $this->getSettingService()->set('default', $defaultSetting);

            $this->getSettingService()->set('user_partner', $setting);

            if (isset($auth['setting_time']) && $auth['setting_time'] > 0) {
                $firstSettingTime = $auth['setting_time'];
                $auth = $request->request->all();
                $auth['setting_time'] = $firstSettingTime;
            } else {
                $auth = $request->request->all();
                $auth['setting_time'] = time();
            }

            if (empty($auth['welcome_methods'])) {
                $auth['welcome_methods'] = array();
            }

            if ($auth['register_protective'] == "none") {
                $auth['captcha_enabled'] = 0;
            } else {
                $auth['captcha_enabled'] = 1;
            }

            if($auth["register_mode"] == "email_or_mobile" ) {
                foreach ($auth['registerSort'] as $key => $value) {
                    if($value == "email" || $value == "mobile") {
                        unset($auth['registerSort'][$key]);
                    }
                }
                if(!in_array('emailOrMobile', $auth['registerSort'])) {
                    array_unshift($auth['registerSort'], 'emailOrMobile');
                }

                foreach ($auth['registerFieldNameArray'] as $key => $value) {
                    if($value == "email" || $value == "mobile") {
                        unset($auth['registerFieldNameArray'][$key]);
                    }
                }
                if(!in_array('emailOrMobile', $auth['registerFieldNameArray'])) {
                    array_unshift($auth['registerFieldNameArray'], 'emailOrMobile');
                }
            }


            if($auth["register_mode"] == "email") {
                foreach ($auth['registerSort'] as $key => $value) {
                    if($value == "emailOrMobile") {
                        unset($auth['registerSort'][$key]);
                    }
                }
                if(!in_array('email', $auth['registerSort'])) {
                    array_unshift($auth['registerSort'], 'email');
                }

                foreach ($auth['registerFieldNameArray'] as $key => $value) {
                    if($value == "emailOrMobile") {
                        unset($auth['registerFieldNameArray'][$key]);
                    }
                }
                if(!in_array('email', $auth['registerFieldNameArray'])) {
                    array_unshift($auth['registerFieldNameArray'], 'email');
                }
                if(!in_array('mobile', $auth['registerFieldNameArray'])) {
                    $auth['registerFieldNameArray'][] = 'mobile';
                }
            }

            $this->getSettingService()->set('auth', $auth);
            
            $this->getLogService()->info('system', 'update_settings', "更新注册设置", $auth);
            $this->setFlashMessage('success', '注册设置已保存！');
        }

        $userFields = $this->getUserFieldService()->getAllFieldsOrderBySeqAndEnabled();

        if ($auth['registerFieldNameArray']) {
            
            if($auth["register_mode"] == "email_or_mobile") {
                foreach ($auth['registerFieldNameArray'] as $key => $value) {
                    if($value == "email" || $value == "mobile") {
                        unset($auth['registerFieldNameArray'][$key]);
                    }
                }
                if(!in_array('emailOrMobile', $auth['registerFieldNameArray'])) {
                    array_unshift($auth['registerFieldNameArray'], 'emailOrMobile');
                }
            }


            if($auth["register_mode"] == "email") {

                foreach ($auth['registerFieldNameArray'] as $key => $value) {
                    if($value == "emailOrMobile") {
                        unset($auth['registerFieldNameArray'][$key]);
                    }
                }
                if(!in_array('email', $auth['registerFieldNameArray'])) {
                    array_unshift($auth['registerFieldNameArray'], 'email');
                }
                if(!in_array('mobile', $auth['registerFieldNameArray'])) {
                    $auth['registerFieldNameArray'][] = 'mobile';
                }
            }

            foreach ($userFields as $key => $fieldValue) {
                if (!in_array($fieldValue['fieldName'], $auth['registerFieldNameArray'])) {
                    $auth['registerFieldNameArray'][] = $fieldValue['fieldName'];
                }
            }

        }

        return $this->render('TopxiaAdminBundle:System:auth.html.twig', array(
            'auth' => $auth,
            'userFields' => $userFields,
            'defaultSetting' => $defaultSetting,
            'hasOwnCopyright' => false,
        ));
    }

    public function userAvatarAction(Request $request)
    {
        $defaultSetting = $this->getSettingService()->get('default', array());

        if ($request->getMethod() == 'POST') {
            $userDefaultSetting = $request->request->all();

            $userDefaultSetting =  ArrayToolkit::parts($userDefaultSetting, array(
                'defaultAvatar'
            )); 

            $defaultSetting = array_merge($defaultSetting,$userDefaultSetting);

            $this->getSettingService()->set('default', $defaultSetting);
            
            $this->getLogService()->info('system', 'update_settings', "更新头像设置", $userDefaultSetting);
            $this->setFlashMessage('success', '头像设置已保存！');
        }

        return $this->render('TopxiaAdminBundle:System:user-avatar.html.twig', array(
            'defaultSetting' => $defaultSetting,
        ));
    }

    public function loginConnectAction(Request $request)
    {
        $loginConnect = $this->getSettingService()->get('login_bind', array());

        $default = array(
            'login_limit' => 0,
            'enabled' => 0,
            'verify_code' => '',
            'captcha_enabled' => 0,
            'temporary_lock_enabled' => 0,
            'temporary_lock_allowed_times' => 5,
            'temporary_lock_minutes' => 20,
        );

        $clients = OAuthClientFactory::clients();
        foreach ($clients as $type => $client) {
            $default["{$type}_enabled"] = 0;
            $default["{$type}_key"] = '';
            $default["{$type}_secret"] = '';
            $default["{$type}_set_fill_account"] = 0;
        }

        $loginConnect = array_merge($default, $loginConnect);
        if ($request->getMethod() == 'POST') {
            $loginConnect = $request->request->all();
            $this->getSettingService()->set('login_bind', $loginConnect);
            $this->getLogService()->info('system', 'update_settings', "更新登录设置", $loginConnect);
            $this->setFlashMessage('success', '登录设置已保存！');
        }

        return $this->render('TopxiaAdminBundle:System:login-connect.html.twig', array(
            'loginConnect' => $loginConnect,
            'clients' => $clients,
        ));
    }

    public function userCenterAction(Request $request)
    {
        $setting = $this->getSettingService()->get('user_partner', array());

        $default = array(
            'mode' => 'default',
            'email_filter' => '',
        );

        $setting = array_merge($default, $setting);

        $configDirectory = $this->getServiceKernel()->getParameter('kernel.root_dir') . '/config/';
        $discuzConfigPath = $configDirectory . 'uc_client_config.php';
        $phpwindConfigPath = $configDirectory . 'windid_client_config.php';

        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $data['email_filter'] = trim(str_replace(array("\n\r", "\r\n", "\r"), "\n", $data['email_filter']));
            $setting = array('mode' => $data['mode'],
                'email_filter' => $data['email_filter'],
            );
            $this->getSettingService()->set('user_partner', $setting);

            $discuzConfig = $data['discuz_config'];
            $phpwindConfig = $data['phpwind_config'];

            if ($setting['mode'] == 'discuz') {
                if (!file_exists($discuzConfigPath) or !is_writeable($discuzConfigPath)) {
                    $this->setFlashMessage('danger', "配置文件{$discuzConfigPath}不可写，请打开此文件，复制Ucenter配置的内容，覆盖原文件的配置。");
                    goto response;
                }
                file_put_contents($discuzConfigPath, $discuzConfig);
            } elseif ($setting['mode'] == 'phpwind') {
                if (!file_exists($phpwindConfigPath) or !is_writeable($phpwindConfigPath)) {
                    $this->setFlashMessage('danger', "配置文件{$phpwindConfigPath}不可写，请打开此文件，复制WindID配置的内容，覆盖原文件的配置。");
                    goto response;
                }
                file_put_contents($phpwindConfigPath, $phpwindConfig);
            }

            $this->getLogService()->info('system', 'setting', "用户中心设置", $setting);
            $this->setFlashMessage('success', '用户中心设置已保存！');
        }

        if (file_exists($discuzConfigPath)) {
            $discuzConfig = file_get_contents($discuzConfigPath);
        } else {
            $discuzConfig = '';
        }

        if (file_exists($phpwindConfigPath)) {
            $phpwindConfig = file_get_contents($phpwindConfigPath);
        } else {
            $phpwindConfig = '';
        }

        response:
        return $this->render('TopxiaAdminBundle:System:user-center.html.twig', array(
            'setting' => $setting,
            'discuzConfig' => $discuzConfig,
            'phpwindConfig' => $phpwindConfig,
        ));
    }

    public function userFieldsAction()
    {

        $textCount = $this->getUserFieldService()->searchFieldCount(array('fieldName' => 'textField'));
        $intCount = $this->getUserFieldService()->searchFieldCount(array('fieldName' => 'intField'));
        $floatCount = $this->getUserFieldService()->searchFieldCount(array('fieldName' => 'floatField'));
        $dateCount = $this->getUserFieldService()->searchFieldCount(array('fieldName' => 'dateField'));
        $varcharCount = $this->getUserFieldService()->searchFieldCount(array('fieldName' => 'varcharField'));

        $fields = $this->getUserFieldService()->getAllFieldsOrderBySeq();
        for ($i = 0; $i < count($fields); $i++) {
            if (strstr($fields[$i]['fieldName'], "textField")) {
                $fields[$i]['fieldName'] = "多行文本";
            }

            if (strstr($fields[$i]['fieldName'], "varcharField")) {
                $fields[$i]['fieldName'] = "文本";
            }

            if (strstr($fields[$i]['fieldName'], "intField")) {
                $fields[$i]['fieldName'] = "整数";
            }

            if (strstr($fields[$i]['fieldName'], "floatField")) {
                $fields[$i]['fieldName'] = "小数";
            }

            if (strstr($fields[$i]['fieldName'], "dateField")) {
                $fields[$i]['fieldName'] = "日期";
            }

        }

        return $this->render('TopxiaAdminBundle:System:user-fields.html.twig', array(
            'textCount' => $textCount,
            'intCount' => $intCount,
            'floatCount' => $floatCount,
            'dateCount' => $dateCount,
            'varcharCount' => $varcharCount,
            'fields' => $fields,
        ));
    }

    public function editUserFieldsAction(Request $request, $id)
    {
        $field = $this->getUserFieldService()->getField($id);

        if (empty($field)) {
            throw $this->createNotFoundException();
        }

        if (strstr($field['fieldName'], "textField")) {
            $field['fieldName'] = "多行文本";
        }

        if (strstr($field['fieldName'], "varcharField")) {
            $field['fieldName'] = "文本";
        }

        if (strstr($field['fieldName'], "intField")) {
            $field['fieldName'] = "整数";
        }

        if (strstr($field['fieldName'], "floatField")) {
            $field['fieldName'] = "小数";
        }

        if (strstr($field['fieldName'], "dateField")) {
            $field['fieldName'] = "日期";
        }

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            if (isset($fields['enabled'])) {
                $fields['enabled'] = 1;
            } else {
                $fields['enabled'] = 0;
            }

            $field = $this->getUserFieldService()->updateField($id, $fields);

            return $this->redirect($this->generateUrl('admin_setting_user_fields'));
        }

        return $this->render('TopxiaAdminBundle:System:user-fields.modal.edit.html.twig', array(
            'field' => $field,
        ));
    }

    public function deleteUserFieldsAction(Request $request, $id)
    {
        $field = $this->getUserFieldService()->getField($id);

        if (empty($field)) {
            throw $this->createNotFoundException();
        }

        if ($request->getMethod() == 'POST') {

            $auth = $this->getSettingService()->get('auth', array());

            $courseSetting = $this->getSettingService()->get('course', array());

            if (isset($auth['registerFieldNameArray'])) {
                foreach ($auth['registerFieldNameArray'] as $key => $value) {
                    if ($value == $field['fieldName']) {
                        unset($auth['registerFieldNameArray'][$key]);
                    }

                }
            }
            if (isset($courseSetting['userinfoFieldNameArray'])) {
                foreach ($courseSetting['userinfoFieldNameArray'] as $key => $value) {
                    if ($value == $field['fieldName']) {
                        unset($courseSetting['userinfoFieldNameArray'][$key]);
                    }

                }
            }
            $this->getSettingService()->set('auth', $auth);

            $this->getSettingService()->set('course', $courseSetting);

            $this->getUserFieldService()->dropField($id);

            return $this->redirect($this->generateUrl('admin_setting_user_fields'));
        }

        return $this->render('TopxiaAdminBundle:System:user-fields.modal.delete.html.twig', array(
            'field' => $field,
        ));
    }

    public function addUserFieldsAction(Request $request)
    {
        $field = $request->request->all();
        if (isset($field['field_title'])
            && in_array($field['field_title'], array('真实姓名', '手机号码', 'QQ', '所在公司', '身份证号码', '性别', '职业', '微博', '微信'))) {
            throw $this->createAccessDeniedException('请勿添加与默认字段相同的自定义字段！');
        }

        $field = $this->getUserFieldService()->addUserField($field);

        if ($field == false) {
            $this->setFlashMessage('danger', '已经没有可以添加的字段了!');
        }

        return $this->redirect($this->generateUrl('admin_setting_user_fields'));
    }

    private function getUserDefaultSet()
    {
        $default = array(
            'defaultAvatar' => 0,
            'defaultAvatarFileName' => 'avatar',
            'articleShareContent' => '我正在看{{articletitle}}，关注{{sitename}}，分享知识，成就未来。',
            'courseShareContent' => '我正在浏览{{course}}，收获巨大哦，一起来浏览吧！',
            'groupShareContent' => '我在{{groupname}}小组,发表了{{threadname}},很不错哦,一起来看看吧!',
            'classroomShareContent' => '我正在浏览{{classroom}}，收获巨大哦，一起来浏览吧！',
            'user_name' => '顾客',
        );

        return $default;
    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService');
    }

    protected function getAppService()
    {
        return $this->getServiceKernel()->createService('CloudPlatform.AppService');
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }

    protected function getUserFieldService()
    {
        return $this->getServiceKernel()->createService('User.UserFieldService');
    }

    protected function getAuthService()
    {
        return $this->getServiceKernel()->createService('User.AuthService');
    }

}