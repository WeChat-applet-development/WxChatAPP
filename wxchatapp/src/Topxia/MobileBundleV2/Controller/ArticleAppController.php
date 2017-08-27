<?php

namespace Topxia\MobileBundleV2\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Topxia\WebBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;

class ArticleAppController extends MobileBaseController
{
    public function indexAction(Request $request)
    {
        $setting = $this->getSettingService()->get('article', array());
        if (empty($setting)) {
            $setting = array('name' => '资讯频道', 'pageNums' => 20);
        }
        return $this->createJson($request, $setting);
        //return $this->render('TopxiaMobileBundleV2:Article:index.html.twig', array());
    }

    public function categoryAction(Request $request)
    {
        $categoryTree = $this->getArticleCategoryService()->getCategoryTree();
        return $this->createJson($request, $categoryTree);
    }

    public function listAction(Request $request)
    {
        $start = (int) $request->get("start", 0);
        $limit = (int) $request->get("limit", 10);

        $categoryId = $request->get("categoryId");
        $setting = $this->getSettingService()->get('article', array());
        if (empty($setting)) {
            $setting = array('name' => '资讯频道', 'pageNums' => 20);
        }

        $conditions = array(
            'status' => 'published'
        );

        if (!empty($categoryId)) {
            $conditions['categoryId'] = $categoryId;
            $conditions['includeChildren'] = true;
        }
		
		$result = array();
		$result['status'] = $request->query->get('status', "ok");
		$result['count'] = $this->getArticleService()->searchArticlesCount($conditions);
        $result['total'] = $this->getArticleService()->searchArticlesCount($conditions);
        $result['start'] = (int) $request->query->get('start', 0);
        $result['limit'] = (int) $request->query->get('limit', 10);
		
        $latestArticles = $this->getArticleService()->searchArticles($conditions, 'published', $start, $limit);

		$result['posts'] = $latestArticles;

        return $this->createJson($request, $result);
		
        //return $this->createJson($request, $latestArticles);
    }

    public function detailAction(Request $request, $id)
    {
        $article = $this->getArticleService()->getArticle($id);

        if (empty($article)) {
            throw $this->createNotFoundException('文章已删除或者未发布！');
        }

        if ($article['status'] != 'published') {
            return $this->createMessageResponse('error','文章不是发布状态，请查看！');
        }

        $setting = $this->getSettingService()->get('article', array());

        if (empty($setting)) {
            $setting = array('name' => '资讯频道', 'pageNums' => 20);
        }

        $conditions = array(
            'status' => 'published'
        );

        $createdTime = $article['createdTime'];

        //$currentArticleId = $article['id'];
        //$articlePrevious = $this->getArticleService()->getArticlePrevious($currentArticleId);
        //$articleNext = $this->getArticleService()->getArticleNext($currentArticleId);
    
        //$articleSetting = $this->getSettingService()->get('article', array());
    
        $this->getArticleService()->hitArticle($id);

        return $this->createJson($request, array(
            "title"=>$article["title"],
            "content"=>$this->render('TopxiaMobileBundleV2:Article:detail.html.twig', array(
            'article' => $article))->getContent()
        ));
    }

    private function getArticleService()
    {
        return $this->getServiceKernel()->createService('Article.ArticleService');
    }

    private function getArticleCategoryService()
    {
        return $this->getServiceKernel()->createService('Article.CategoryService');
    }
}
