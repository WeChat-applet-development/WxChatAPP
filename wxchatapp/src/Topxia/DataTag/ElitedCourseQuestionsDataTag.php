<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class ElitedCourseQuestionsDataTag extends CourseBaseDataTag implements DataTag  
{
    /**
     * 获取精选产品问答列表
     *
     * 可传入的参数：
     *   courseId 必需 产品ID
     *   count 必需 产品话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品话题
     */

    public function getData(array $arguments)
    {
        $this->checkCourseId($arguments);
        $this->checkCount($arguments);

        $conditions = array( 'courseId' => $arguments['courseId'],'type' => 'question','isElite' => '1');
    	$questions = $this->getThreadService()->searchThreads($conditions, 'created', 0, $arguments['count']);
        $questions['courses'] = $this->getCourseService()->getCourse($arguments['courseId']);

        return $questions;

    }

}