<?php 

namespace Topxia\MobileBundleV2\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Topxia\WebBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Service\Util\CloudClientFactory;

class TeacherAppController extends MobileBaseController{

	/*public function indexAction(){
        $conditions = array(
            'roles'=>'ROLE_TEACHER',
            'locked'=>0
        );

        // $paginator = new Paginator(
        //     $this->get('request'),
        //     $this->getUserService()->searchUserCount($conditions),
        //     20
        // );

        $teachers = $this->getUserService()->searchUsers(
            $conditions,
            array('promotedTime', 'DESC'),
            0,
            10
        );

        $teachers = $this->filterUsers($teachers);

        $profiles = $this->getUserService()->findUserProfilesByIds(ArrayToolkit::column($teachers, 'id'));

		return $this->render('TopxiaMobileBundleV2:Teacher:list.html.twig',array(
            'teachers' => $teachers ,
            'profiles' => $profiles
		));
	}*/
	
	public function indexAction(Request $request){
        $start = (int) $request->get("start", 0);
        $limit = (int) $request->get("limit", 10);
		
		$conditions = array(
            'roles'=>'ROLE_TEACHER',
            'locked'=>0
        );

		$result = array();
		$result['status'] = $request->query->get('status', "ok");
		$result['count'] = $limit;
		$result['total'] = $this->getUserService()->searchUserCount($conditions);
        $result['start'] = $start;
        $result['limit'] = $limit;
				
		$teachers = $this->getUserService()->searchUsers(
            $conditions,
            array('promotedTime', 'DESC'),
            $start,
            $limit
        );
		
		$result['profiles'] = $profiles = $this->getUserService()->findUserProfilesByIds(ArrayToolkit::column($teachers, 'id'));
        $result['teachers'] = $teachers = $this->filterUsers($teachers);

        return $this->createJson($request, $result);
	}
}