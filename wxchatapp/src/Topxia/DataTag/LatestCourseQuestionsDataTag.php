<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class LatestCourseQuestionsDataTag extends CourseBaseDataTag implements DataTag  
{
    /**
     * 获取最新发表的产品问答列表
     *
     * 可传入的参数：
     *   courseId 可选 产品ID
     *   count 必需 产品话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 产品话题
     */

    public function getData(array $arguments)
    {
        $this->checkCount($arguments);
        if (empty($arguments['courseId'])) {
            $conditions = array('type' => 'question');
        } else {
            $conditions = array( 'courseId' => $arguments['courseId'],'type' => 'question');
        }

        $questions = $this->getThreadService()->searchThreads($conditions, 'created', 0, $arguments['count']);

        return $this->getCoursesAndUsers($questions);
    }

}