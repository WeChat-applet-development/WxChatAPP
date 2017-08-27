<?php

namespace Topxia\WebBundle\Controller;
use Topxia\Common\FileToolkit;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;

class GroupController extends BaseController 
{
    public function indexAction() 
    {   
        $mycreatedGroup = array();
        $myJoinGroup = array();

        $activeGroup = $this->getGroupService()->searchGroups(array('status'=>'open',),  array('memberNum', 'DESC'),0, 8);
    
        $recentlyThread = $this->getThreadService()->searchThreads(
            array(
                'createdTime'=>time()-30*24*60*60,
                'status'=>'open'
                ),
            $this->filterSort('byCreatedTimeOnly'),0, 25
        );

        $ownerIds = ArrayToolkit::column($recentlyThread, 'userId');
        $groupIds = ArrayToolkit::column($recentlyThread, 'groupId');
        $userIds =  ArrayToolkit::column($recentlyThread, 'lastPostMemberId');

        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);

        $owners=$this->getUserService()->findUsersByIds($ownerIds);

        $groups=$this->getGroupService()->getGroupsByids($groupIds);

        $user = $this->getCurrentUser();

        if ($user['id']) {

            $membersCount=$this->getGroupService()->searchMembersCount(array('userId'=>$user['id']));

            $start=$membersCount>12 ? rand(0,$membersCount-12) : 0 ;

            $members=$this->getGroupService()->searchMembers(array('userId'=>$user['id']),array('createdTime',"DESC"),$start,
            12);

            $groupIds = ArrayToolkit::column($members, 'groupId');

            $myJoinGroup=$this->getGroupService()->getGroupsByids($groupIds);

        }

        $newGroups=$this->getGroupService()->searchGroups(array('status'=>'open',),
            array('createdTime','DESC'),0,8);

        return $this->render("TopxiaWebBundle:Group:index.html.twig", array(
            'activeGroup' => $activeGroup,
            'myJoinGroup' => $myJoinGroup,
            'lastPostMembers'=>$lastPostMembers,
            'owners'=>$owners,
            'newGroups'=>$newGroups,
            'groupinfo'=>$groups,
            'user'=>$user,  
            'recentlyThread'=>$recentlyThread,
        ));
    }

    public function addGroupAction(Request $request) 
    {
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')!==true) {
            return $this->createMessageResponse('info', '目前只允许管理员创建小组!');
        }

        $user = $this->getCurrentUser();

        if ($request->getMethod() == 'POST') {

            $mygroup = $request->request->all();

            $title=trim($mygroup['group']['grouptitle']);
            if(empty($title)){
                $this->setFlashMessage('danger',"小组名称不能为空！");

                return $this->render("TopxiaWebBundle:Group:groupadd.html.twig");
            }

            $group = array(
                'title' => $mygroup['group']['grouptitle'],
                'about' => $mygroup['group']['about'],
            );

            $group = $this->getGroupService()->addGroup($user,$group);
            return $this->redirect($this->generateUrl('group_logo_set',array('id'=>$group['id'])));
        }

        return $this->render("TopxiaWebBundle:Group:groupadd.html.twig");
    }

    public function memberCenterAction()
    {
        
        $user=$this->getCurrentUser();

        $groupsCount=$this->getGroupService()->searchMembersCount(array('userId'=>$user['id']));
        $members=$this->getGroupService()->searchMembers(array('userId'=>$user['id']),array('createdTime',"DESC"),0,
        9);

        $groupIds = ArrayToolkit::column($members, 'groupId');
        $groups=$this->getGroupService()->getGroupsByids($groupIds);
        $ownThreads=$this->getThreadService()->searchThreads(array('userId'=>$user['id']),array(array('createdTime','DESC')),0,10);
    
        $groupIds = ArrayToolkit::column($ownThreads, 'groupId');
        $threadsCount=$this->getThreadService()->searchThreadsCount(array('userId'=>$user['id']));
        $groupsAsOwnThreads=$this->getGroupService()->getGroupsByids($groupIds);
        
        $userIds = ArrayToolkit::column($ownThreads, 'lastPostMemberId');
        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);
        $collectThreadsIds=$this->getThreadService()->searchThreadCollects(array('userId'=>$user['id']),array('id',"DESC"), 0,10);
        $collectThreads = array();
        foreach ($collectThreadsIds as $collectThreadsId) {
            $collectThreads[]=$this->getThreadService()->getThread($collectThreadsId['threadId']);  
        }
        $collectCount=$this->getThreadService()->searchThreadCollectCount(array('userId'=>$user['id']));
        
        $groupIdsAsCollectThreads = ArrayToolkit::column($collectThreads, 'groupId');
        $groupsAsCollectThreads=$this->getGroupService()->getGroupsByids($groupIdsAsCollectThreads);

        $userIds =  ArrayToolkit::column($collectThreads, 'lastPostMemberId');
        $collectLastPostMembers=$this->getUserService()->findUsersByIds($userIds);

        $userIds = ArrayToolkit::column($ownThreads, 'lastPostMemberId');
        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);

        $postThreadsIds=$this->getThreadService()->searchPostsThreadIds(array('userId'=>$user['id']),array('id','DESC'),0,10);
        
        $threads = array();
        foreach ($postThreadsIds as $postThreadsId) {
            $threads[]=$this->getThreadService()->getThread($postThreadsId['threadId']);
        
        }

        $postsCount=$this->getThreadService()->searchPostsThreadIdsCount(array('userId'=>$user['id']));
            

        $groupIdsAsPostThreads = ArrayToolkit::column($threads, 'groupId');
        $groupsAsPostThreads=$this->getGroupService()->getGroupsByids($groupIdsAsPostThreads);

        $userIds =  ArrayToolkit::column($threads, 'lastPostMemberId');
        $postLastPostMembers=$this->getUserService()->findUsersByIds($userIds);

        return $this->render("TopxiaWebBundle:Group:group-member-center.html.twig",array(
            'user'=>$user,
            'groups'=>$groups,
            'threads'=>$threads,
            'threadsCount'=>$threadsCount,
            'postsCount'=>$postsCount,
            'collectCount'=>$collectCount,
            'groupsAsCollectThreads'=>$groupsAsCollectThreads,
            'collectLastPostMembers'=>$collectLastPostMembers,
            'collectThreads'=>$collectThreads,
            'postLastPostMembers'=>$postLastPostMembers,
            'groupsAsPostThreads'=>$groupsAsPostThreads,
            'lastPostMembers'=>$lastPostMembers,
            'groupsAsOwnThreads'=>$groupsAsOwnThreads,
            'ownThreads'=>$ownThreads,
            'groupsCount'=>$groupsCount));

    }

    public function searchAction(Request $request)
    {
        $keyWord=$request->query->get('keyWord') ? : "";

        $paginator=new Paginator(
            $this->get('request'),
            $this->getGroupService()->searchGroupsCount(array('title'=>$keyWord,'status'=>'open')),
            24
            );

        $groups=$this->getGroupService()->searchGroups(
                array('title'=>$keyWord,'status'=>'open'),
                array('createdTime',"DESC"),$paginator->getOffsetCount(),
                $paginator->getPerPageCount()
        );

        return $this->render("TopxiaWebBundle:Group:search.html.twig",array(
            'paginator'=>$paginator,
            'groups'=>$groups,
          ));
    }

    public function memberJoinAction(Request $request)
    {
        $user=$this->getCurrentUser();

        $admins=$this->getGroupService()->searchMembers(array('userId'=>$user['id'],'role'=>'admin'),
            array('createdTime',"DESC"),0,1000
            );
        $owners=$this->getGroupService()->searchMembers(array('userId'=>$user['id'],'role'=>'owner'),
            array('createdTime',"DESC"),0,1000
            );
        $members=array_merge($admins,$owners);
        $groupIds = ArrayToolkit::column($members, 'groupId');
        $adminGroups=$this->getGroupService()->getGroupsByids($groupIds);

        $paginator=new Paginator(
            $this->get('request'),
            $this->getGroupService()->searchMembersCount(array('userId'=>$user['id'],'role'=>'member')),
            12
            );

        $members=$this->getGroupService()->searchMembers(array('userId'=>$user['id'],'role'=>'member'),array('createdTime',"DESC"),$paginator->getOffsetCount(),
                $paginator->getPerPageCount());

        $groupIds = ArrayToolkit::column($members, 'groupId');
        $groups=$this->getGroupService()->getGroupsByids($groupIds);
        
        return $this->render("TopxiaWebBundle:Group:group-member-join.html.twig",array(
            'user'=>$user,
            'adminGroups'=>$adminGroups,
            'paginator'=>$paginator,
            'groups'=>$groups));

    }

    public function memberThreadsAction()
    {
        $user=$this->getCurrentUser();

        $paginator=new Paginator(
            $this->get('request'),
            $this->getThreadService()->searchThreadsCount(array('userId'=>$user['id'])),
            12
            );

        $threads=$this->getThreadService()->searchThreads(array('userId'=>$user['id']),array(array('createdTime',"DESC")),$paginator->getOffsetCount(),
                $paginator->getPerPageCount());

        $groupIds = ArrayToolkit::column($threads, 'groupId');

        $userIds =  ArrayToolkit::column($threads, 'lastPostMemberId');
        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);
        $groups=$this->getGroupService()->getGroupsByids($groupIds);

        return $this->render("TopxiaWebBundle:Group:group-member-threads.html.twig",array(
            'user'=>$user,
            'paginator'=>$paginator,
            'lastPostMembers'=>$lastPostMembers,
            'threads'=>$threads,
            'groups'=>$groups));

    }

    public function memberPostsAction()
    {
        $user=$this->getCurrentUser();
        $threads=array();
        $paginator=new Paginator(
            $this->get('request'),
            $this->getThreadService()->searchPostsThreadIdsCount(array('userId'=>$user['id'])),
            12
            );

        $postThreadsIds=$this->getThreadService()->searchPostsThreadIds(array('userId'=>$user['id']),
            array('id',"DESC"),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount());

        foreach ($postThreadsIds as $postThreadsId) {
            $threads[]=$this->getThreadService()->getThread($postThreadsId['threadId']);
        
        }
        
        $groupIdsAsPostThreads = ArrayToolkit::column($threads, 'groupId');
        $groupsAsPostThreads=$this->getGroupService()->getGroupsByids($groupIdsAsPostThreads);

        $userIds =  ArrayToolkit::column($threads, 'lastPostMemberId');
        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);
        return $this->render("TopxiaWebBundle:Group:group-member-posts.html.twig",array(
            'user'=>$user,
            'paginator'=>$paginator,
            'threads'=>$threads,
            'lastPostMembers'=>$lastPostMembers,
            'groups'=>$groupsAsPostThreads,
            ));

    }

    public function collectingAction()
    {
        $user=$this->getCurrentUser();

        $threads=array();
        $paginator=new Paginator(
            $this->get('request'),
            $this->getThreadService()->searchThreadCollectCount(array('userId'=>$user['id'])),
            12
            );

        $collectThreadsIds=$this->getThreadService()->searchThreadCollects(
            array('userId'=>$user['id']),
            array('id',"DESC"),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
            );

        foreach ($collectThreadsIds as $collectThreadsId) {
            $threads[]=$this->getThreadService()->getThread($collectThreadsId['threadId']);  
        }
        
        $groupIdsAsPostThreads = ArrayToolkit::column($threads, 'groupId');
        $groupsAsPostThreads=$this->getGroupService()->getGroupsByids($groupIdsAsPostThreads);

        $userIds =  ArrayToolkit::column($threads, 'lastPostMemberId');
        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);
        return $this->render("TopxiaWebBundle:Group:group-member-collect.html.twig",array(
            'user'=>$user,
            'paginator'=>$paginator,
            'threads'=>$threads,
            'lastPostMembers'=>$lastPostMembers,
            'groups'=>$groupsAsPostThreads,
            ));
    }

    public function groupIndexAction(Request $request,$id) 
    {
        $group = $this->getGroupService()->getGroup($id);

        $groupOwner=$this->getUserService()->getUser($group['ownerId']);

        if($group['status']=="close"){
            return $this->createMessageResponse('info','该小组已被关闭');
        }

        $recentlyJoinMember=$this->getGroupService()->searchMembers(array('groupId'=>$id),
            array('createdTime','DESC'),0,12);

        $memberIds = ArrayToolkit::column($recentlyJoinMember, 'userId');

        $user=$this->getCurrentUser();

        $userIsGroupMember=$this->getGroupService()->getMemberByGroupIdAndUserId($id,$user['id']);
        $recentlyMembers=$this->getUserService()->findUsersByIds($memberIds);

        $filters = $this->getThreadSearchFilters($request);

        $conditions = $this->convertFiltersToConditions($id, $filters);  
    
        $paginator = new Paginator(
            $this->get('request'),
            $this->getThreadService()->searchThreadsCount($conditions),
            $conditions['num']  
        );
            
        $threads=$this->getThreadService()->searchThreads(
            $conditions,
            $this->filterSort($filters['sort']),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        $ownerIds = ArrayToolkit::column($threads, 'userId');

        $userIds =  ArrayToolkit::column($threads, 'lastPostMemberId');

        $owners=$this->getUserService()->findUsersByIds($ownerIds);

        $lastPostMembers=$this->getUserService()->findUsersByIds($userIds);

        $activeMembers=$this->getGroupService()->searchMembers(array('groupId'=>$id,'role'=>'member'),
            array('postNum','DESC'),0,12);

        $memberIds = ArrayToolkit::column($activeMembers, 'userId');

        $members=$this->getUserService()->findUsersByIds($memberIds);
        return $this->render("TopxiaWebBundle:Group:groupindex.html.twig", array(
            'groupinfo' => $group,
            'is_groupmember' => $this->getGroupMemberRole($id),
            'recentlyJoinMember'=>$recentlyJoinMember,
            'owner'=>$owners,
            'user'=>$user,
            'groupOwner'=>$groupOwner,
            'id'=>$id,
            'threads'=>$threads,
            'paginator'=>$paginator,
            'condition'=>$filters,
            'lastPostMembers'=>$lastPostMembers,
            'userIsGroupMember'=>$userIsGroupMember,
            'members'=>$recentlyMembers,
                   
        ));
    }

    public function groupMemberAction(Request $request,$id) 
    {
        $group = $this->getGroupService()->getGroup($id);

        if($group['status']=="close"){
            return $this->createMessageResponse('info','该小组已被关闭');
        }

        $user=$this->getCurrentUser();

        $paginator = new Paginator(
            $this->get('request'),
            $this->getGroupService()->searchMembersCount(array('groupId'=>$id,'role'=>'member')),
            30
        );

        $Members=$this->getGroupService()->searchMembers(array('groupId'=>$id,'role'=>'member'),
            array('createdTime','DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount());

        $memberIds = ArrayToolkit::column($Members, 'userId');

        $users=$this->getUserService()->findUsersByIds($memberIds);
        $owner=$this->getUserService()->getUser($group['ownerId']);

        $groupAdmin=$this->getGroupService()->searchMembers(array('groupId'=>$id,'role'=>'admin'),
            array('createdTime','DESC'),
            0,
            1000);

        $groupAdminIds = ArrayToolkit::column($groupAdmin, 'userId');
        $usersLikeAdmin=$this->getUserService()->findUsersByIds($groupAdminIds);

        return $this->render("TopxiaWebBundle:Group:groupmember.html.twig", array(
            'groupinfo' => $group,
            'is_groupmember' => $this->getGroupMemberRole($id),
            'groupmember_info'=>$Members,
            'owner_info'=>$owner,
            'paginator'=>$paginator,
            'members'=>$users,
            'usersLikeAdmin'=>$usersLikeAdmin,
            'groupAdmin'=>$groupAdmin,
        ));
    }

    private function checkManagePermission($id)
    {   
        $user=$this->getCurrentUser();

        if($this->get('security.context')->isGranted('ROLE_ADMIN')==true) return true;
        if($this->getGroupService()->isOwner($id, $user['id'])) return true;
        if($this->getGroupService()->isAdmin($id, $user['id'])) return true;
        return false;
    }

    private function checkOwnerPermission($id)
    {   
        $user=$this->getCurrentUser();

        if($this->get('security.context')->isGranted('ROLE_ADMIN')==true) return true;
        if($this->getGroupService()->isOwner($id, $user['id'])) return true;
        return false;
    }

    public function deleteMembersAction(Request $request,$id)
    {
        $user=$this->getCurrentUser();

        if(!$this->checkManagePermission($id)){
            return $this->createMessageResponse('info', '您没有权限!');
        }
        
        $deleteMemberIds=$request->request->all();

        $group = $this->getGroupService()->getGroup($id);
        $groupUrl = $this->generateUrl('group_show', array('id'=>$id), true);
        if(isset($deleteMemberIds['memberId'])){

            $deleteMemberIds=$deleteMemberIds['memberId'];

            foreach ($deleteMemberIds as $memberId) {
          
                $this->getGroupService()->deleteMemberByGroupIdAndUserId($id,$memberId);

                $this->getNotifiactionService()->notify($memberId,'default',"您已经被移除<a href='{$groupUrl}' target='_blank'><strong>“{$group['title']}”</strong></a>。");
            }
        }

        
        return new Response('success');
    }

    public function setAdminAction(Request $request,$id)
    {
        $user=$this->getCurrentUser();

        if (!$this->checkOwnerPermission($id)) {
            return $this->createMessageResponse('info', '您没有权限!');
        }
        
        $memberIds=$request->request->all();
        $group = $this->getGroupService()->getGroup($id);
        $groupUrl = $this->generateUrl('group_show', array('id'=>$id), true);

        if(isset($memberIds['memberId'])){

            $memberIds=$memberIds['memberId'];

            foreach ($memberIds as $memberId) {
                $member=$this->getGroupService()->getMemberByGroupIdAndUserId($id,$memberId);
                $this->getGroupService()->updateMember($member['id'],array('role'=>'admin'));
                $this->getNotifiactionService()->notify($memberId,'default',"您在<a href='{$groupUrl}' target='_blank'><strong>“{$group['title']}”</strong></a>被组长任命为副组长。");

            }
        }
        return new Response('success');

    }

    public function removeAdminAction(Request $request,$id)
    {
        $user=$this->getCurrentUser();

        if (!$this->checkOwnerPermission($id)) {
            return $this->createMessageResponse('info', '您没有权限!');
        }

        $memberIds=$request->request->all();

        $group = $this->getGroupService()->getGroup($id);
        $groupUrl = $this->generateUrl('group_show', array('id'=>$id), true);

        if(isset($memberIds['adminId'])){

            $memberIds=$memberIds['adminId'];

            foreach ($memberIds as $memberId) {
                $member=$this->getGroupService()->getMemberByGroupIdAndUserId($id,$memberId);
                $this->getGroupService()->updateMember($member['id'],array('role'=>'member'));
                $this->getNotifiactionService()->notify($memberId,'default',"您在<a href='{$groupUrl}' target='_blank'><strong>“{$group['title']}”</strong></a>被组长取消副组长。");

            }
        }

        return new Response('success');

    }

    public function groupSetAction(Request $request,$id)
    {
        $user=$this->getCurrentUser();

        $group = $this->getGroupService()->getGroup($id);

        if (!$this->checkManagePermission($id)) {
            return $this->createMessageResponse('info', '您没有权限!');
        }

        return $this->render("TopxiaWebBundle:Group:setting-info.html.twig", array(
                    'groupinfo' => $group,
                    'is_groupmember' => $this->getGroupMemberRole($id),
                    'id'=>$id,
                    'logo'=>$group['logo'],
                    'backgroundLogo'=>$group['backgroundLogo'],)
        );

    }

    public function logoCropAction(Request $request,$id)
    {

        $group = $this->getGroupService()->getGroup($id);
        $currentUser = $this->getCurrentUser();

        if (!$this->checkManagePermission($id)) {
            return $this->createMessageResponse('info', '您没有权限!');
        }

        if($request->getMethod() == 'POST') {

            $options = $request->request->all();
            if($request->query->get('page')=="backGroundLogoCrop"){
               $this->getGroupService()->changeGroupImg($id, "backgroundLogo", $options["images"]);
            }else{
               $this->getGroupService()->changeGroupImg($id, "logo", $options["images"]);
            }
          
            return $this->redirect($this->generateUrl('group_show', array(
                'id'=>$id,
            )));
        }
        
        $fileId = $request->getSession()->get("fileId");
        list($pictureUrl, $naturalSize, $scaledSize) = $this->getFileService()->getImgFileMetaInfo($fileId, 270, 270);

        return $this->render('TopxiaWebBundle:Group:setting-logo-crop.html.twig',array(
            'groupinfo' => $group,
            'is_groupmember' => $this->getGroupMemberRole($id),
            'pictureUrl' => $pictureUrl,
            'naturalSize' => $naturalSize,
            'scaledSize' => $scaledSize,
        ));

    }

    private function setLogo($request,$user)
    {
        $data = $request->files->get('form');
        $file = $data['avatar'];

        if (!FileToolkit::isImageFile($file)) {
              return $this->createMessageResponse('info', '上传图片格式错误，请上传jpg, gif, png格式的文件。!');
        }
        $filenamePrefix = "user_{$user['id']}_";

        $hash = substr(md5($filenamePrefix . time()), -8);

        $ext = $file->getClientOriginalExtension();

        $filename = $filenamePrefix . $hash . '.' . $ext;

        $directory = $this->container->getParameter('topxia.upload.public_directory') . '/tmp';

        $file = $file->move($directory, $filename);

        $fileName = str_replace('.', '!', $file->getFilename()); 

        return $filename;

    }

    public function setGroupLogoAction(Request $request, $id)
    {
        $user=$this->getCurrentUser();

        $group = $this->getGroupService()->getGroup($id);
        if (!$this->checkManagePermission($id)) {
            return $this->createMessageResponse('info', '您没有权限!');
        }

        return $this->render("TopxiaWebBundle:Group:setting-logo.html.twig", array(
                'groupinfo' => $group,
                'is_groupmember' => $this->getGroupMemberRole($id),
                'id'=>$id,
                'logo'=>$group['logo'],
                'backgroundLogo'=>$group['backgroundLogo'],)
        );

    }
     public function setGroupBackgroundLogoAction(Request $request,$id)
     {
        $user=$this->getCurrentUser();
        
        $group = $this->getGroupService()->getGroup($id);
        if (!$this->checkManagePermission($id)) {
            return $this->createMessageResponse('info', '您没有权限!');
        }

        return $this->render("TopxiaWebBundle:Group:setting-background.html.twig", array(
                'groupinfo' => $group,
                'is_groupmember' => $this->getGroupMemberRole($id),
                'id'=>$id,
                'logo'=>$group['backgroundLogo'],)
        );

    }
    
    public function hotGroupAction($count=15,$colNum=4)
    {
        $hotGroups = $this->getGroupService()->searchGroups(array('status'=>'open',),  array('memberNum', 'DESC'),0, $count);
        
        return $this->render('TopxiaWebBundle:Group:groups-ul.html.twig', array(
                'groups' => $hotGroups,
                'colNum'=>$colNum,
                )
        );       
    }

    public function hotThreadAction($textNum=15)
    {   
        $groupSetting=$this->getSettingService()->get('group', array());

        $time=7*24*60*60;
        if(isset($groupSetting['threadTime_range'])) $time=$groupSetting['threadTime_range']*24*60*60;
        
        $hotThreads = $this->getThreadService()->searchThreads(
            array(
                'createdTime'=>time()-$time,
                'status'=>'open'
                ),
            $this->filterSort('byPostNum'),0, 11
        );

        return $this->render('TopxiaWebBundle:Group:hot-thread.html.twig', array(
                'hotThreads' => $hotThreads,
                'textNum'=>$textNum,
                )
        );       
    }

    private function getGroupMemberRole($userId)
    {
        $user = $this->getCurrentUser();

        if (!$user['id']) return 0;

        if ($this->getGroupService()->isOwner($userId, $user['id'])) return 2;

        if ($this->getGroupService()->isAdmin($userId, $user['id'])) return 3;

        if ($this->getGroupService()->isMember($userId, $user['id'])) return 1;

        return 0;
    }

    public function groupJoinAction($id) 
    {   
        $user=$this->getCurrentUser();

        if (!$user->isLogin()) {
            return $this->createMessageResponse('info', '你好像忘了登录哦？', null, 3000, $this->generateUrl('login'));
        }
        
        try{
            $this->getGroupService()->joinGroup($user,$id);
        }catch (\Exception $e){
            $this->setFlashMessage("danger",$e->getMessage());
        }
        
        return $this->redirect($this->generateUrl('group_show', array(
            'id'=>$id,
        )));
    }
  
    public function groupExitAction($id)
    {
        $user=$this->getCurrentUser();
        $this->getGroupService()->exitGroup($user,$id);

        return $this->redirect($this->generateUrl('group_show', array(
            'id'=>$id,
        )));
    }

    public function groupEditAction(Request $request,$id)
    {
        $currentUser = $this->getCurrentUser();
        if (!$this->checkManagePermission($id)) {
            return $this->createMessageResponse('info', '您没有权限!');
        }

        $groupinfo=$request->request->all();
        $group=array();
        if($groupinfo){
            $group=array(
            'title'=>$groupinfo['group']['grouptitle'],
            'about'=>$groupinfo['group']['about']); 
        }        
        $this->getGroupService()->updateGroup($id,$group);
  
        return $this->redirect($this->generateUrl('group_show', array(
            'id'=>$id,
        )));
    }
   
    private function getThreadService()
    {
        return $this->getServiceKernel()->createService('Group.ThreadService');
    }
    protected function getUserService()
    {
        return $this->getServiceKernel()->createService('User.UserService');
    }
    private function getGroupService() 
    {
        return $this->getServiceKernel()->createService('Group.GroupService');
    }

    private function getNotifiactionService()
    {
        return $this->getServiceKernel()->createService('User.NotificationService');
    }

    private function filterSort($sort)
    {
        switch ($sort) {
            case 'byPostNum':
                $orderBys=array(
                    array('isStick','DESC'),
                    array('postNum','DESC'),
                    array('createdTime','DESC'),
                );
                break;
            case 'byStick':
                $orderBys=array(
                    array('isStick','DESC'),
                    array('createdTime','DESC'),
                );
                break;
            case 'byCreatedTime':
                $orderBys=array(
                    array('isStick','DESC'),
                    array('createdTime','DESC'),
                );
                break;
            case 'byLastPostTime':
                $orderBys=array(
                    array('isStick','DESC'),
                    array('lastPostTime','DESC'),
                );
                break;
            case 'byCreatedTimeOnly':
                $orderBys=array(
                    array('createdTime','DESC'),
                );
                break;
            default:
            
                throw $this->createNotFoundException('参数sort不正确。');
        }
        return $orderBys;
    }
    private function getThreadSearchFilters($request)
    {
        $filters = array();
        $filters['type'] = $request->query->get('type');
        if (!in_array($filters['type'], array('all','elite','reward'))) {
            $filters['type'] = 'all';
        }
        $filters['sort'] = $request->query->get('sort');

        if (!in_array($filters['sort'], array('byCreatedTime', 'byLastPostTime', 'byPostNum'))) {
            $filters['sort'] = 'byCreatedTime';
        }
        $filters['num'] = $request->query->get('num');

        if (!in_array($filters['num'], array(25))) {
            $filters['num'] = 25;
        }
        return $filters;
    }

    protected function getSettingService()
    {
        return $this->getServiceKernel()->createService('System.SettingService');
    }

    protected function getFileService()
    {
        return $this->getServiceKernel()->createService('Content.FileService');
    }
    
    private function convertFiltersToConditions($id, $filters)
    {
        $conditions = array('groupId' => $id,'num'=>10,'status'=>'open');
        switch ($filters['type']) {
            case 'elite':
                $conditions['isElite'] = 1;
                break;
            case 'reward':
                $conditions['type'] = 'reward';
                break;
            default:
                break;
        }
        $conditions['num'] = $filters['num'];
        return $conditions;
    }
}
