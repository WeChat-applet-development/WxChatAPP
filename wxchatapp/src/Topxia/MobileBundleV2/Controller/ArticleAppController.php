<?php

namespace Topxia\MobileBundleV2\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Topxia\WebBundle\Controller\BaseController;
use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Service\Util\CloudClientFactory;

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
		
		$result = array();
		$result['status'] = $request->query->get('status', "ok");
		$result['posts'] = $categoryTree;

        return $this->createJson($request, $result);
		
        //return $this->createJson($request, $categoryTree);
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
		$result['count'] = $limit;
        $result['total'] = $this->getArticleService()->searchArticlesCount($conditions);
        $result['start'] = $start;
        $result['limit'] = $limit;
		
        $latestArticles = $this->getArticleService()->searchArticles($conditions, 'published', $start, $limit);

		$result['posts'] = $latestArticles = $this->filterArticles($latestArticles);

        return $this->createJson($request, $result);
		
        //return $this->createJson($request, $latestArticles);
    }

    /*public function detailAction(Request $request, $id)
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
		
        return $this->createJson($request, array(
            "title"=>$article["title"],
            "content"=>$this->render('TopxiaMobileBundleV2:Article:detail.html.twig', array(
            'article' => $article))->getContent()
        ));
    }*/
	
	public function detailAction(Request $request, $id)
    {
        $article = $this->getArticleService()->getArticle($id);

        if (empty($article)) {
            throw $this->createNotFoundException('文章已删除或者未发布！');
        }

        if ($article['status'] != 'published') {
            return $this->createMessageResponse('error','文章不是发布状态，请查看！');
        }

		
		$result = array();
		$result['status'] = $request->query->get('status', "ok");
		$result['article'] = $this->filterArticle($article);
		
        return $this->createJson($request, $result);
    }
	
	protected function filterArticle($article)
    {
        if (empty($article)) {
            return null;
        }

        $articles = $this->filterArticles(array($article));

        return current($articles);
    }

    protected function filterArticles($articles)
    {
        if (empty($articles)) {
            return array();
        }

        $self = $this;
        $container = $this->container;
        return array_map(function($article) use ($self, $container) {
            $article['thumb'] = $container->get('topxia.twig.web_extension')->getFilePath($article['thumb'], 'article-large.png', true);
            $article['originalThumb'] = $container->get('topxia.twig.web_extension')->getFilePath($article['originalThumb'], 'article-large.png', true);
            if (empty($article['picture'])) {
				$article['picture'] = $article['originalThumb'];
			} else {
			
			}
            $article['body'] = $self->convertAbsoluteUrl($container->get('request'), $article['body']);

            return $article;
        }, $articles);
    }
	
	public function convertAbsoluteUrl($request, $html)
    {
        $baseUrl = $request->getSchemeAndHttpHost();
        $html = preg_replace_callback('/src=[\'\"]\/(.*?)[\'\"]/', function($matches) use ($baseUrl) {
            return "src=\"{$baseUrl}/{$matches[1]}\"";
        }, $html);

        return $html;

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
