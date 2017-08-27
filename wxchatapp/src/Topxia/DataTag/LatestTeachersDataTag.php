<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class LatestTeachersDataTag extends CourseBaseDataTag implements DataTag  
{

    /**
     * 获取最新商家列表
     *
     * 可传入的参数：
     *   count    必需 商家数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 商家列表
     */
    public function getData(array $arguments)
    {	

        $this->checkCount($arguments);

        $conditions = array(
            'roles'=>'ROLE_TEACHER',
        );
    	$users = $this->getUserService()->searchUsers($conditions, array('promotedTime', 'DESC'), 0, $arguments['count']);
        
        return $this->unsetUserPasswords($users);
    }

}
