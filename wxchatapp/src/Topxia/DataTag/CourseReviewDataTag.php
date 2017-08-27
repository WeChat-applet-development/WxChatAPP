<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class CourseReviewDataTag extends CourseBaseDataTag implements DataTag  
{
    /**
     * 获取一个产品评论
     *
     * 可传入的参数：
     *   reviewId 必需 产品评论ID
     * 
     * @param  array $arguments 参数
     * @return array 产品评论
     */
    
    public function getData(array $arguments)
    {
        $this->checkReviewId($arguments);

    	$review = $this->getReviewService()->getReview($arguments['reviewId']);
        if (empty($review)) {
            return null;
        }
        $review['reviewer'] = $this->getUserService()->getUser($review['userId']);
        $Reviewer = &$review['reviewer'];
        unset($Reviewer['password']);
        unset($Reviewer['salt']);
        $review['course'] = $this->getCourseService()->getCourse($review['courseId']);

        return $review;

    }

    
}
