<?php

use Composer\Autoload\ClassLoader;

require __DIR__.'/../../vendor/autoload.php';

$loader = new Twig_Loader_Filesystem(__DIR__ . '/templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false,
));

$twig->addGlobal('edusho_version', \Topxia\System::VERSION);

$step =intval(empty($_GET['step']) ? 0 : $_GET['step']);

$functionName = 'install_step' . $step;

$functionName();

use Topxia\Service\Common\ServiceKernel;
use Topxia\Service\User\CurrentUser;
use Topxia\Service\CloudPlatform\KeyApplier;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Topxia\Common\BlockToolkit;

function check_installed()
{
    if (array_key_exists('nokey', $_GET)) {
        setcookie('nokey', 1);
        $_COOKIE['nokey'] = 1;
    }

    if (file_exists(__DIR__ . '/../../app/data/install.lock')) {
        exit('already install.');
    }
}

function install_step0()
{
    check_installed();

    global $twig;
    echo $twig->render('step-0.html.twig', array('step' => 0));
}

function install_step1()
{
    check_installed();
    global $twig;

    $pass = true;

    $env = array();
    $env['os'] = PHP_OS;
    $env['phpVersion'] = PHP_VERSION;
    $env['phpVersionOk'] = version_compare(PHP_VERSION, '5.3.0') >= 0;
    $env['pdoMysqlOk'] = extension_loaded('pdo_mysql');
    $env['uploadMaxFilesize'] = ini_get('upload_max_filesize');
    $env['uploadMaxFilesizeOk'] = intval($env['uploadMaxFilesize']) >= 2;
    $env['postMaxsize'] = ini_get('post_max_size');
    $env['postMaxsizeOk'] = intval($env['postMaxsize']) >= 8;
    $env['maxExecutionTime'] = ini_get('max_execution_time');
    $env['maxExecutionTimeOk'] = ini_get('max_execution_time') >= 30;
    $env['mbstringOk'] = extension_loaded('mbstring');
    $env['gdOk'] = extension_loaded('gd');
    $env['curlOk'] = extension_loaded('curl');
    
    if (!$env['phpVersionOk'] or 
        !$env['pdoMysqlOk'] or 
        !$env['uploadMaxFilesizeOk'] or 
        !$env['postMaxsizeOk'] or 
        !$env['maxExecutionTimeOk'] or
        !$env['mbstringOk'] or
        !$env['curlOk'] or
        !$env['gdOk']) {
        $pass = false;
    }

    $paths = array(
        'app/config/parameters.yml',
        'app/data/udisk',
        'app/data/private_files',
        'web/files',
        'web/install',
        'app/cache',
        'app/data',
        'app/logs',
    );

    $checkedPaths = array();
    foreach ($paths as $path) {
        $checkedPath = __DIR__ . '/../../' . $path;
        $checked = is_executable($checkedPath) && is_writable($checkedPath) && is_readable($checkedPath);
        if (PHP_OS == 'WINNT') {
            $checked = true;
        }
        if (!$checked) {
            $pass = false;
        }
        $checkedPaths[$path] = $checked;
    }
    $safemode = ini_get('safe_mode');
    if($safemode == 'On')
       $pass = false;

    echo $twig->render('step-1.html.twig', array(
        'step' => 1,
        'env' => $env,
        'paths' => $checkedPaths,
        'safemode' => $safemode,
        'pass' => $pass
    ));
}

function install_step2()
{
    check_installed();
    global $twig;

    $error = null;
    $post = array();
    if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
        $post = $_POST;

        $replace = empty($_POST['database_replace']) ? false : true;

        $error = _create_database($_POST, $replace);
        if (empty($error)) {
            $error = _create_config($_POST);
        }
        if (empty($error)) {
            header("Location: start-install.php?step=3");
            exit(); 
        }
    }

    echo $twig->render('step-2.html.twig', array(
        'step' => 2,
        'error' => $error,
        'post' => $post,
    ));
}

function install_step3()
{
    check_installed();
    global $twig;

    $connection = _create_connection();

    $serviceKernel = ServiceKernel::create('prod', true);
    $serviceKernel->setParameterBag(new ParameterBag(array(
        'kernel' => array(
            'root_dir' => realpath(__DIR__ . '/../../app'),
        )
    )));
    $serviceKernel->setConnection($connection);

    $error = null;
    if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {

        $init = new SystemInit();
        $admin = $init->initAdmin($_POST);
        $init->initSiteSettings($_POST);
        $init->initRegisterSetting($admin);
        $init->initMailerSetting($_POST['sitename']);
        $init->initPaymentSetting();
        $init->initStorageSetting();
        $init->initTag();
        $init->initCategory();
        $init->initFile();
        $init->initPages();
        $init->initNavigations();
        $init->initBlocks();
        $init->initThemes();
        $init->initLockFile();
        $init->initRefundSetting();
        $init->initArticleSetting();
        $init->initDefaultSetting();

        header("Location: start-install.php?step=4");
        exit();
    }

    echo $twig->render('step-3.html.twig', array(
        'step' => 3,
        'error' => $error,
        'request' => $_POST,
    ));
}

function install_step4()
{
    global $twig;
    
        $userAgent = 'wxchatapp Install Client 1.0';
        $connectTimeout = 10;
        $timeout = 10;
        $url = "http://open.edusoho.com/api/v1/block/two_dimension_code";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_URL, $url );
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);

    echo $twig->render('step-4.html.twig', array(
        'step' => 4,
        "response"=>$response,
    ));
}

function install_step5()
{
    try {
        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__);
    } catch(\Exception $e) {

    }

    header("Location: ../app.php/");
    exit(); 
}

/**
 * 生产Key
 */
function install_step999()
{
    if (empty($_COOKIE['nokey'])) {
        
        if (empty($_SESSION)){

            session_start();
            
        }

        $connection = _create_connection();
        $serviceKernel = ServiceKernel::create('prod', true);
        $serviceKernel->setParameterBag(new ParameterBag(array(
            'kernel' => array(
                'root_dir' => realpath(__DIR__ . '/../../app'),
            )
        )));

        $serviceKernel->setConnection($connection);

        $init = new SystemInit();

        $key = $init->initKey();

        echo json_encode($key);
    } else {
        echo json_encode(array(
            'accessKey' => '__NOKEY__',
            'secretKey' => '__NOKEY__',
        ));
    }
}

function _create_database($config, $replace)
{
    try {
        $pdo = new PDO("mysql:host={$config['database_host']};port={$config['database_port']}", "{$config['database_user']}", "{$config['database_password']}");

        $pdo->exec("SET NAMES utf8");

        $result = $pdo->exec("create database `{$config['database_name']}`;");
        if (empty($result) and !$replace) {
            return "数据库{$config['database_name']}已存在，创建失败，请删除或者勾选覆盖数据库之后再安装！";
        }

        $pdo->exec("USE `{$config['database_name']}`;");

        $sql = file_get_contents('./wxchatapp.sql');
        $result = $pdo->exec($sql);
        if ($result === false) {
            return "创建数据库表结构失败，请删除数据库后重试！";
        }

        return null;

    } catch (\PDOException $e) {
        return '数据库连接不上，请检查数据库服务器、用户名、密码是否正确!';
    }
}

function _create_config($config)
{
    $secret = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    $config = "parameters:
    database_driver: pdo_mysql
    database_host: {$config['database_host']}
    database_port: {$config['database_port']}
    database_name: {$config['database_name']}
    database_user: {$config['database_user']}
    database_password: '{$config['database_password']}'
    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: null
    mailer_password: null
    locale: zh_CN
    secret: {$secret}
    user_partner: none";

    file_put_contents(__DIR__ . "/../../app/config/parameters.yml", $config);
}

function _create_connection()
{
     $factory = new \Doctrine\Bundle\DoctrineBundle\ConnectionFactory(array()); 
     $parameters = file_get_contents(__DIR__ . "/../../app/config/parameters.yml");
     $parameters = \Symfony\Component\Yaml\Yaml::parse($parameters);
     $parameters = $parameters['parameters'];

     $connection = $factory->createConnection(
        array(
            'dbname' => $parameters['database_name'], 
            'host' => $parameters['database_host'], 
            'port' => $parameters['database_port'], 
            'user' => $parameters['database_user'], 
            'password' => $parameters['database_password'], 
            'charset' => 'UTF8', 
            'driver' => $parameters['database_driver'], '
            driverOptions' => array())
        ,
        new \Doctrine\DBAL\Configuration(),
        null,
        array('enum' => 'string')
    );

    $connection->exec("SET NAMES utf8");

    return $connection;
}

class SystemInit
{

    public function initAdmin($user)
    {
        $user = $user = $this->getUserService()->register($user);
        $user['roles'] =  array('ROLE_USER', 'ROLE_TEACHER', 'ROLE_SUPER_ADMIN');
        $user['currentIp'] = '127.0.0.1';

        $currentUser = new CurrentUser();
        $currentUser->fromArray($user);
        ServiceKernel::instance()->setCurrentUser($currentUser);

        $this->getUserService()->changeUserRoles($user['id'], array('ROLE_USER', 'ROLE_TEACHER', 'ROLE_SUPER_ADMIN'));
        return $this->getUserService()->getUser($user['id']);
    }

    public function initDefaultSetting()
    {
        $settingService = $this->getSettingService();

        $defaultSetting = array();
        $defaultSetting['user_name'] ='顾客';
        $defaultSetting['chapter_name'] ='章';
        $defaultSetting['part_name'] ='节';

        $default = $settingService->get('default', array());
        $defaultSetting = array_merge($default, $defaultSetting);

        $settingService->set('default', $defaultSetting);
    }

    public function initKey()
    {
        $settings = $this->getSettingService()->get('storage', array());
        if (!empty($settings['cloud_key_applied'])) {
            return array(
                'accessKey' => '您的Key已生成，请直接进入系统',
                'secretKey' => '---',
            );
        }

        $applier = new KeyApplier();

        $users = $this->getUserService()->searchUsers(array('roles' => 'ROLE_SUPER_ADMIN'), array('createdTime', 'DESC'), 0, 1);

        if (empty($users) or empty($users[0])) {
            return array('error' => '管理员账号不存在，创建Key失败');
        }
        $keys = $applier->applyKey($users[0], 'opensource', 'install');

        if (empty($keys['accessKey']) or empty($keys['secretKey'])) {
            return array('error' => 'Key生成失败，请检查服务器网络后，重试！');
        }

        $settings['cloud_access_key'] = $keys['accessKey'];
        $settings['cloud_secret_key'] = $keys['secretKey'];
        $settings['cloud_key_applied'] = 1;

        $this->getSettingService()->set('storage', $settings);

        return $keys;
    }

    public function initRefundSetting()
    {

        $setting = array(
            'maxRefundDays' => 10,
            'applyNotification' => '您好，您退款的产品为{{course}}，管理员已收到您的退款申请，请耐心等待退款审核结果。',
            'successNotification' => '您好，您申请退款产品{{course}} 审核通过，将为您退款{{amount}}元。',
            'failedNotification' => '您好，您申请退款产品{{course}} 审核未通过，请与管理员再协商解决纠纷。',
        );
        $setting = $this->getSettingService()->set('refund', $setting);

    }

    public function initArticleSetting()
    {
        $setting = array(
            'name' => '资讯频道', 'pageNums' => 20
        );
        $setting = $this->getSettingService()->set('article', $setting);
    }

    public function initSiteSettings($settings)
    {
        $default = array(
            'name'=> $settings['sitename'],
            'slogan'=>'',
            'url'=>'',
            'logo'=>'',
            'seo_keywords'=>'',
            'seo_description'=>'',
            'master_email'=> $settings['admin']['email'],
            'icp'=>'',
            'analytics'=>'',
            'status'=>'open',
            'closed_note'=>'',
            'homepage_template'=>'less'
        );

        $this->getSettingService()->set('site', $default);
    }


    public function initRegisterSetting($user)
    {
        $emailBody = <<<'EOD'
Hi, {{nickname}}

欢迎加入{{sitename}}!

请点击下面的链接完成注册：

{{verifyurl}}

如果以上链接无法点击，请将上面的地址复制到你的浏览器(如IE)的地址栏中打开，该链接地址24小时内打开有效。

感谢对{{sitename}}的支持！

{{sitename}} {{siteurl}}

(这是一封自动产生的email，请勿回复。)
EOD;

        $default = array(
            'register_mode'=>'email',
            'email_activation_title' => '请激活您的{{sitename}}账号',
            'email_activation_body' => trim($emailBody),
            'welcome_enabled' => 'opened',
            'welcome_sender' => $user['nickname'],
            'welcome_methods' => array(),
            'welcome_title' => '欢迎加入{{sitename}}',
            'welcome_body' => '您好{{nickname}}，我是{{sitename}}的管理员，欢迎加入{{sitename}}，祝您浏览愉快。如有问题，随时与我联系。',
        );

        $this->getSettingService()->set('auth', $default);
    }

    public function initMailerSetting($sitename)
    {
        $default = array(
            'enabled'=>0,
            'host'=>'smtp.example.com',
            'port'=>'25',
            'username'=>'user@example.com',
            'password'=>'',
            'from'=>'user@example.com',
            'name'=> $sitename,
        );
        $this->getSettingService()->set('mailer', $default);
    }

    public function initPaymentSetting()
    {
        $default = array(
            'enabled'=>0,
            'bank_gateway'=>'none',
            'alipay_enabled'=>0,
            'alipay_key'=>'',
            'alipay_secret' => '',
        );
        $this->getSettingService()->set('payment', $default);
    }

    public function initStorageSetting()
    {
        $default = array(
            'upload_mode'=>'local',
            'cloud_access_key'=>'',
            'cloud_secret_key'=>'',
            'cloud_api_server'=>'http://api.edusoho.net',
            'cloud_bucket'=>'',
        );

        $this->getSettingService()->set('storage', $default);
    }

    public function initTag()
    {
        $defaultTag = $this->getTagService()->getTagByName('默认标签');
        if (!$defaultTag) {
            $this->getTagService()->addTag(array('name' => '默认标签'));
        }
    }

    public function initCategory()
    {
        $group = $this->getCategoryService()->addGroup(array(
            'name' => '产品分类',
            'code' => 'course',
            'depth' => 2,
        ));

        $this->getCategoryService()->createCategory(array(
            'name' => '默认分类',
            'code' => 'default',
            'weight' => 100,
            'groupId' => $group['id'],
            'parentId' => 0,
        ));
    }

    public function initFile()
    {
        $this->getFileService()->addFileGroup(array(
            'name' => '默认文件组',
            'code' => 'default',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '缩略图',
            'code' => 'thumb',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '产品',
            'code' => 'course',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '用户',
            'code' => 'user',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '产品私有文件',
            'code' => 'course_private',
            'public' => 0,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '资讯',
            'code' => 'article',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '临时目录',
            'code' => 'tmp',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '全局设置文件',
            'code' => 'system',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '小组',
            'code' => 'group',
            'public' => 1,
        ));

    }

    public function initPages()
    {
        $this->getContentService()->createContent(array(
            'title'=>'关于我们',
            'type'=>'page',
            'alias'=>'aboutus',
            'body'=>'',
            'template'=>'default',
            'status'=>'published',
        ));

        $this->getContentService()->createContent(array(
            'title'=>'常见问题',
            'type'=>'page',
            'alias'=>'questions',
            'body'=>'',
            'template'=>'default',
            'status'=>'published',
        ));
    }

    public function initNavigations()
    {
        $this->getNavigationService()->createNavigation(array(
            'name'=>'商家资源', 
            'url'=> 'teacher', 
            'sequence' => 1,
            'isNewWin'=>0,
            'isOpen'=> 1,
            'type'=>'top'
        ));

        $this->getNavigationService()->createNavigation(array(
            'name'=>'常见问题', 
            'url'=> 'page/questions', 
            'sequence' => 2,
            'isNewWin'=>0,
            'isOpen'=> 1,
            'type'=>'top'
        ));

        $this->getNavigationService()->createNavigation(array(
            'name' => '关于我们', 
            'url' => 'page/aboutus',
            'sequence' => 2,
            'isNewWin' => 0,
            'isOpen' => 1,
            'type' => 'top'
        ));
    }

    public function initThemes()
    {
        $this->getSettingService()->set('theme', array('uri' => 'default'));
    }

    public function initBlocks()
    {
        $themeDir = realpath(__DIR__ . '/../themes/');

        $metaFiles = array(
            'system' => "{$themeDir}/block.json",
            'default' => "{$themeDir}/default/block.json",
            'autumn' => "{$themeDir}/autumn/block.json"
        );

        foreach ($metaFiles as $category => $file) {
            $metas = file_get_contents($file);
            $metas = json_decode($metas, true);

            foreach ($metas as $code => $meta) {

                $data = array();
                foreach ($meta['items'] as $key => $item) {
                    $data[$key] = $item['default'];
                }

                $filename = __DIR__ . '/blocks/' . "block-" . md5($code) . '.html';
                if (file_exists($filename)) {
                    $content = file_get_contents($filename);
                } else {
                    $content = '';
                }

                $this->getBlockService()->createBlock(array(
                    'title' => $meta['title'] ,
                    'mode' => 'template' ,
                    'templateName' => $meta['templateName'],
                    'content' => $content,
                    'code' => $code,
                    'meta' => $meta,
                    'data' => $data,
                    'category' => $category
                ));
            }

        }

    }

    public function initLockFile()
    {
        file_put_contents(__DIR__ . '/../../app/data/install.lock', '');
    }

    private function getUserService()
    {
        return ServiceKernel::instance()->createService('User.UserService');
    }

    private function getSettingService()
    {
        return ServiceKernel::instance()->createService('System.SettingService');
    }

    private function getCategoryService()
    {
        return ServiceKernel::instance()->createService('Taxonomy.CategoryService');
    }

    private function getTagService()
    {
        return ServiceKernel::instance()->createService('Taxonomy.TagService');
    }

    private function getFileService()
    {
        return ServiceKernel::instance()->createService('Content.FileService');
    }

    protected function getContentService()
    {
        return ServiceKernel::instance()->createService('Content.ContentService');
    }

    protected function getBlockService()
    {
        return ServiceKernel::instance()->createService('Content.BlockService');
    }

    protected function getNavigationService()
    {
        return ServiceKernel::instance()->createService('Content.NavigationService');
    }

    protected function postRequest($url, $params)
    {
        $userAgent = 'wxchatapp Install Client 1.0';

        $connectTimeout = 10;

        $timeout = 10;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_URL, $url );

        // curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE );

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    
}