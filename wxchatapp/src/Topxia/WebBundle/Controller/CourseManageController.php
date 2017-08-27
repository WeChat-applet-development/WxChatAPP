<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\WebBundle\Form\ReviewType;
use Topxia\Service\Course\CourseService;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\NumberToolkit;
use Topxia\Common\Paginator;
use Topxia\Common\FileToolkit;
use Topxia\Service\Util\LiveClientFactory;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;

class CourseManageController extends BaseController
{
	public function indexAction(Request $request, $id)
	{
        return $this->forward('TopxiaWebBundle:CourseManage:base',  array('id' => $id));
	}

	public function baseAction(Request $request, $id)
	{
		$course = $this->getCourseService()->tryManageCourse($id);
        $courseSetting = $this->getSettingService()->get('course', array());
	    if($request->getMethod() == 'POST'){
            $data = $request->request->all();
            $this->getCourseService()->updateCourse($id, $data);
            $this->setFlashMessage('success', '产品基本信息已保存！');
            return $this->redirect($this->generateUrl('course_manage_base',array('id' => $id))); 
        }

        $tags = $this->getTagService()->findTagsByIds($course['tags']);
        if ($course['type'] == 'live') {
            $client = LiveClientFactory::createClient();
            $liveCapacity = $client->getCapacity();
        } else {
            $liveCapacity = null;
        }
        $default = $this->getSettingService()->get('default', array());
		return $this->render('TopxiaWebBundle:CourseManage:base.html.twig', array(
			'course' => $course,
            'tags' => ArrayToolkit::column($tags, 'name'),
            'liveCapacity' => empty($liveCapacity['capacity']) ? 0 : $liveCapacity['capacity'],
            'liveProvider' => empty($liveCapacity['code']) ? 0 : $liveCapacity['code'],
            'default'=> $default
		));
	}

    public function nicknameCheckAction(Request $request, $courseId)
    {
        $nickname = $request->query->get('value');
        $result = $this->getUserService()->isNicknameAvaliable($nickname);
        if ($result) {
            $response = array('success' => false, 'message' => '该用户还不存在！');
        } else {
            $user = $this->getUserService()->getUserByNickname($nickname);
            $isCourseStudent = $this->getCourseService()->isCourseStudent($courseId, $user['id']);
            if($isCourseStudent){
                $response = array('success' => false, 'message' => '该用户已是本产品的顾客了！');
            } else {
                $response = array('success' => true, 'message' => '');
            }
            
            $isCourseTeacher = $this->getCourseService()->isCourseTeacher($courseId, $user['id']);
            if($isCourseTeacher){
                $response = array('success' => false, 'message' => '该用户是本产品的商家，不能添加!');
            }
        }
        return $this->createJsonResponse($response);
    }

	public function detailAction(Request $request, $id)
	{
        
		$course = $this->getCourseService()->tryManageCourse($id);

	    if($request->getMethod() == 'POST'){
            $detail = $request->request->all();
            $detail['goals'] = (empty($detail['goals']) or !is_array($detail['goals'])) ? array() : $detail['goals'];
            $detail['audiences'] = (empty($detail['audiences']) or !is_array($detail['audiences'])) ? array() : $detail['audiences'];

            $this->getCourseService()->updateCourse($id, $detail);
            $this->setFlashMessage('success', '产品详细信息已保存！');

            return $this->redirect($this->generateUrl('course_manage_detail',array('id' => $id))); 
        }

		return $this->render('TopxiaWebBundle:CourseManage:detail.html.twig', array(
			'course' => $course
		));
	}

	public function pictureAction(Request $request, $id)
	{
		$course = $this->getCourseService()->tryManageCourse($id);

		return $this->render('TopxiaWebBundle:CourseManage:picture.html.twig', array(
			'course' => $course,
		));
	}

    public function pictureCropAction(Request $request, $id)
    {
        $course = $this->getCourseService()->tryManageCourse($id);

        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->getCourseService()->changeCoursePicture($course['id'], $data["images"]);
            return $this->redirect($this->generateUrl('course_manage_picture', array('id' => $course['id'])));
        }

        $fileId = $request->getSession()->get("fileId");
        list($pictureUrl, $naturalSize, $scaledSize) = $this->getFileService()->getImgFileMetaInfo($fileId, 480, 270);

        return $this->render('TopxiaWebBundle:CourseManage:picture-crop.html.twig', array(
            'course' => $course,
            'pictureUrl' => $pictureUrl,
            'naturalSize' => $naturalSize,
            'scaledSize' => $scaledSize,
        ));
    }

    public function priceAction(Request $request, $id)
    {
        $course = $this->getCourseService()->tryManageCourse($id);

        $canModifyPrice = true;
        $teacherModifyPrice = $this->setting('course.teacher_modify_price', true);
        if (empty($teacherModifyPrice)) {
            if (!$this->getCurrentUser()->isAdmin()) {
                $canModifyPrice = false;
                goto response;
            }
        }

        if ($request->getMethod() == 'POST') {
            $fields = $request->request->all();

            if (isset($fields['coinPrice'])) {
                $this->getCourseService()->setCoursePrice($course['id'], 'coin', $fields['coinPrice']);
                unset($fields['coinPrice']);
            }

            if (isset($fields['price'])) {
                $this->getCourseService()->setCoursePrice($course['id'], 'default', $fields['price']);
                unset($fields['price']);
            }

            if (!empty($fields)) {
                $course = $this->getCourseService()->updateCourse($id, $fields);
            } else {
                $course = $this->getCourseService()->getCourse($id);
            }

            $this->setFlashMessage('success', '产品价格已经修改成功!');
        }

        response:

        if ($this->isPluginInstalled("Vip") && $this->setting('vip.enabled')) {
            $levels = $this->getLevelService()->findEnabledLevels();
        } else {
            $levels = array();
        }

        if (($course['discountId'] > 0)&&($this->isPluginInstalled("Discount"))) {
            $discount = $this->getDiscountService()->getDiscount($course['discountId']);
        } else {
            $discount = null;
        }

        return $this->render('TopxiaWebBundle:CourseManage:price.html.twig', array(
            'course' => $course,
            'canModifyPrice' => $canModifyPrice,
            'levels' => $this->makeLevelChoices($levels),
            'discount' => $discount,
        ));
    }

    public function dataAction($id)
    {
        $course = $this->getCourseService()->tryManageCourse($id);

        $isLearnedNum=$this->getCourseService()->searchMemberCount(array('isLearned'=>1,'courseId'=>$id));

        $learnTime=$this->getCourseService()->searchLearnTime(array('courseId'=>$id));
        $learnTime=$course["studentNum"]==0 ? 0 : intval($learnTime/$course["studentNum"]);

        $noteCount=$this->getNoteService()->searchNoteCount(array('courseId'=>$id));

        $questionCount=$this->getThreadService()->searchThreadCount(array('courseId'=>$id,'type'=>'question'));

        $lessons=$this->getCourseService()->searchLessons(array('courseId'=>$id),array('createdTime', 'ASC'),0,1000);

        foreach ($lessons as $key => $value) {
            $lessonLearnedNum=$this->getCourseService()->findLearnsCountByLessonId($value['id']);

            $finishedNum=$this->getCourseService()->searchLearnCount(array('status'=>'finished','lessonId'=>$value['id']));
            
            $lessonLearnTime=$this->getCourseService()->searchLearnTime(array('lessonId'=>$value['id']));
            $lessonLearnTime=$lessonLearnedNum==0 ? 0 : intval($lessonLearnTime/$lessonLearnedNum);

            $lessonWatchTime=$this->getCourseService()->searchWatchTime(array('lessonId'=>$value['id']));
            $lessonWatchTime=$lessonWatchTime==0 ? 0 : intval($lessonWatchTime/$lessonLearnedNum);

            $lessons[$key]['LearnedNum']=$lessonLearnedNum;
            $lessons[$key]['length']=intval($lessons[$key]['length']/60);
            $lessons[$key]['finishedNum']=$finishedNum;
            $lessons[$key]['learnTime']=$lessonLearnTime;
            $lessons[$key]['watchTime']=$lessonWatchTime;

            if($value['type']=='testpaper'){
                $paperId=$value['mediaId'];
                $score=$this->getTestpaperService()->searchTestpapersScore(array('testId'=>$paperId));
                $paperNum=$this->getTestpaperService()->searchTestpaperResultsCount(array('testId'=>$paperId));
                
                $lessons[$key]['score']=$finishedNum==0 ? 0 : intval($score/$paperNum);
            }
        }

        return $this->render('TopxiaWebBundle:CourseManage:learning-data.html.twig', array(
            'course' => $course,
            'isLearnedNum'=>$isLearnedNum,
            'learnTime'=>$learnTime,
            'noteCount'=>$noteCount,
            'questionCount'=>$questionCount,
            'lessons'=>$lessons,
        ));
    }

    private function makeLevelChoices($levels)
    {
        $choices = array();
        foreach ($levels as $level) {
            $choices[$level['id']] = $level['name'];
        }
        return $choices;
    }

    public function teachersAction(Request $request, $id)
    {
        $course = $this->getCourseService()->tryManageCourse($id);

        if($request->getMethod() == 'POST'){
        	
            $data = $request->request->all();
            $data['ids'] = empty($data['ids']) ? array() : array_values($data['ids']);

            $teachers = array();
            foreach ($data['ids'] as $teacherId) {
            	$teachers[] = array(
            		'id' => $teacherId,
            		'isVisible' => empty($data['visible_' . $teacherId]) ? 0 : 1
        		);
            }

            $this->getCourseService()->setCourseTeachers($id, $teachers);
            $this->setFlashMessage('success', '商家设置成功！');

            return $this->redirect($this->generateUrl('course_manage_teachers',array('id' => $id))); 
        }

        $teacherMembers = $this->getCourseService()->findCourseTeachers($id);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($teacherMembers, 'userId'));

        $teachers = array();
        foreach ($teacherMembers as $member) {
        	if (empty($users[$member['userId']])) {
        		continue;
        	}
        	$teachers[] = array(
                'id' => $member['userId'],
        		'nickname' => $users[$member['userId']]['nickname'],
                'avatar'  => $this->getWebExtension()->getFilePath($users[$member['userId']]['smallAvatar'], 'avatar.png'),
        		'isVisible' => $member['isVisible'] ? true : false,
    		);
        }

        return $this->render('TopxiaWebBundle:CourseManage:teachers.html.twig', array(
            'course' => $course,
            'teachers' => $teachers
        ));
    }

    public function publishAction(Request $request, $id)
    {
    	$this->getCourseService()->publishCourse($id);
    	return $this->createJsonResponse(true);
    }

    public function teachersMatchAction(Request $request)
    {
        $likeString = $request->query->get('q');
        $users = $this->getUserService()->searchUsers(array('nickname'=>$likeString, 'roles'=> 'ROLE_TEACHER'), array('createdTime', 'DESC'), 0, 10);

        $teachers = array();
        foreach ($users as $user) {
            $teachers[] = array(
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'avatar' => $this->getWebExtension()->getFilePath($user['smallAvatar'], 'avatar.png'),
                'isVisible' => 1
            );
        }

        return $this->createJsonResponse($teachers);
    }

    private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

    private function getLevelService()
    {
        return $this->getServiceKernel()->createService('Vip:Vip.LevelService');
    }

    private function getFileService()
    {
        return $this->getServiceKernel()->createService('Content.FileService');
    }

    private function getWebExtension()
    {
        return $this->container->get('topxia.twig.web_extension');
    }
    
    private function getTagService()
    {
        return $this->getServiceKernel()->createService('Taxonomy.TagService');
    }

    private function getNoteService()
    {
        return $this->getServiceKernel()->createService('Course.NoteService');
    }

    private function getThreadService()
    {
        return $this->getServiceKernel()->createService('Course.ThreadService');
    }

    private function getTestpaperService()
    {
        return $this->getServiceKernel()->createService('Testpaper.TestpaperService');
    }

    private function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    } 

    protected function getClassroomService()
    {
        return $this->getServiceKernel()->createService('Classroom:Classroom.ClassroomService');
    }
    protected function getDiscountService()
    {
        return $this->getServiceKernel()->createService('Discount:Discount.DiscountService');
    }


}