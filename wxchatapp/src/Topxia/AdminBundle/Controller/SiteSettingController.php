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

class SiteSettingController extends BaseController
{
    public function siteAction(Request $request)
    {
        $site = $this->getSettingService()->get('site', array());

        $default = array(
            'name' => '',
            'slogan' => '',
            'url' => '',
            'logo' => '',
            'seo_keywords' => '',
            'seo_description' => '',
            'master_email' => '',
            'icp' => '',
            'analytics' => '',
            'status' => 'open',
            'closed_note' => '',
            'favicon' => '',
            'copyright' => '',
        );

        $site = array_merge($default, $site);

        if ($request->getMethod() == 'POST') {
            $site = $request->request->all();
            $this->getSettingService()->set('site', $site);
            $this->getLogService()->info('system', 'update_settings', "更新站点设置", $site);
            $this->setFlashMessage('success', '站点信息设置已保存！');
        }

        return $this->render('TopxiaAdminBundle:System:site.html.twig', array(
            'site' => $site,
        ));
    }

    public function consultSettingAction(Request $request)
    {
        $consult = $this->getSettingService()->get('consult', array());
        $default = array(
            'enabled' => 0,
            'worktime' => '9:00 - 17:00',
            'qq' => array(
                array('name' => '', 'number' => ''),
            ),
            'qqgroup' => array(
                array('name' => '', 'number' => ''),
            ),
            'phone' => array(
                array('name' => '', 'number' => ''),
            ),
            'webchatURI' => '',
            'email' => '',
            'color' => 'default',
        );

        $consult = array_merge($default, $consult);
        if ($request->getMethod() == 'POST') {
            $consult = $request->request->all();
            ksort($consult['qq']);
            ksort($consult['qqgroup']);
            ksort($consult['phone']);
            $this->getSettingService()->set('consult', $consult);
            $this->getLogService()->info('system', 'update_settings', "更新QQ客服设置", $consult);
            $this->setFlashMessage('success', 'QQ客服设置已保存！');
        }
        return $this->render('TopxiaAdminBundle:System:consult-setting.html.twig', array(
            'consult' => $consult,
        ));
    }

    public function consultUploadAction(Request $request)
    {
        $file = $request->files->get('consult');
        if (!FileToolkit::isImageFile($file)) {
            throw $this->createAccessDeniedException('图片格式不正确！');
        }

        $filename = 'webchat.' . $file->getClientOriginalExtension();

        $directory = "{$this->container->getParameter('topxia.upload.public_directory')}/system";
        $file = $file->move($directory, $filename);

        $consult = $this->getSettingService()->get('consult', array());

        $consult['webchatURI'] = "{$this->container->getParameter('topxia.upload.public_url_path')}/system/{$filename}";
        $consult['webchatURI'] = ltrim($consult['webchatURI'], '/');

        $this->getSettingService()->set('consult', $consult);

        $this->getLogService()->info('system', 'update_settings', "更新微信二维码", array('webchatURI' => $consult['webchatURI']));

        $response = array(
            'path' => $consult['webchatURI'],
            'url' => $this->container->get('templating.helper.assets')->getUrl($consult['webchatURI']),
        );

        return new Response(json_encode($response));

    }

    public function shareAction(Request $request)
    {
        $defaultSetting = $this->getSettingService()->get('default', array());
        $default = $this->getDefaultSet();

        $defaultSetting = array_merge($default, $defaultSetting);

        if ($request->getMethod() == 'POST') {
            $defaultSetting = $request->request->all();
            $default = $this->getSettingService()->get('default', array());
            $defaultSetting = array_merge($default, $defaultSetting);

            $this->getSettingService()->set('default', $defaultSetting);
            $this->getLogService()->info('system', 'update_settings', "更新分享设置", $defaultSetting);
            $this->setFlashMessage('success', '分享设置已保存！');
        }

        return $this->render('TopxiaAdminBundle:System:share.html.twig', array(
            'defaultSetting' => $defaultSetting,
        ));
    }

    private function getDefaultSet()
    {
        $default = array(
            'defaultAvatar' => 0,
            'defaultCoursePicture' => 0,
            'defaultAvatarFileName' => 'avatar',
            'defaultCoursePictureFileName' => 'coursePicture',
            'articleShareContent' => '我正在看{{articletitle}}，关注{{sitename}}，分享知识，成就未来。',
            'courseShareContent' => '我正在浏览{{course}}，收获巨大哦，一起来浏览吧！',
            'groupShareContent' => '我在{{groupname}}小组,发表了{{threadname}},很不错哦,一起来看看吧!',
            'classroomShareContent' => '我正在浏览{{classroom}}，收获巨大哦，一起来浏览吧！',
            'user_name' => '顾客',
            'chapter_name' => '章',
            'part_name' => '节',
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